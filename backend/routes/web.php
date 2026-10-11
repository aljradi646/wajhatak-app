<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicSitePageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// بديل موثوق لرابط `storage:link` الذي لا يعمل جيدًا على الاستضافة المشتركة
// (InfinityFree). يخدم الملفات من قرص `public` مباشرةً متى لم تكن موجودة عبر
// الرابط `public/storage`. آمن: يرفض أي مسار لا يمر بمرشّحات القرص العام.
Route::get('/storage/{path}', function (string $path) {
    $path = (string) str($path)->replace('\\', '/');
    if ($path === '' || str_contains($path, '..')) {
        abort(404);
    }
    try {
        $file = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
    } catch (\Throwable $e) {
        abort(404);
    }
    if (! is_file($file)) {
        abort(404);
    }
    return response()->file($file);
})->where('path', '.*')->name('storage.file');

Route::get('/pages', [PublicSitePageController::class, 'index'])->name('public.pages.index');
Route::get('/pages/{slug}', [PublicSitePageController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('public.pages.show');
Route::get('/privacy', fn () => redirect()->route('public.pages.show', 'privacy'))->name('public.privacy');
Route::get('/terms', fn () => redirect()->route('public.pages.show', 'terms'))->name('public.terms');
Route::get('/about', fn () => redirect()->route('public.pages.show', 'about'))->name('public.about');
Route::get('/how-to-use', fn () => redirect()->route('public.pages.show', 'how-to-use'))->name('public.how-to-use');
Route::get('/contact', fn () => redirect()->route('public.pages.show', 'contact'))->name('public.contact');

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'admin'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

require __DIR__.'/admin.php';
