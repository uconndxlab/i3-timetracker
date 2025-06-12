<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use phpCAS;

class CasServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('cas', function (Application $app) {
            $config = $app->make('config')->get('cas');

            if (empty($config['hostname'])) {
                throw new \RuntimeException('CAS hostname is not configured.');
            }

            phpCAS::client(
                CAS_VERSION_2_0,
                $config['hostname'],
                $config['port'],
                $config['uri'],
                true
            );

            if (!empty($config['ca_cert_path'])) {
                if (!file_exists($config['ca_cert_path']) || !is_readable($config['ca_cert_path'])) {
                    throw new \RuntimeException('CAS CA certificate path is invalid or not readable: ' . $config['ca_cert_path']);
                }
                phpCAS::setCasServerCACert($config['ca_cert_path']);
            } else {
                if ($app->environment() !== 'production') {
                    phpCAS::setNoCasServerValidation();
                } else {
                    throw new \RuntimeException('CAS CA certificate path must be set in production environment.');
                }
            }

            // Debugging
            if ($config['debug'] === true) {
                phpCAS::setDebug($config['debug_log_path']);
            }
            return phpCAS::class;
        });
    }
}