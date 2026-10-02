<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeSessionObjects
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach ($request->session()->all() as $key => $value) {
            if ($this->containsUnsafeObject($value)) {
                $request->session()->forget($key);
            }
        }

        return $next($request);
    }

    private function containsUnsafeObject(mixed $value): bool
    {
        if (is_object($value)) {
            if (get_class($value) === '__PHP_Incomplete_Class'
                || $value instanceof Model
                || $value instanceof EloquentCollection) {
                return true;
            }

            return false;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsUnsafeObject($item)) {
                    return true;
                }
            }
        }

        return false;
    }
}
