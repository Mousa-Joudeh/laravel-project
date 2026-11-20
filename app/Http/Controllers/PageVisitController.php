<?php

namespace App\Http\Controllers;

use App\Models\PageVisit;
use Illuminate\Http\Request;

class PageVisitController extends Controller
{

    public function index(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $query = PageVisit::with('user')->latest('visited_at');

        if ($request->filled('route_name')) {
            $query->where('route_name', $request->route_name);
        }

        $visits = $query->paginate(15);

        return view('admin.page-visits.index', compact('visits'));
    }

    public function show(PageVisit $pageVisit)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $visit = PageVisit::with('user')->findOrFail($pageVisit->id);

        return view('admin.page-visits.show', compact('visit'));
    }

    public function destroy(PageVisit $pageVisit)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $pageVisit = PageVisit::findOrFail($pageVisit->id);

        $pageVisit->delete();

        return redirect()->route('page-visits.index')
            ->with('success', 'Page visit deleted successfully!');
    }
}