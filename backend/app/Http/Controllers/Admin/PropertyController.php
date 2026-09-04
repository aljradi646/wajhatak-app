<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\Property;
use App\Models\PropertyFeature;
use App\Models\PropertyLocation;
use App\Models\PropertyType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::query()->with(['agent.user', 'type', 'location'])
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('transaction'), fn ($q, $t) => $q->where('transaction_type', $t));

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('reference_code', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereHas('agent.user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $allowed = ['title', 'price', 'area', 'bedrooms', 'status', 'created_at', 'published_at'];
        if (in_array($sort, $allowed, true)) {
            $query->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        return view('admin.properties.index', [
            'properties' => $query->withCount('images')->paginate(15)->withQueryString(),
            'search' => $search,
            'status' => $request->input('status'),
        ]);
    }

    public function trash(Request $request)
    {
        $query = Property::query()->onlyTrashed()->with(['agent.user']);
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")->orWhere('reference_code', 'like', "%{$search}%");
            });
        }

        return view('admin.properties.trash', [
            'properties' => $query->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('admin.properties.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $isFurnished = $request->boolean('is_furnished');
        $isNew = $request->boolean('is_new');
        $isFeatured = $request->boolean('is_featured');

        $property = DB::transaction(function () use ($data, $isFurnished, $isNew, $isFeatured) {
            $property = Property::create([
                'agent_id' => $data['agent_id'],
                'property_type_id' => $data['property_type_id'],
                'property_location_id' => $data['property_location_id'],
                'title' => $data['title'],
                'slug' => $data['slug'],
                'reference_code' => $data['reference_code'],
                'description' => $data['description'],
                'transaction_type' => $data['transaction_type'],
                'status' => $data['status'],
                'price' => $data['price'],
                'currency' => $data['currency'] ?? 'SAR',
                'area' => $data['area'] ?? null,
                'bedrooms' => $data['bedrooms'] ?? null,
                'bathrooms' => $data['bathrooms'] ?? null,
                'parking_spaces' => $data['parking_spaces'] ?? null,
                'is_furnished' => $isFurnished,
                'is_new' => $isNew,
                'is_featured' => $isFeatured,
                'published_at' => $data['published_at'] ?? null,
            ]);
            if (! empty($data['features'])) {
                $property->features()->sync($data['features']);
            }
            return $property;
        });

        ActivityLog::record('property', "تم إنشاء العقار «{$property->title}» ({$property->reference_code})", $property);

        return redirect()->route('admin.properties.index')->with('status', 'تم إنشاء العقار بنجاح.');
    }

    public function show(Property $property)
    {
        $property->load(['agent.user', 'type', 'location', 'images', 'features']);
        return view('admin.properties.show', compact('property'));
    }

    public function edit(Property $property)
    {
        $property->load('features');
        return view('admin.properties.edit', array_merge($this->formData(), ['property' => $property]));
    }

    public function update(Request $request, Property $property)
    {
        $data = $this->validateData($request, $property);

        $property->fill([
            'agent_id' => $data['agent_id'],
            'property_type_id' => $data['property_type_id'],
            'property_location_id' => $data['property_location_id'],
            'title' => $data['title'],
            'slug' => $data['slug'],
            'reference_code' => $data['reference_code'],
            'description' => $data['description'],
            'transaction_type' => $data['transaction_type'],
            'status' => $data['status'],
            'price' => $data['price'],
            'currency' => $data['currency'] ?? 'SAR',
            'area' => $data['area'] ?? null,
            'bedrooms' => $data['bedrooms'] ?? null,
            'bathrooms' => $data['bathrooms'] ?? null,
            'parking_spaces' => $data['parking_spaces'] ?? null,
            'is_furnished' => $request->boolean('is_furnished'),
            'is_new' => $request->boolean('is_new'),
            'is_featured' => $request->boolean('is_featured'),
            'published_at' => $data['published_at'] ?? null,
        ]);
        $property->save();

        if (array_key_exists('features', $data)) {
            $property->features()->sync($data['features'] ?? []);
        }

        ActivityLog::record('property', "تم تحديث العقار «{$property->title}» ({$property->reference_code})", $property);

        return redirect()->route('admin.properties.index')->with('status', 'تم تحديث العقار بنجاح.');
    }

    public function destroy(Property $property)
    {
        $property->delete();
        ActivityLog::record('property', "تم نقل العقار «{$property->title}» إلى سلة المحذوفات", $property);
        return redirect()->route('admin.properties.index')->with('status', 'تم نقل العقار إلى سلة المحذوفات.');
    }

    public function restore(int $id)
    {
        $property = Property::withTrashed()->findOrFail($id);
        $property->restore();
        ActivityLog::record('property', "تمت استعادة العقار «{$property->title}»", $property);
        return redirect()->route('admin.properties.trash')->with('status', 'تمت استعادة العقار بنجاح.');
    }

    public function forceDelete(int $id)
    {
        $property = Property::withTrashed()->findOrFail($id);
        ActivityLog::record('property', "تم حذف العقار «{$property->title}» نهائيًا", $property);
        $property->forceDelete();
        return redirect()->route('admin.properties.trash')->with('status', 'تم حذف العقار نهائيًا.');
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'action' => ['required', 'in:delete,restore,force'],
        ]);

        $ids = array_filter($data['ids']);
        if (empty($ids)) {
            return back()->with('error', 'لم يتم تحديد أي عناصر.');
        }

        $properties = Property::withTrashed()->whereIn('id', $ids)->get();

        if ($data['action'] === 'delete') {
            $properties->each->delete();
            $msg = 'تم حذف العقارات المحددة.';
        } elseif ($data['action'] === 'restore') {
            $properties->each->restore();
            $msg = 'تمت استعادة العقارات المحددة.';
        } else {
            $properties->each->forceDelete();
            $msg = 'تم حذف العقارات المحددة نهائيًا.';
        }

        ActivityLog::record('property', "{$msg} ({$properties->count()} عنصر)", null, properties: ['action' => $data['action'], 'ids' => $properties->pluck('id')->all()]);

        return back()->with('status', $msg);
    }

    private function validateData(Request $request, ?Property $property = null): array
    {
        $unique = $property ? ','.$property->id : '';

        return $request->validate([
            'agent_id' => ['required', 'exists:agents,id'],
            'property_type_id' => ['required', 'exists:property_types,id'],
            'property_location_id' => ['required', 'exists:property_locations,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:properties,slug'.$unique],
            'reference_code' => ['required', 'string', 'max:191', 'unique:properties,reference_code'.$unique],
            'description' => ['required', 'string'],
            'transaction_type' => ['required', 'in:sale,rent'],
            'status' => ['required', 'in:draft,pending,published,rejected,archived'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'parking_spaces' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
            'features' => ['nullable', 'array'],
            'features.*' => ['integer'],
        ]);
    }

    private function formData(): array
    {
        return [
            'agents' => Agent::query()->with('user')->where('is_active', true)->get(),
            'types' => PropertyType::query()->where('is_active', true)->get(),
            'locations' => PropertyLocation::query()->orderBy('city')->get(),
            'features' => PropertyFeature::query()->where('is_active', true)->get(),
            'statuses' => PropertyStatus::cases(),
            'transactions' => TransactionType::cases(),
        ];
    }
}
