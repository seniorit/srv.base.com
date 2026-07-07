<?php

namespace App\Providers;

use App\Http\HttpHandler\ResponseHttp;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    $this->app->singleton(ResponseHttp::class, function ($app) {
      return new ResponseHttp();
    });
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    Schema::defaultStringLength(255);
  }
}
