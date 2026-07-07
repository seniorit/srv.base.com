<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppendContentType
{

  public function handle(Request $request, Closure $next): Response
  {
    if (($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH'))
      && !$request->headers->has('Content-Type')
    ) {
      $request->headers->set('Content-Type', 'application/json');
    }
    return $next($request);
  }
}
