<?php

namespace App\Http\Middleware;

use App\Models\Restaurant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveRestaurantHost
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower(rtrim($request->getHost(), '.'));
        $domain = strtolower(trim((string) config('app.domain'), '.'));

        if ($domain === '' || $host === $domain || ! str_ends_with($host, '.'.$domain)) {
            return $next($request);
        }

        $subdomain = substr($host, 0, -strlen('.'.$domain));
        abort_unless(
            preg_match('/\A[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\z/', $subdomain) === 1,
            404,
        );

        $restaurants = Restaurant::query()
            ->whereRaw('LOWER(subdomain) = ?', [$subdomain])
            ->limit(2)
            ->get();

        abort_unless($restaurants->count() === 1 && $restaurants->first()->is_active, 404);

        $request->attributes->set('restaurant', $restaurants->first());

        return $next($request);
    }
}
