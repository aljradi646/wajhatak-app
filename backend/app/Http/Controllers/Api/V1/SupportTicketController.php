<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class SupportTicketController extends Controller {
    public function index(Request $r): JsonResponse {
        return response()->json(['data'=>SupportTicket::query()->where('user_id',$r->user()->id)->latest('updated_at')->paginate(30)]);
    }
    public function store(Request $r): JsonResponse {
        $d=$r->validate([
            'subject'=>['required','string','min:4','max:190'],
            'category'=>['required','in:account,property,agent,security,technical,other'],
            'message'=>['required','string','min:10','max:10000'],
        ],['subject.required'=>'اكتب عنوانًا واضحًا للبلاغ.','category.in'=>'اختر نوعًا صالحًا للبلاغ.','message.min'=>'اكتب تفاصيل لا تقل عن 10 أحرف.']);
        $ticket=SupportTicket::query()->create([
            'reference'=>'WJ-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
            'user_id'=>$r->user()->id,'subject'=>trim($d['subject']),'category'=>$d['category'],'status'=>'open','priority'=>'normal',
        ]);
        $ticket->messages()->create(['user_id'=>$r->user()->id,'sender_role'=>'user','body'=>trim($d['message'])]);
        $ticket->forceFill(['last_reply_at'=>now()])->save();
        return response()->json(['data'=>$ticket->fresh()->load('messages.user:id,name')],201);
    }
    public function show(Request $r,SupportTicket $ticket): JsonResponse {
        abort_unless((int)$ticket->user_id===(int)$r->user()->id,404);
        return response()->json(['data'=>$ticket->load('messages.user:id,name')]);
    }
    public function reply(Request $r,SupportTicket $ticket): JsonResponse {
        abort_unless((int)$ticket->user_id===(int)$r->user()->id,404);
        abort_if($ticket->status==='closed',409,'أُغلق هذا البلاغ. أنشئ بلاغًا جديدًا إذا استمرت المشكلة.');
        $d=$r->validate(['message'=>['required','string','min:2','max:10000']]);
        $ticket->messages()->create(['user_id'=>$r->user()->id,'sender_role'=>'user','body'=>trim($d['message'])]);
        $ticket->forceFill(['status'=>'in_progress','last_reply_at'=>now(),'closed_at'=>null])->save();
        return response()->json(['data'=>$ticket->fresh()->load('messages.user:id,name')],201);
    }
}
