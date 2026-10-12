<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class SupportTicket extends Model {
    protected $fillable=['reference','user_id','subject','category','priority','status','last_reply_at','closed_at'];
    protected function casts(): array { return ['last_reply_at'=>'datetime','closed_at'=>'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function messages(): HasMany { return $this->hasMany(SupportTicketMessage::class)->orderBy('created_at')->orderBy('id'); }
    public function attachments(): HasMany { return $this->hasMany(SupportTicketAttachment::class)->latest('created_at'); }
}
