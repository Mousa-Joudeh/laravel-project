<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PageVisit;

class TrackPageVisit
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {  
         $response = $next($request);
        $routeName = $request->route()?->getName();
        $isHtmlResponse = str_contains(
            strtolower((string) $response->headers->get('Content-Type')),
            'text/html'
        );
        if (
            $request->isMethod('GET')
            && $response->getStatusCode() === 200
            && is_string($routeName)
            && $routeName !== ''
            && $isHtmlResponse
        ) {
            PageVisit::create([
                'route_name' => $routeName,
                'path' => '/' . ltrim($request->path(), '/'),
                'user_id' => $request->user()?->getAuthIdentifier(),
                'visited_at' => now(),
            ]);
        } 
   
    return $response;
      
    }
}
