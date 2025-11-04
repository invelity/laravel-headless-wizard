<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class WizardSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession()) {
            throw new \RuntimeException(__('Session middleware not configured. Add StartSession middleware before WizardSession.'));
        }

        $request->session()->start();

        return $next($request);
    }
}
