<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{

  public function boot(): void
  {
    require_once app_path('Utilities/AppHelper.php');
  }
}
