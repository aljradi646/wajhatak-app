<?php

use App\Http\Controllers\ProfileController;
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
