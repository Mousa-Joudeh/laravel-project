<?php

namespace App\Http\Controllers;

use App\Models\PageVisit;
use Illuminate\View\View;


class PageVisitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
{
    if (auth()->user()?->role !== 'admin') {
        abort(403);
    }

    $pageVisits = PageVisit::query()
        ->select('route_name')
        ->selectRaw('COUNT(*) AS visits_count')
        ->groupBy('route_name')
        ->orderByDesc('visits_count')
        ->orderBy('route_name')
        ->paginate(15);

    return view('page-visits.index', compact('pageVisits'));
}

public function show(PageVisit $pageVisit): View
{
    if (auth()->user()?->role !== 'admin') {
        abort(403);
    }

    $pageVisit->load('user');

    return view('page-visits.show', compact('pageVisit'));
}
}

