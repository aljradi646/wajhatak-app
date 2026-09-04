<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = Favorite::query()->where('user_id', $request->user()->id)
            ->with(['property.type', 'property.location', 'property.agent.user', 'property.images'])
            ->latest()->paginate(15);

        return PropertyResource::collection($favorites->through(function (Favorite $favorite) {
            $favorite->property->is_favorited = true;
            return $favorite->property;
        }));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['property_id' => ['required', 'integer', 'exists:properties,id']]);
        $property = Property::query()->findOrFail($data['property_id']);
        abort_unless($property->status === PropertyStatus::Published, 404);
        Favorite::query()->firstOrCreate(['user_id' => $request->user()->id, 'property_id' => $property->id]);

        return response()->json(status: 204);
    }

    public function destroy(Request $request, Property $property): JsonResponse
    {
        Favorite::query()->where('user_id', $request->user()->id)->where('property_id', $property->id)->delete();

        return response()->json(status: 204);
    }
}
