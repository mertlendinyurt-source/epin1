<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

class UploadController extends Controller
{
    /**
     * Upload file
     * POST /api/admin/upload
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:5120', // 5MB max
            'category' => 'sometimes|string|in:products,logos,heroes,misc',
        ]);

        $file = $request->file('file');
        $category = $request->input('category', 'misc');

        // Validate file type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return response()->json([
                'success' => false,
                'error' => 'Sadece resim dosyaları yüklenebilir (JPEG, PNG, GIF, WebP, SVG)',
            ], 400);
        }

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Uuid::uuid4()->toString() . '.' . $extension;
        
        // Save file
        $path = $file->storeAs('uploads/' . $category, $filename, 'public');
        
        // Generate URL
        $url = '/uploads/' . $category . '/' . $filename;

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $url,
                'filename' => $filename,
                'size' => $file->getSize(),
                'mimeType' => $file->getMimeType(),
            ],
        ]);
    }

    /**
     * Delete file
     * DELETE /api/admin/upload
     */
    public function delete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => 'required|string',
        ]);

        $url = $data['url'];

        // Only allow deleting files in uploads folder
        if (!str_starts_with($url, '/uploads/')) {
            return response()->json([
                'success' => false,
                'error' => 'Geçersiz dosya yolu',
            ], 400);
        }

        // Convert URL to path
        $path = 'public' . $url;

        if (Storage::exists($path)) {
            Storage::delete($path);
            return response()->json([
                'success' => true,
                'message' => 'Dosya silindi',
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Dosya bulunamadı',
        ], 404);
    }
}
