<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EmailService;
use Ramsey\Uuid\Uuid;

class SupportController extends Controller
{
    private AuditService $audit;
    private EmailService $email;

    public function __construct(AuditService $audit, EmailService $email)
    {
        $this->audit = $audit;
        $this->email = $email;
    }

    /**
     * Get all tickets
     * GET /api/admin/support/tickets
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $category = $request->query('category');

        $query = Ticket::with('user');

        if ($status) $query->where('status', $status);
        if ($category) $query->where('category', $category);

        $tickets = $query->orderBy('updated_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $tickets->map->toApiArrayWithUser(),
        ]);
    }

    /**
     * Get single ticket with messages
     * GET /api/admin/support/tickets/{ticketId}
     */
    public function show(Request $request, string $ticketId): JsonResponse
    {
        $ticket = Ticket::with(['user', 'messages'])->find($ticketId);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'error' => 'Talep bulunamadı',
            ], 404);
        }

        $data = $ticket->toApiArrayWithUser();
        $data['messages'] = $ticket->messages->map->toApiArray();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Admin reply to ticket
     * POST /api/admin/support/tickets/{ticketId}/messages
     */
    public function sendMessage(Request $request, string $ticketId): JsonResponse
    {
        $authAdmin = $request->attributes->get('auth_admin');

        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return response()->json([
                'success' => false,
                'error' => 'Talep bulunamadı',
            ], 404);
        }

        if ($ticket->status === 'closed') {
            return response()->json([
                'success' => false,
                'error' => 'Bu talep kapatılmış',
            ], 403);
        }

        $data = $request->validate([
            'message' => 'required|string|min:2|max:5000',
        ]);

        // Create message
        $message = new TicketMessage();
        $message->id = Uuid::uuid4()->toString();
        $message->ticket_id = $ticketId;
        $message->sender = 'admin';
        $message->message = $data['message'];
        $message->admin_username = $authAdmin['username'] ?? null;
        $message->save();

        // Update ticket: user can now reply
        $ticket->status = 'waiting_user';
        $ticket->user_can_reply = true;
        $ticket->touch();
        $ticket->save();

        // Send email notification to user
        $user = User::find($ticket->user_id);
        if ($user) {
            try {
                $this->email->sendSupportReply(
                    $ticket->toArray(),
                    $user->toArray(),
                    $data['message']
                );
            } catch (\Exception $e) {}
        }

        $this->audit->logFromRequest(
            AuditService::TICKET_REPLY,
            $authAdmin['id'] ?? null,
            'ticket',
            $ticketId
        );

        return response()->json([
            'success' => true,
            'data' => $message->toApiArray(),
        ]);
    }

    /**
     * Close ticket
     * POST /api/admin/support/tickets/{ticketId}/close
     */
    public function close(Request $request, string $ticketId): JsonResponse
    {
        $authAdmin = $request->attributes->get('auth_admin');

        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return response()->json([
                'success' => false,
                'error' => 'Talep bulunamadı',
            ], 404);
        }

        $ticket->status = 'closed';
        $ticket->user_can_reply = false;
        $ticket->closed_by = $authAdmin['username'] ?? null;
        $ticket->closed_at = now();
        $ticket->save();

        $this->audit->logFromRequest(
            AuditService::TICKET_CLOSE,
            $authAdmin['id'] ?? null,
            'ticket',
            $ticketId
        );

        return response()->json([
            'success' => true,
            'data' => $ticket->toApiArray(),
            'message' => 'Talep kapatıldı',
        ]);
    }
}