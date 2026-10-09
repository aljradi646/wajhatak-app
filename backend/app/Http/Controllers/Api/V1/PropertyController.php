<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Models\PropertyLocation;
use App\Services\AI\AiIndexSyncService;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'property_type' => ['nullable', 'string', 'max:30'],
            'transaction_type' => ['nullable', 'in:sale,rent'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999', 'gte:min_price'],
            'min_area' => ['nullable', 'numeric', 'min:0'],
            'max_area' => ['nullable', 'numeric', 'min:0', 'gte:min_area'],
            'bedrooms' => ['nullable', 'integer', 'between:0,20'],
            'bedrooms_min' => ['nullable', 'integer', 'between:0,20'],
            'bedrooms_max' => ['nullable', 'integer', 'between:0,20', 'gte:bedrooms_min'],
            'bathrooms' => ['nullable', 'integer', 'between:0,20'],
            'bathrooms_min' => ['nullable', 'integer', 'between:0,20'],
            'bathrooms_max' => ['nullable', 'integer', 'between:0,20', 'gte:bathrooms_min'],
            'parking_spaces' => ['nullable', 'integer', 'between:0,50'],
            'parking_spaces_min' => ['nullable', 'integer', 'between:0,50'],
            'parking_spaces_max' => ['nullable', 'integer', 'between:0,50', 'gte:parking_spaces_min'],
            'is_furnished' => ['nullable', 'boolean'],
            'is_new' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:recommended,relevance,newest,oldest,price_asc,price_desc,area_asc,area_desc'],
        ]);

        $query = Property::query()
            ->with(['type', 'location.country', 'location.region', 'location.cityReference', 'location.area', 'agent.user', 'images'])
            ->where('status', PropertyStatus::Published);

        $this->applyFilters($query, $request);
        $this->applySort($query, $request->string('sort')->toString());

        $userId = $this->apiUser($request)?->id;
        if ($userId) {
            $query->withExists(['favorites as is_favorited' => fn (Builder $favorite) => $favorite->where('user_id', $userId)]);
        }

        return PropertyResource::collection($query->paginate(15)->withQueryString());
    }

    public function show(Request $request, Property $property): PropertyResource
    {
        $user = $this->apiUser($request);
        Gate::forUser($user)->authorize('view', $property);
        $property->load(['type', 'location.country', 'location.region', 'location.cityReference', 'location.area', 'agent.user', 'images', 'features']);
        if ($user) {
            $property->is_favorited = $property->favorites()->where('user_id', $user->id)->exists();
        }

        return new PropertyResource($property);
    }

    public function mine(Request $request)
    {
        $agent = $request->user()->agentProfile;
        abort_unless($agent, 403, 'هذه الخدمة مخصصة للوكلاء.');

        return PropertyResource::collection(Property::query()
            ->with(['type', 'location.country', 'location.region', 'location.cityReference', 'location.area', 'agent.user', 'images'])
            ->where('agent_id', $agent->id)
            ->latest()
            ->paginate(20));
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $agent = $request->user()->agentProfile;
        abort_unless($agent?->is_active, 403, 'حساب الوكيل غير مفعّل.');

        // بوابة التوثيق: لا إضافة عقارات إلا بعد موافقة الإدارة على حساب الوكيل.
        abort_unless($agent->isApproved(), 403, 'حسابك قيد المراجعة من الإدارة — سيتمكن من إضافة العقارات بعد توثيقه.');

        $property = DB::transaction(function () use ($request, $agent) {
            $location = PropertyLocation::query()->create($request->validated('location'));
            $attributes = $request->safe()->except(['location', 'feature_ids', 'images']);
            // Let the database default (YER) apply when no currency is supplied.
            if (array_key_exists('currency', $attributes) && blank($attributes['currency'])) {
                unset($attributes['currency']);
            }
            $property = Property::query()->create([
                ...$attributes,
                'agent_id' => $agent->id,
                'status' => PropertyStatus::Pending,
                'slug' => Str::slug($request->string('title')->toString()).'-'.Str::lower(Str::random(6)),
                'reference_code' => 'LUX-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'property_location_id' => $location->id,
            ]);
            $property->features()->sync($request->input('feature_ids', []));

            foreach ($request->file('images', []) as $index => $image) {
                $path = $image->storePublicly('properties/'.$property->id, 'public');
                $property->images()->create(['path' => $path, 'sort_order' => $index, 'is_cover' => $index === 0]);
            }

            return $property;
        });

        app(AiIndexSyncService::class)->sync($property->refresh());
        // Refresh so database defaults (e.g. currency = YER) are reflected in the response.
        return response()->json(['data' => new PropertyResource($property->refresh()->load(['type', 'location.country', 'location.region', 'location.cityReference', 'location.area', 'agent.user', 'images', 'features']))], 201);
    }

    public function uploadImage(Request $request, Property $property): JsonResponse
    {
        $this->authorize('update', $property);
        $data = $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'], 'alt_text' => ['nullable', 'string', 'max:255']]);
        $path = $data['image']->storePublicly('properties/'.$property->id, 'public');
        $image = $property->images()->create([
            'path' => $path,
            'alt_text' => $data['alt_text'] ?? null,
            'sort_order' => ((int) $property->images()->max('sort_order')) + 1,
            'is_cover' => ! $property->images()->exists(),
        ]);

        return response()->json(['data' => ['id' => $image->id, 'url' => asset('storage/'.$image->path)]], 201);
    }

    public function destroyImage(Request $request, Property $property, $imageId): JsonResponse
    {
        $this->authorize('update', $property);
        $image = $property->images()->findOrFail($imageId);
        Storage::disk('public')->delete($image->path);
        $wasCover = $image->is_cover;
        $image->delete();

        if ($wasCover) {
            $cover = $property->images()->orderBy('sort_order')->first();
            if ($cover) {
                $cover->update(['is_cover' => true]);
            }
        }

        return response()->json(status: 204);
    }

    public function setCover(Request $request, Property $property, $imageId): JsonResponse
    {
        $this->authorize('update', $property);
        $property->images()->where('is_cover', true)->update(['is_cover' => false]);
        $image = $property->images()->findOrFail($imageId);
        $image->update(['is_cover' => true]);

        return response()->json(status: 204);
    }

    public function update(UpdatePropertyRequest $request, Property $property): PropertyResource
    {
        $data = $request->validated();
        DB::transaction(function () use ($property, $data): void {
            if (array_key_exists('location', $data)) {
                $property->location->update($data['location']);
            }
            if (array_key_exists('feature_ids', $data)) {
                $property->features()->sync($data['feature_ids'] ?? []);
            }
            $property->update(collect($data)->except(['location', 'feature_ids'])->all());
        });

        app(AiIndexSyncService::class)->sync($property->fresh());
        return new PropertyResource($property->fresh()->load(['type', 'location.country', 'location.region', 'location.cityReference', 'location.area', 'agent.user', 'images', 'features']));
    }

    public function destroy(Request $request, Property $property): JsonResponse
    {
        $this->authorize('delete', $property);
        DB::transaction(function () use ($property): void {
            foreach ($property->images as $image) {
                Storage::disk('public')->delete($image->path);
            }
            $property->delete();
        });

        return response()->json(status: 204);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $query->when($request->filled('q'), fn (Builder $q) => $q->where(fn (Builder $search) => $search
            ->where('title', 'like', '%'.$request->string('q')->toString().'%')
            ->orWhere('description', 'like', '%'.$request->string('q')->toString().'%')))
            ->when($request->filled('city'), fn (Builder $q) => $q->whereHas('location', fn (Builder $location) => $location->where('city', $request->string('city')->toString())))
            ->when($request->filled('district'), fn (Builder $q) => $q->whereHas('location', fn (Builder $location) => $location->where('district', $request->string('district')->toString())))
            ->when($request->filled('neighborhood'), fn (Builder $q) => $q->whereHas('location', fn (Builder $location) => $location->where('neighborhood', $request->string('neighborhood')->toString())))
            ->when($request->filled('property_type'), fn (Builder $q) => $q->whereHas('type', fn (Builder $type) => $type->where('slug', $request->string('property_type')->toString())))
            ->when($request->filled('transaction_type'), fn (Builder $q) => $q->where('transaction_type', $request->string('transaction_type')->toString()))
            ->when($request->filled('min_price'), fn (Builder $q) => $q->where('price', '>=', $request->float('min_price')))
            ->when($request->filled('max_price'), fn (Builder $q) => $q->where('price', '<=', $request->float('max_price')))
            ->when($request->filled('min_area'), fn (Builder $q) => $q->where('area', '>=', $request->float('min_area')))
            ->when($request->filled('max_area'), fn (Builder $q) => $q->where('area', '<=', $request->float('max_area')))
            ->when($request->filled('bedrooms_min') || $request->filled('bedrooms'), fn (Builder $q) => $q->where('bedrooms', '>=', $request->filled('bedrooms_min') ? $request->integer('bedrooms_min') : $request->integer('bedrooms')))
            ->when($request->filled('bedrooms_max'), fn (Builder $q) => $q->where('bedrooms', '<=', $request->integer('bedrooms_max')))
            ->when($request->filled('bathrooms_min') || $request->filled('bathrooms'), fn (Builder $q) => $q->where('bathrooms', '>=', $request->filled('bathrooms_min') ? $request->integer('bathrooms_min') : $request->integer('bathrooms')))
            ->when($request->filled('bathrooms_max'), fn (Builder $q) => $q->where('bathrooms', '<=', $request->integer('bathrooms_max')))
            ->when($request->filled('parking_spaces_min') || $request->filled('parking_spaces'), fn (Builder $q) => $q->where('parking_spaces', '>=', $request->filled('parking_spaces_min') ? $request->integer('parking_spaces_min') : $request->integer('parking_spaces')))
            ->when($request->filled('parking_spaces_max'), fn (Builder $q) => $q->where('parking_spaces', '<=', $request->integer('parking_spaces_max')))
            ->when($request->has('is_furnished') && $request->input('is_furnished') !== null, fn (Builder $q) => $q->where('is_furnished', $request->boolean('is_furnished')))
            ->when($request->has('is_new') && $request->input('is_new') !== null, fn (Builder $q) => $q->where('is_new', $request->boolean('is_new')))
            ->when($request->has('is_featured') && $request->input('is_featured') !== null, fn (Builder $q) => $q->where('is_featured', $request->boolean('is_featured')));
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'newest' => $query->orderByDesc('published_at')->orderByDesc('id'),
            'oldest' => $query->orderBy('published_at')->orderBy('id'),
            'price_asc' => $query->orderBy('price')->orderByDesc('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('id'),
            'area_asc' => $query->orderBy('area')->orderByDesc('id'),
            'area_desc' => $query->orderByDesc('area')->orderByDesc('id'),
            default => $query->orderByDesc('is_featured')->orderByDesc('published_at')->orderByDesc('id'),
        };
    }

    private function apiUser(Request $request): ?User
    {
        return $request->user('sanctum') ?? $request->user();
    }
}
