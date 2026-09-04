<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Property;
use App\Models\User;
use App\Models\ViewingRequest;
use Illuminate\Database\Eloquent\Builder;

class DashboardController extends Controller
{
    public function index()
    {
        $messagesLast7Days = Message::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return view('admin.dashboard', [
            'totalUsers' => User::query()->count(),
            'activeAgents' => Agent::query()->where('is_active', true)->count(),
            'publishedProperties' => Property::query()->where('status', 'published')->count(),
            'pendingProperties' => Property::query()->where('status', 'pending')->count(),
            'pendingViewingRequests' => ViewingRequest::query()->where('status', 'pending')->count(),
            'totalConversations' => Conversation::query()->count(),
            'totalMessages' => Message::query()->count(),
            'messagesLast7Days' => $messagesLast7Days,
            'propertiesByStatus' => [
                'draft' => Property::query()->where('status', 'draft')->count(),
                'pending' => Property::query()->where('status', 'pending')->count(),
                'published' => Property::query()->where('status', 'published')->count(),
                'rejected' => Property::query()->where('status', 'rejected')->count(),
                'archived' => Property::query()->where('status', 'archived')->count(),
            ],
            'latestProperties' => Property::query()
                ->with(['type', 'agent.user'])
                ->latest('id')
                ->limit(6)
                ->get(),
            'recentActivity' => ActivityLog::query()
                ->with('user')
                ->latest('id')
                ->limit(8)
                ->get(),
            'favoritesCount' => \App\Models\Favorite::query()->count(),
        ]);
    }
}
