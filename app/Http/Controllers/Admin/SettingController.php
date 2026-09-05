<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function __construct()
    {
        // Ensure default settings always exist.
        Setting::seedDefaults();
    }

    public function index(Request $request)
    {
        $query = Setting::query();
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('value', 'like', "%{$search}%");
            });
        }

        // Group settings for display
        $all = Setting::query()->orderBy('key')->get();
        $groups = [
            'عام' => ['site_name', 'site_tagline', 'language', 'default_currency'],
            'تواصل' => ['support_email', 'support_phone', 'address'],
            'النظام' => ['maintenance_mode', 'registration_enabled'],
        ];
        $grouped = [];
        foreach ($groups as $label => $keys) {
            $grouped[$label] = $all->whereIn('key', $keys)->values();
        }
        $other = $all->whereNotIn('key', collect($groups)->flatten()->all())->values();

        return view('admin.settings.index', [
            'settings' => $query->orderBy('key')->paginate(15)->withQueryString(),
            'search' => $search,
            'groups' => $grouped,
            'other' => $other,
            'types' => Setting::TYPES,
        ]);
    }

    public function update(Request $request, Setting $setting)
    {
        $data = $this->validateData($request, $setting);
        $setting->update($data);
        ActivityLog::record('setting', "تم تحديث الإعداد «{$setting->key}»", $setting);
        return redirect()->route('admin.settings.index')->with('status', "تم تحديث الإعداد «{$setting->key}» بنجاح.");
    }

    public function destroy(Setting $setting)
    {
        $key = $setting->key;
        $setting->delete();
        ActivityLog::record('setting', "تم حذف الإعداد «{$key}»", $setting);
        return redirect()->route('admin.settings.index')->with('status', "تم حذف الإعداد «{$key}» بنجاح.");
    }

    /**
     * Save the platform identity (site name, tagline, contact info) and the
     * real logo uploaded by the admin. The logo is stored as branding/logo.png
     * (used in the admin panel + login pages) and a square branding/logo-small.png
     * (browser favicon / small badge).
     */
    public function updateIdentity(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'site_tagline' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'support_email' => ['nullable', 'email', 'max:191'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        Setting::put('site_name', $data['site_name']);
        Setting::put('site_tagline', $data['site_tagline'] ?? '');
        Setting::put('address', $data['address'] ?? '');
        Setting::put('support_phone', $data['support_phone'] ?? '');
        Setting::put('support_email', $data['support_email'] ?? '');
        Setting::put('default_currency', $data['default_currency'] ?? 'YER');

        if ($request->hasFile('logo')) {
            $disk = Storage::disk('public');

            // Remove the old branding files (also written by the demo seeder).
            $disk->delete(['branding/logo.png', 'branding/logo-small.png']);

            $logo = file_get_contents($data['logo']->getRealPath());
            $disk->put('branding/logo.png', $logo);

            $disk->put('branding/logo-small.png', $this->squareLogo($logo));
        }

        ActivityLog::record('setting', 'تم تحديث هوية المنصة والشعار');

        return back()->with('status', 'تم حفظ هوية المنصة بنجاح.');
    }

    /**
     * Create a small square PNG (128x128) from the uploaded logo using GD,
     * so favicons and small brand marks always show a centered crop.
     */
    private function squareLogo(string $source): ?string
    {
        if (! extension_loaded('gd')) {
            return $source;
        }

        $image = @imagecreatefromstring($source);
        if ($image === false) {
            return $source;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $side = min($width, $height);

        $square = imagecreatetruecolor(128, 128);
        imagesavealpha($square, true);
        $transparent = imagecolorallocatealpha($square, 0, 0, 0, 127);
        imagefill($square, 0, 0, $transparent);

        imagecopyresampled(
            $square,
            $image,
            0, 0,
            intdiv($width - $side, 2),
            intdiv($height - $side, 2),
            128, 128,
            $side, $side
        );

        ob_start();
        imagepng($square, null, 9);
        $png = ob_get_clean();
        ob_end_clean();

        imagedestroy($square);
        imagedestroy($image);

        return $png === false ? null : $png;
    }

    /**
     * Add or update a setting by key (works for both create & edit).
     */
    public function quickUpdate(Request $request)
    {
        $request->validate([
            'key' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:50'],
        ]);

        $setting = Setting::put(
            $request->input('key'),
            $request->input('value', ''),
            $request->input('type', 'string')
        );

        ActivityLog::record('setting', "تم حفظ الإعداد «{$setting->key}»", $setting);
        return redirect()->route('admin.settings.index')->with('status', "تم حفظ الإعداد «{$setting->key}» بنجاح.");
    }

    private function validateData(Request $request, ?Setting $setting = null): array
    {
        $unique = $setting ? ','.$setting->id : '';
        return $request->validate([
            'key' => ['required', 'string', 'max:255', 'unique:settings,key'.$unique],
            'value' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in(array_keys(Setting::TYPES))],
        ]);
    }
}
