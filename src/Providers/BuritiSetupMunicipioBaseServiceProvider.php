<?php

declare(strict_types=1);

namespace iEducar\Packages\BuritiSetupMunicipioBase\Providers;

use iEducar\Packages\BuritiSetupMunicipioBase\Console\Commands\IeducarSetupMunicipioBase;
use Illuminate\Support\ServiceProvider;

class BuritiSetupMunicipioBaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                IeducarSetupMunicipioBase::class,
            ]);
        }
    }
}
