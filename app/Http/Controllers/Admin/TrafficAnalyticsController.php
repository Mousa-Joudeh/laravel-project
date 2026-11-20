<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageVisit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TrafficAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $days = (int) $request->query('period', 30);
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;
        $start = now()->startOfDay()->subDays($days - 1);
        $end = now()->addDay()->startOfDay();
        $previousStart = $start->copy()->subDays($days);
        $search = trim((string) $request->query('search', ''));
        $visitor = (string) $request->query('visitor', '');
        $sort = (string) $request->query('sort', 'visited_at');
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($sort, ['visited_at', 'path', 'route_name'], true) ? $sort : 'visited_at';
        $perPage = in_array((int) $request->query('per_page', 5), [5, 10, 25, 50], true)
            ? (int) $request->query('per_page', 5) : 5;

        $periodQuery = fn ($from, $to) => PageVisit::query()
            ->where('visited_at', '>=', $from)->where('visited_at', '<', $to)
            ->where('route_name', 'not like', 'admin.%')
            ->where('path', 'not like', '/admin%')
            ->where('path', 'not like', 'admin/%');
        $total = $periodQuery($start, $end)->count();
        $previous = $periodQuery($previousStart, $start)->count();
        $loggedIn = $periodQuery($start, $end)->whereNotNull('user_id')->count();
        $guests = $total - $loggedIn;
        $knownUsers = $periodQuery($start, $end)->whereNotNull('user_id')->distinct()->count('user_id');
        $popular = $periodQuery($start, $end)->selectRaw('path, COUNT(*) AS views')
            ->groupBy('path')->orderByDesc('views')->limit(6)->get();
        $topMax = max(1, (int) ($popular->max('views') ?? 0));
        $daily = $periodQuery($start, $end)->selectRaw('DATE(visited_at) AS day, COUNT(*) AS views')
            ->groupBy('day')->pluck('views', 'day');
        $trend = collect(range(0, $days - 1))->map(function ($offset) use ($start, $daily) {
            $date = $start->copy()->addDays($offset);
            return ['label' => $date->format('d M'), 'value' => (int) ($daily[$date->format('Y-m-d')] ?? 0)];
        });
        $maxTrend = max(1, (int) $trend->max('value'));
        $query = $periodQuery($start, $end)->with('user:id,name,email');
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('path', 'like', "%{$search}%")
                ->orWhere('route_name', 'like', "%{$search}%"));
        }
        if ($visitor === 'guest') {
            $query->whereNull('user_id');
        } elseif ($visitor === 'member') {
            $query->whereNotNull('user_id');
        }
        $visits = $query->orderBy($sort, $direction)->orderBy('id', 'desc')
            ->paginate($perPage)->withQueryString();

        return view('admin.analytics.index', compact(
            'days', 'search', 'visitor', 'sort', 'direction', 'visits', 'total', 'previous',
            'loggedIn', 'guests', 'knownUsers', 'popular', 'topMax', 'trend', 'maxTrend'
        ));
    }
}
