<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\PropertyFeature;
use App\Models\PropertyType;
use App\Models\Region;
use Illuminate\Http\Request;

class TaxonomyController extends Controller
{
    public function propertyTypes()
    {
        return response()->json(['data' => PropertyType::query()->where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'slug'])]);
    }

    public function features()
    {
        return response()->json(['data' => PropertyFeature::query()->where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'icon'])]);
    }

    public function countries()
    {
        return response()->json(['data' => Country::query()
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'code', 'currency_code'])]);
    }

    public function regions(Request $request)
    {
        return response()->json(['data' => Region::query()
            ->where('is_active', true)
            ->when($request->filled('country_id'), fn ($query) => $query->where('country_id', $request->integer('country_id')))
            ->orderBy('name_ar')
            ->get(['id', 'country_id', 'name_ar', 'name_en'])]);
    }

    public function cities(Request $request)
    {
        return response()->json(['data' => City::query()
            ->where('is_active', true)
            ->when($request->filled('region_id'), fn ($query) => $query->where('region_id', $request->integer('region_id')))
            ->orderBy('name_ar')
            ->get(['id', 'region_id', 'name_ar', 'name_en'])]);
    }

    public function areas(Request $request)
    {
        return response()->json(['data' => Area::query()
            ->where('is_active', true)
            ->when($request->filled('city_id'), fn ($query) => $query->where('city_id', $request->integer('city_id')))
            ->orderBy('name_ar')
            ->get(['id', 'city_id', 'name_ar', 'name_en'])]);
    }

    /**
     * Data-driven currency catalogue consumed by the Flutter app dropdowns.
     */
    public function currencies()
    {
        $default = config('currencies.default', 'YER');
        $supported = collect(config('currencies.supported', []))
            ->map(fn (array $currency, string $code) => [
                'code' => $currency['code'] ?? $code,
                'name_ar' => $currency['name_ar'] ?? $code,
                'name_en' => $currency['name_en'] ?? $code,
                'symbol_ar' => $currency['symbol_ar'] ?? $code,
                'symbol_en' => $currency['symbol_en'] ?? $code,
                'flag' => $currency['flag'] ?? null,
                'decimals' => (int) ($currency['decimals'] ?? 0),
                'is_default' => ($currency['code'] ?? $code) === $default,
            ])
            ->values();

        return response()->json(['data' => $supported]);
    }
}
