<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplateVersion extends Model
{
    protected $fillable=['email_template_id','version','subject','html_content','text_content','css_styles','variables','created_by','change_note'];
    protected $casts=['css_styles'=>'array','variables'=>'array','version'=>'integer'];
    public function template(): BelongsTo { return $this->belongsTo(EmailTemplate::class,'email_template_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
}
