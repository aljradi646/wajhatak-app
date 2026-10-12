<?php
namespace App\Http\Controllers;
use App\Models\Setting;
use App\Models\SitePage;
use Illuminate\View\View;
class PublicSitePageController extends Controller {
    public function index(): View {
        return view('public.pages.index',['pages'=>SitePage::query()->where('is_published',true)->orderBy('sort_order')->get(),'siteName'=>Setting::get('site_name','وجهتك')]);
    }
    public function show(string $slug): View {
        $page=SitePage::query()->where('slug',$slug)->where('is_published',true)->firstOrFail();
        return view('public.pages.show',['page'=>$page,'siteName'=>Setting::get('site_name','وجهتك'),'supportEmail'=>$slug==='contact'?Setting::get('support_email',''):null,'supportPhone'=>$slug==='contact'?Setting::get('support_phone',''):null]);
    }
}
