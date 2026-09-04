<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function index()
    {
        return view('admin.locations.index', [
            'countries' => Country::query()->withCount('regions')->orderBy('name_ar')->get(),
            'regions' => Region::query()->with(['country'])->withCount('cities')->orderBy('name_ar')->get(),
            'cities' => City::query()->with(['region'])->withCount('areas')->orderBy('name_ar')->get(),
            'areas' => Area::query()->with(['city'])->orderBy('name_ar')->get(),
            'tabs' => ['countries', 'regions', 'cities', 'areas'],
        ]);
    }

    // ---- Countries ----
    public function storeCountry(Request $request)
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:3'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'is_active' => ['boolean'],
        ]);
        $country = Country::create([
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'code' => $data['code'] ?? null,
            'currency_code' => $data['currency_code'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        ActivityLog::record('location', "تم إنشاء الدولة «{$country->name_ar}»", $country);
        return redirect()->route('admin.locations.index', ['tab' => 'countries'])->with('status', 'تم إنشاء الدولة بنجاح.');
    }

    public function updateCountry(Request $request, Country $country)
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:3'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'is_active' => ['boolean'],
        ]);
        $country->update([
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'code' => $data['code'] ?? null,
            'currency_code' => $data['currency_code'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        ActivityLog::record('location', "تم تحديث الدولة «{$country->name_ar}»", $country);
        return redirect()->route('admin.locations.index', ['tab' => 'countries'])->with('status', 'تم تحديث الدولة بنجاح.');
    }

    public function destroyCountry(Country $country)
    {
        $name = $country->name_ar;
        $country->delete();
        ActivityLog::record('location', "تم حذف الدولة «{$name}»", $country);
        return redirect()->route('admin.locations.index', ['tab' => 'countries'])->with('status', 'تم حذف الدولة بنجاح.');
    }

    // ---- Regions ----
    public function storeRegion(Request $request)
    {
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
        $region = Region::create([
            'country_id' => $data['country_id'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        ActivityLog::record('location', "تم إنشاء المنطقة «{$region->name_ar}»", $region);
        return redirect()->route('admin.locations.index', ['tab' => 'regions'])->with('status', 'تم إنشاء المنطقة بنجاح.');
    }

    public function updateRegion(Request $request, Region $region)
    {
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
        $region->update([
            'country_id' => $data['country_id'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        ActivityLog::record('location', "تم تحديث المنطقة «{$region->name_ar}»", $region);
        return redirect()->route('admin.locations.index', ['tab' => 'regions'])->with('status', 'تم تحديث المنطقة بنجاح.');
    }

    public function destroyRegion(Region $region)
    {
        $name = $region->name_ar;
        $region->delete();
        ActivityLog::record('location', "تم حذف المنطقة «{$name}»", $region);
        return redirect()->route('admin.locations.index', ['tab' => 'regions'])->with('status', 'تم حذف المنطقة بنجاح.');
    }

    // ---- Cities ----
    public function storeCity(Request $request)
    {
        $data = $request->validate([
            'region_id' => ['required', 'exists:regions,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
        $city = City::create([
            'region_id' => $data['region_id'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        ActivityLog::record('location', "تم إنشاء المدينة «{$city->name_ar}»", $city);
        return redirect()->route('admin.locations.index', ['tab' => 'cities'])->with('status', 'تم إنشاء المدينة بنجاح.');
    }

    public function updateCity(Request $request, City $city)
    {
        $data = $request->validate([
            'region_id' => ['required', 'exists:regions,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
        $city->update([
            'region_id' => $data['region_id'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        ActivityLog::record('location', "تم تحديث المدينة «{$city->name_ar}»", $city);
        return redirect()->route('admin.locations.index', ['tab' => 'cities'])->with('status', 'تم تحديث المدينة بنجاح.');
    }

    public function destroyCity(City $city)
    {
        $name = $city->name_ar;
        $city->delete();
        ActivityLog::record('location', "تم حذف المدينة «{$name}»", $city);
        return redirect()->route('admin.locations.index', ['tab' => 'cities'])->with('status', 'تم حذف المدينة بنجاح.');
    }

    // ---- Areas ----
    public function storeArea(Request $request)
    {
        $data = $request->validate([
            'city_id' => ['required', 'exists:cities,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
        $area = Area::create([
            'city_id' => $data['city_id'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        ActivityLog::record('location', "تم إنشاء الحي «{$area->name_ar}»", $area);
        return redirect()->route('admin.locations.index', ['tab' => 'areas'])->with('status', 'تم إنشاء الحي بنجاح.');
    }

    public function updateArea(Request $request, Area $area)
    {
        $data = $request->validate([
            'city_id' => ['required', 'exists:cities,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
        $area->update([
            'city_id' => $data['city_id'],
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        ActivityLog::record('location', "تم تحديث الحي «{$area->name_ar}»", $area);
        return redirect()->route('admin.locations.index', ['tab' => 'areas'])->with('status', 'تم تحديث الحي بنجاح.');
    }

    public function destroyArea(Area $area)
    {
        $name = $area->name_ar;
        $area->delete();
        ActivityLog::record('location', "تم حذف الحي «{$name}»", $area);
        return redirect()->route('admin.locations.index', ['tab' => 'areas'])->with('status', 'تم حذف الحي بنجاح.');
    }

    // ---- Cascade helpers (JSON, used by property forms) ----
    public function regionsFor(Country $country)
    {
        return response()->json($country->regions()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']));
    }

    public function citiesFor(Region $region)
    {
        return response()->json($region->cities()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']));
    }

    public function areasFor(City $city)
    {
        return response()->json($city->areas()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']));
    }
}
