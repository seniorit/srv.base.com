<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitServiceProvider extends ServiceProvider
{

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    $this->rateLimiter();
  }

  /**
   * Configure rate limiting for the application.
   *
   * This method defines a rate limiter for the 'api' named key,
   * allowing a maximum of 60 requests per minute. The rate limit
   * is applied based on the user's ID if authenticated, or the
   * client's IP address otherwise.
   */
  private function rateLimiter(): void
  {
    RateLimiter::for('api', function (Request $request) {
      return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });
  }
}
