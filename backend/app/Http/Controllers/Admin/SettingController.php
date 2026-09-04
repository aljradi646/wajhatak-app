<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;
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
