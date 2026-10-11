<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SitePage extends Model {
    protected $fillable=['slug','title','summary','content','category','is_published','sort_order','seo_title','seo_description'];
    protected function casts(): array { return ['is_published'=>'boolean','sort_order'=>'integer']; }
}
