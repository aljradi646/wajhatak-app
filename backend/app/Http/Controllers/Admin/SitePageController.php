<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SitePage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
class SitePageController extends Controller {
    public function index(): View { return view('admin.pages.index',['pages'=>SitePage::query()->orderBy('sort_order')->paginate(20)]); }
    public function create(): View { return view('admin.pages.create',['page'=>new SitePage()]); }
    public function store(Request $request): RedirectResponse {
        $page=SitePage::query()->create($this->validated($request));
        ActivityLog::record('site_page',"تم إنشاء الصفحة «{$page->title}»",$page);
        return redirect()->route('admin.pages.index')->with('status','تم إنشاء الصفحة وحفظها في قاعدة البيانات.');
    }
    public function edit(SitePage $page): View { return view('admin.pages.edit',compact('page')); }
    public function update(Request $request,SitePage $page): RedirectResponse {
        $page->update($this->validated($request,$page));
        ActivityLog::record('site_page',"تم تحديث الصفحة «{$page->title}»",$page);
        return redirect()->route('admin.pages.index')->with('status','تم حفظ تحديثات الصفحة.');
    }
    private function validated(Request $r,?SitePage $page=null): array {
        $data=$r->validate([
            'slug'=>['required','string','max:100','alpha_dash',Rule::unique('site_pages','slug')->ignore($page?->id)],
            'title'=>['required','string','max:190'],'summary'=>['nullable','string','max:500'],
            'content'=>['required','string','min:40','max:50000'],'category'=>['required',Rule::in(['legal','info','help'])],
            'sort_order'=>['required','integer','min:0','max:10000'],'seo_title'=>['nullable','string','max:190'],
            'seo_description'=>['nullable','string','max:320'],'is_published'=>['nullable','boolean'],
        ]);
        $data['is_published']=$r->boolean('is_published');
        return $data;
    }
}
