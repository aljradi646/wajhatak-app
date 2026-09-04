<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PropertyFeature;
use Illuminate\Http\Request;

class PropertyFeatureController extends Controller
{
    public function index(Request $request)
    {
        $query = PropertyFeature::query();
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('icon', 'like', "%{$search}%");
            });
        }

        return view('admin.property_features.index', [
            'features' => $query->orderBy('name_ar')->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('admin.property_features.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $propertyFeature = PropertyFeature::create($data);
        ActivityLog::record('property_feature', "تم إنشاء الخاصية «{$propertyFeature->name_ar}»", $propertyFeature);
        return redirect()->route('admin.property-features.index')->with('status', 'تم إنشاء الخاصية بنجاح.');
    }

    public function edit(PropertyFeature $propertyFeature)
    {
        return view('admin.property_features.edit', compact('propertyFeature'));
    }

    public function update(Request $request, PropertyFeature $propertyFeature)
    {
        $data = $this->validateData($request, $propertyFeature);
        $propertyFeature->update($data);
        ActivityLog::record('property_feature', "تم تحديث الخاصية «{$propertyFeature->name_ar}»", $propertyFeature);
        return redirect()->route('admin.property-features.index')->with('status', 'تم تحديث الخاصية بنجاح.');
    }

    public function destroy(PropertyFeature $propertyFeature)
    {
        $propertyFeature->delete();
        ActivityLog::record('property_feature', "تم حذف الخاصية «{$propertyFeature->name_ar}»", $propertyFeature);
        return redirect()->route('admin.property-features.index')->with('status', 'تم حذف الخاصية بنجاح.');
    }

    private function validateData(Request $request, ?PropertyFeature $feature = null): array
    {
        $unique = $feature ? ','.$feature->id : '';
        return $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:property_features,slug'.$unique],
            'icon' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
    }
}
