<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Services\Mail\UnifiedMailService;
use App\Services\Notifications\FirebasePushNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:open,in_progress,waiting_user,resolved,closed,rejected,rejected'],
            'category' => ['nullable', 'in:account,property,agent,security,technical,other'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:190'],
        ]);

        $query = SupportTicket::query()->with('user')->withCount(['messages', 'attachments'])->latest('updated_at');
        $status = $filters['status'] ?? null;
        $category = $filters['category'] ?? null;
        $search = trim((string) ($filters['search'] ?? ''));
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        if ($status) {
            $query->where('status', $status);
        }
        if ($category) {
            $query->where('category', $category);
        }
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($search !== '') {
            $query->where(fn ($builder) => $builder
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($user) => $user
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")));
        }

        return view('admin.support-tickets.index', [
            'tickets' => $query->paginate(20)->withQueryString(),
            'statusFilter' => $status,
            'categoryFilter' => $category,
            'search' => $search,
            'fromFilter' => $from,
            'toFilter' => $to,
            'counts' => [
                'open' => SupportTicket::where('status', 'open')->count(),
                'rejected' => SupportTicket::where('status', 'rejected')->count(),
                'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
                'waiting_user' => SupportTicket::where('status', 'waiting_user')->count(),
                'resolved' => SupportTicket::where('status', 'resolved')->count(),
            ],
        ]);
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['user', 'messages.user:id,name,email', 'attachments.uploader:id,name']);
        return view('admin.support-tickets.show', compact('ticket'));
    }

    public function downloadAttachment(SupportTicket $ticket, SupportTicketAttachment $attachment): BinaryFileResponse
    {
        abort_unless((int) $attachment->support_ticket_id === (int) $ticket->id, 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return response()->download(
            Storage::disk($attachment->disk)->path($attachment->path),
            $attachment->original_name,
            ['X-Content-Type-Options' => 'nosniff']
        );
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:10000'],
            'status' => ['nullable', 'in:in_progress,waiting_user,resolved,closed,rejected'],
            'send_email' => ['nullable', 'boolean'],
        ]);
        $sendEmail = $request->boolean('send_email');
        $databaseNotification = null;

        $message = DB::transaction(function () use ($request, $ticket, $data, $sendEmail, &$databaseNotification) {
            $message = $ticket->messages()->create([
                'user_id' => $request->user()->id,
                'sender_role' => 'admin',
                'body' => trim($data['message']),
                'email_status' => $sendEmail ? 'pending' : 'not_requested',
            ]);
            $status = $data['status'] ?? 'waiting_user';
            $ticket->forceFill([
                'status' => $status,
                'last_reply_at' => now(),
                'closed_at' => in_array($status, ['resolved', 'closed'], true) ? now() : null,
            ])->save();

            $databaseNotification = $ticket->user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'support_ticket_reply',
                'data' => [
                    'kind' => 'support_ticket_reply',
                    'title' => 'رد الإدارة على البلاغ '.$ticket->reference,
                    'message' => trim($data['message']),
                    'support_ticket_id' => $ticket->id,
                    'ticket_reference' => $ticket->reference,
                    'ticket_subject' => $ticket->subject,
                ],
            ]);

            return $message;
        });

        if ($databaseNotification !== null) {
            app(FirebasePushNotificationService::class)->sendDatabaseNotification($ticket->user, $databaseNotification);
        }

        $mailState = null;
        if ($sendEmail) {
            try {
                $user = $ticket->user;
                $body = "مرحبًا {$user->name}،\n\nورد رد جديد من فريق وجهتك على البلاغ {$ticket->reference}.\n\nالعنوان: {$ticket->subject}\n\nالرد:\n".trim($data['message'])."\n\nافتح تطبيق وجهتك لمتابعة المحادثة.";
                app(UnifiedMailService::class)->send($user->email, 'رد جديد على بلاغك '.$ticket->reference.' — وجهتك', $body, [
                    'html' => '<div dir="rtl" style="font-family:Arial,sans-serif;line-height:1.9;color:#16332b"><h2>رد فريق وجهتك</h2><p>مرحبًا '.e($user->name).'، ورد رد جديد على البلاغ <strong>'.e($ticket->reference).'</strong>.</p><p><strong>'.e($ticket->subject).'</strong></p><div style="padding:16px;border-radius:12px;background:#f2f8f5;white-space:pre-wrap">'.e(trim($data['message'])).'</div><p>افتح تطبيق وجهتك لمتابعة المحادثة.</p></div>',
                ]);
                $mailState = 'sent';
                $message->forceFill(['email_status' => 'sent', 'email_sent_at' => now()])->save();
            } catch (Throwable $exception) {
                report($exception);
                $mailState = 'failed';
                $message->forceFill(['email_status' => 'failed'])->save();
            }
        }

        ActivityLog::record('support_ticket', 'رد الإدارة على البلاغ '.$ticket->reference, $ticket);
        $notice = $mailState === 'failed'
            ? 'تم إرسال الرد داخل التطبيق، لكن تعذر إرسال البريد. راجع إعدادات البريد.'
            : ($mailState === 'sent' ? 'تم إرسال الرد داخل التطبيق والبريد الإلكتروني.' : 'تم إرسال الرد داخل التطبيق.');

        return redirect()->route('admin.support-tickets.show', $ticket)->with('status', $notice);
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:open,in_progress,waiting_user,resolved,closed']]);
        $ticket->forceFill([
            'status' => $data['status'],
            'closed_at' => in_array($data['status'], ['resolved', 'closed'], true) ? now() : null,
        ])->save();
        ActivityLog::record('support_ticket', 'تحديث حالة البلاغ '.$ticket->reference, $ticket);

        return back()->with('status', 'تم تحديث حالة البلاغ.');
    }
}
