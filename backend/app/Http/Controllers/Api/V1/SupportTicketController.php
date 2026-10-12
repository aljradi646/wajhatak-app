<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->withCount(['messages', 'attachments'])
            ->latest('updated_at')
            ->paginate(30);

        return response()->json(['data' => $tickets]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'min:4', 'max:190'],
            'category' => ['required', 'in:account,property,agent,security,technical,other'],
            'message' => ['required', 'string', 'min:10', 'max:10000'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,txt'],
        ], [
            'subject.required' => 'اكتب عنوانًا واضحًا للبلاغ.',
            'category.in' => 'اختر نوعًا صالحًا للبلاغ.',
            'message.min' => 'اكتب تفاصيل لا تقل عن 10 أحرف.',
            'attachments.max' => 'يمكن إرفاق خمسة ملفات كحد أقصى.',
            'attachments.*.max' => 'يجب ألا يتجاوز حجم الملف الواحد 10 ميجابايت.',
            'attachments.*.mimes' => 'الملفات المسموحة: JPG وPNG وWEBP وPDF وTXT.',
        ]);

        $ticket = SupportTicket::query()->create([
            'reference' => 'WJ-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
            'user_id' => $request->user()->id,
            'subject' => trim($data['subject']),
            'category' => $data['category'],
            'status' => 'open',
            'priority' => 'normal',
        ]);

        $message = $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'sender_role' => 'user',
            'body' => trim($data['message']),
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('support-tickets/'.$ticket->id, 'local');
            $ticket->attachments()->create([
                'support_ticket_message_id' => $message->id,
                'uploaded_by' => $request->user()->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => Str::limit(preg_replace('/[^\pL\pN._ -]/u', '_', basename($file->getClientOriginalName())) ?: 'attachment', 255, ''),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => $file->getSize(),
            ]);
        }

        $ticket->forceFill(['last_reply_at' => now()])->save();

        return response()->json([
            'data' => $ticket->fresh()->load(['messages.user:id,name', 'attachments']),
        ], 201);
    }

    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_unless((int) $ticket->user_id === (int) $request->user()->id, 404);

        return response()->json([
            'data' => $ticket->load(['messages.user:id,name', 'attachments']),
        ]);
    }

    public function downloadAttachment(Request $request, SupportTicket $ticket, SupportTicketAttachment $attachment): BinaryFileResponse
    {
        abort_unless((int) $ticket->user_id === (int) $request->user()->id, 404);
        abort_unless((int) $attachment->support_ticket_id === (int) $ticket->id, 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return response()->download(
            Storage::disk($attachment->disk)->path($attachment->path),
            $attachment->original_name,
            ['X-Content-Type-Options' => 'nosniff']
        );
    }

    public function reply(Request $request, SupportTicket $ticket): JsonResponse
    {
        abort_unless((int) $ticket->user_id === (int) $request->user()->id, 404);
        abort_if(in_array($ticket->status, ['closed', 'rejected'], true), 409, 'أُغلق هذا البلاغ أو رُفض. أنشئ بلاغًا جديدًا إذا استمرت المشكلة.');

        $data = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:10000'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,txt'],
        ]);

        $message = $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'sender_role' => 'user',
            'body' => trim($data['message']),
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('support-tickets/'.$ticket->id, 'local');
            $ticket->attachments()->create([
                'support_ticket_message_id' => $message->id,
                'uploaded_by' => $request->user()->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => Str::limit(preg_replace('/[^\pL\pN._ -]/u', '_', basename($file->getClientOriginalName())) ?: 'attachment', 255, ''),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => $file->getSize(),
            ]);
        }

        $ticket->forceFill([
            'status' => 'in_progress',
            'last_reply_at' => now(),
            'closed_at' => null,
        ])->save();

        return response()->json([
            'data' => $ticket->fresh()->load(['messages.user:id,name', 'attachments']),
        ], 201);
    }
}
