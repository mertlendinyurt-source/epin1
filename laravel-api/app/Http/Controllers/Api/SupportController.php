<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\EmailService;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class SupportController extends Controller
{
    private EmailService $email;
    private AuditService $audit;

    public function __construct(EmailService $email, AuditService $audit)
    {
        $this->email = $email;
        $this->audit = $audit;
    }

    /**
     * Create new support ticket
     * POST /api/support/tickets
     */
    public function store(Request $request): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user');

        $data = $request->validate([
            'subject' => 'required|string|min:5|max:200',
            'message' => 'required|string|min:10|max:2000',
            'category' => 'required|in:odeme,teslimat,hesap,diger',
        ]);

        // Rate limiting: 3 tickets per 10 minutes
        $recentTickets = Ticket::where('user_id', $authUser['id'])
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentTickets >= 3) {
            return response()->json([
                'success' => false,
                'error' => 'Çok fazla talep oluşturdunuz. Lütfen biraz bekleyin.',
            ], 429);
        }

        // Create ticket
        $ticket = new Ticket();
        $ticket->id = Uuid::uuid4()->toString();
        $ticket->user_id = $authUser['id'];
        $ticket->subject = $data['subject'];
        $ticket->category = $data['category'];
        $ticket->status = 'waiting_admin';
        $ticket->user_can_reply = false; // User can't reply until admin responds
        $ticket->save();

        // Create initial message
        $message = new TicketMessage();
        $message->id = Uuid::uuid4()->toString();
        $message->ticket_id = $ticket->id;
        $message->sender = 'user';
        $message->message = $data['message'];
        $message->save();

        $this->audit->logFromRequest(
            AuditService::TICKET_CREATE,
            $authUser['id'],
            'ticket',
            $ticket->id
        );

        return response()->json([
            'success' => true,
            'data' => $ticket->toApiArray(),
        ], 201);
    }

    /**
     * Get user's tickets
     * GET /api/support/tickets
     */
    public function index(Request $request): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user');

        $tickets = Ticket::where('user_id', $authUser['id'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tickets->map->toApiArray(),
        ]);
    }

    /**
     * Get single ticket with messages
     * GET /api/support/tickets/{ticketId}
     */
    public function show(Request $request, string $ticketId): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user');

        $ticket = Ticket::where('id', $ticketId)
            ->where('user_id', $authUser['id'])
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'error' => 'Talep bulunamadı',
            ], 404);
        }

        $messages = $ticket->messages;

        $data = $ticket->toApiArray();
        $data['messages'] = $messages->map->toApiArray();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Send message to ticket (user)
     * POST /api/support/tickets/{ticketId}/messages
     */
    public function sendMessage(Request $request, string $ticketId): JsonResponse
    {
        $authUser = $request->attributes->get('auth_user');

        $ticket = Ticket::where('id', $ticketId)
            ->where('user_id', $authUser['id'])
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'error' => 'Talep bulunamadı',
            ], 404);
        }

        // Check if user can reply
        if (!$ticket->user_can_reply) {
            return response()->json([
                'success' => false,
                'error' => 'Admin yanıtı bekleniyor. Şu anda mesaj gönderemezsiniz.',
            ], 403);
        }

        // Check if closed
        if ($ticket->status === 'closed') {
            return response()->json([
                'success' => false,
                'error' => 'Bu talep kapatılmış',
            ], 403);
        }

        $data = $request->validate([
            'message' => 'required|string|min:2|max:2000',
        ]);

        // Create message
        $message = new TicketMessage();
        $message->id = Uuid::uuid4()->toString();
        $message->ticket_id = $ticketId;
        $message->sender = 'user';
        $message->message = $data['message'];
        $message->save();

        // Update ticket: user can't reply again until admin responds
        $ticket->status = 'waiting_admin';
        $ticket->user_can_reply = false;
        $ticket->touch(); // Update updated_at
        $ticket->save();

        return response()->json([
            'success' => true,
            'data' => $message->toApiArray(),
        ]);
    }
}