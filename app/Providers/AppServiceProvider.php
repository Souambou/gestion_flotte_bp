<?php

namespace App\Providers;

use App\Models\Parametre;
use App\Services\ServiceGoogleMaps;
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ServiceGoogleMaps::class);
    }

    public function boot(): void
    {
        // Noms de jours et de mois en francais (calendrier, diffForHumans).
        Carbon::setLocale('fr');
        setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'fr');

        // Format d'affichage francais par defaut dans les vues.
        Blade::directive('dateFr', fn ($expression) => "<?php echo ($expression)?->format('d/m/Y') ?? '—'; ?>");
        Blade::directive('dateHeureFr', fn ($expression) => "<?php echo ($expression)?->format('d/m/Y H:i') ?? '—'; ?>");
        Blade::directive('fcfa', fn ($expression) => "<?php echo number_format((float)($expression), 0, ',', ' ').' FCFA'; ?>");

        // La cle Google Maps saisie dans l'interface est disponible dans toutes les vues.
        View::composer('*', function ($view) {
            if (Schema::hasTable('parametres')) {
                $view->with('cleGoogleMapsGlobale', Parametre::valeur('google_maps_api_key', config('services.google_maps.key')));
            }
        });
    }
}
