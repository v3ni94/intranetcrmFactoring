<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            TenantContext::set($request->user()->tenant_id);
        }

        return $next($request);
    }
}
