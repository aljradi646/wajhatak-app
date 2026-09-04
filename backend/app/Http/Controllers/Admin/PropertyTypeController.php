<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PropertyType;
use Illuminate\Http\Request;

class PropertyTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = PropertyType::query()->withCount('properties');
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return view('admin.property_types.index', [
            'types' => $query->orderBy('name_ar')->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('admin.property_types.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $propertyType = PropertyType::create($data);
        ActivityLog::record('property_type', "تم إنشاء نوع العقار «{$propertyType->name_ar}»", $propertyType);
        return redirect()->route('admin.property-types.index')->with('status', 'تم إنشاء نوع العقار بنجاح.');
    }

    public function edit(PropertyType $propertyType)
    {
        return view('admin.property_types.edit', compact('propertyType'));
    }

    public function update(Request $request, PropertyType $propertyType)
    {
        $data = $this->validateData($request, $propertyType);
        $propertyType->update($data);
        ActivityLog::record('property_type', "تم تحديث نوع العقار «{$propertyType->name_ar}»", $propertyType);
        return redirect()->route('admin.property-types.index')->with('status', 'تم تحديث نوع العقار بنجاح.');
    }

    public function destroy(PropertyType $propertyType)
    {
        if ($propertyType->properties()->exists()) {
            return back()->with('error', 'لا يمكن حذف نوع عقار مرتبط بعقارات.');
        }
        $propertyType->delete();
        ActivityLog::record('property_type', "تم حذف نوع العقار «{$propertyType->name_ar}»", $propertyType);
        return redirect()->route('admin.property-types.index')->with('status', 'تم حذف نوع العقار بنجاح.');
    }

    private function validateData(Request $request, ?PropertyType $type = null): array
    {
        $unique = $type ? ','.$type->id : '';
        return $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:property_types,slug'.$unique],
            'is_active' => ['boolean'],
        ]);
    }
}
