<?php
namespace App\Providers;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;
class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    public function register(): void
    {
        // Telescope::night();
        $this->hideSensitiveRequestDetails();
        $isLocal = $this->app->environment('local');
        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            // Jangan rekam request webhook MQTT/EMQX (sangat frequent, membebani DB)
            if ($entry->isRequest()) {
                $uri = $entry->content['uri'] ?? '';
                if (str_contains($uri, 'mqtt') ||
                    str_contains($uri, 'ws-bridge') ||
                    str_contains($uri, 'heartbeat') ||
                    str_contains($uri, 'camera/upload')) {
                    return false;
                }
            }
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }
        Telescope::hideRequestParameters(['_token']);
        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }
    protected function gate(): void
    {
        Gate::define('viewTelescope', function (User $user) {
            return in_array($user->email, [
                //
            ]);
        });
    }
}
