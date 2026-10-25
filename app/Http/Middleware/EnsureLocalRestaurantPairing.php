<?php

namespace App\Http\Middleware;

use App\Support\LocalRestaurantPairing;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLocalRestaurantPairing
{
    public function __construct(private readonly LocalRestaurantPairing $pairing) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->pairing->isEnabled()) {
            return $next($request);
        }

        $slug = $request->route('slug');
        abort_unless(is_string($slug), 404);
        $this->pairing->restaurantForSlug($slug);

        return $next($request);
    }
}
