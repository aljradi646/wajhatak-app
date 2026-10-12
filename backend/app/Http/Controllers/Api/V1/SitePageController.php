<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SitePage;
use Illuminate\Http\JsonResponse;
class SitePageController extends Controller {
    public function index(): JsonResponse {
        return response()->json(['data'=>SitePage::query()->where('is_published',true)->orderBy('sort_order')->get(['slug','title','summary','category','updated_at'])]);
    }
    public function show(string $slug): JsonResponse {
        $p=SitePage::query()->where('slug',$slug)->where('is_published',true)->firstOrFail();
        return response()->json(['data'=>[
            'slug'=>$p->slug,'title'=>$p->title,'summary'=>$p->summary,'content'=>$p->content,
            'category'=>$p->category,'updated_at'=>$p->updated_at,'site_name'=>Setting::get('site_name','وجهتك'),
            'contact_email'=>$p->slug==='contact'?Setting::get('support_email',''):null,
            'contact_phone'=>$p->slug==='contact'?Setting::get('support_phone',''):null,
        ]]);
    }
}
