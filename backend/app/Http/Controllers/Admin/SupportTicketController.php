<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Services\Mail\UnifiedMailService;
use App\Services\Notifications\FirebasePushNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;
class SupportTicketController extends Controller {
    public function index(Request $r): View {
        $q=SupportTicket::query()->with('user')->withCount('messages')->latest('updated_at');
        $status=$r->input('status'); $search=trim((string)$r->input('search'));
        if($status) $q->where('status',$status);
        if($search!=='') $q->where(fn($x)=>$x->where('reference','like',"%{$search}%")->orWhere('subject','like',"%{$search}%")->orWhereHas('user',fn($u)=>$u->where('name','like',"%{$search}%")->orWhere('email','like',"%{$search}%")));
        return view('admin.support-tickets.index',['tickets'=>$q->paginate(20)->withQueryString(),'statusFilter'=>$status,'search'=>$search,'counts'=>[
            'open'=>SupportTicket::where('status','open')->count(),'in_progress'=>SupportTicket::where('status','in_progress')->count(),
            'waiting_user'=>SupportTicket::where('status','waiting_user')->count(),'resolved'=>SupportTicket::where('status','resolved')->count(),
        ]]);
    }
    public function show(SupportTicket $ticket): View {
        $ticket->load(['user','messages.user:id,name,email']);
        return view('admin.support-tickets.show',compact('ticket'));
    }
    public function reply(Request $r,SupportTicket $ticket): RedirectResponse {
        $d=$r->validate(['message'=>['required','string','min:2','max:10000'],'status'=>['nullable','in:in_progress,waiting_user,resolved,closed'],'send_email'=>['nullable','boolean']]);
        $sendEmail=$r->boolean('send_email');
        $databaseNotification=null;
        $message=DB::transaction(function()use($r,$ticket,$d,$sendEmail,&$databaseNotification){
            $m=$ticket->messages()->create(['user_id'=>$r->user()->id,'sender_role'=>'admin','body'=>trim($d['message']),'email_status'=>$sendEmail?'pending':'not_requested']);
            $status=$d['status']??'waiting_user';
            $ticket->forceFill(['status'=>$status,'last_reply_at'=>now(),'closed_at'=>in_array($status,['resolved','closed'],true)?now():null])->save();
            $databaseNotification=$ticket->user->notifications()->create([
                'id'=>(string)Str::uuid(),'type'=>'support_ticket_reply',
                'data'=>['kind'=>'support_ticket_reply','title'=>'رد الإدارة على البلاغ '.$ticket->reference,
                'message'=>trim($d['message']),'support_ticket_id'=>$ticket->id,'ticket_reference'=>$ticket->reference,'ticket_subject'=>$ticket->subject],
            ]);
            return $m;
        });
        if ($databaseNotification !== null) {
            app(FirebasePushNotificationService::class)->sendDatabaseNotification($ticket->user, $databaseNotification);
        }
        $mailState=null;
        if($sendEmail){
            try{
                $user=$ticket->user;
                $body="مرحبًا {$user->name}،\n\nورد رد جديد من فريق وجهتك على البلاغ {$ticket->reference}.\n\nالعنوان: {$ticket->subject}\n\nالرد:\n".trim($d['message'])."\n\nافتح تطبيق وجهتك لمتابعة المحادثة.";
                app(UnifiedMailService::class)->send($user->email,'رد جديد على بلاغك '.$ticket->reference.' — وجهتك',$body,[
                    'html'=>'<div dir="rtl" style="font-family:Arial,sans-serif;line-height:1.9;color:#16332b"><h2>رد فريق وجهتك</h2><p>مرحبًا '.e($user->name).'، ورد رد جديد على البلاغ <strong>'.e($ticket->reference).'</strong>.</p><p><strong>'.e($ticket->subject).'</strong></p><div style="padding:16px;border-radius:12px;background:#f2f8f5;white-space:pre-wrap">'.e(trim($d['message'])).'</div><p>افتح تطبيق وجهتك لمتابعة المحادثة.</p></div>',
                ]);
                $mailState='sent'; $message->forceFill(['email_status'=>'sent','email_sent_at'=>now()])->save();
            }catch(Throwable $e){report($e);$mailState='failed';$message->forceFill(['email_status'=>'failed'])->save();}
        }
        ActivityLog::record('support_ticket','رد الإدارة على البلاغ '.$ticket->reference,$ticket);
        $notice=$mailState==='failed'?'تم إرسال الرد داخل التطبيق، لكن تعذر إرسال البريد. راجع إعدادات البريد.':($mailState==='sent'?'تم إرسال الرد داخل التطبيق والبريد الإلكتروني.':'تم إرسال الرد داخل التطبيق.');
        return redirect()->route('admin.support-tickets.show',$ticket)->with('status',$notice);
    }
    public function updateStatus(Request $r,SupportTicket $ticket): RedirectResponse {
        $d=$r->validate(['status'=>['required','in:open,in_progress,waiting_user,resolved,closed']]);
        $ticket->forceFill(['status'=>$d['status'],'closed_at'=>in_array($d['status'],['resolved','closed'],true)?now():null])->save();
        ActivityLog::record('support_ticket','تحديث حالة البلاغ '.$ticket->reference,$ticket);
        return back()->with('status','تم تحديث حالة البلاغ.');
    }
}
