<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentResource;
use App\Http\Resources\PropertyResource;
use App\Models\Agent;
use App\Models\Property;

class AgentController extends Controller
{
    public function index()
    {
        return AgentResource::collection(Agent::query()->with('user')->where('is_active', true)->withCount('properties')->paginate(15));
    }

    public function show(Agent $agent)
    {
        abort_unless($agent->is_active, 404);
        $agent->load('user')->loadCount('properties');

        $properties = $this->publishedProperties($agent)->paginate($this->perPage());

        return response()->json(['data' => ['agent' => new AgentResource($agent), 'properties' => PropertyResource::collection($properties)->response()->getData(true)]]);
    }

    public function properties(Agent $agent)
    {
        abort_unless($agent->is_active, 404);

        return PropertyResource::collection($this->publishedProperties($agent)->paginate($this->perPage()));
    }

    private function publishedProperties(Agent $agent)
    {
        return Property::query()->with(['type', 'location', 'agent.user', 'images'])
            ->where('agent_id', $agent->id)->where('status', 'published')->latest('published_at');
    }

    private function perPage(): int
    {
        return min(max((int) request()->query('per_page', 24), 1), 100);
    }
}