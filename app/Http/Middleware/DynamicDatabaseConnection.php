<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DynamicDatabaseConnection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('db_host')) {
            $default = config('database.default');
            $config = [
                "database.connections.{$default}.host" => $request->session()->get('db_host'),
                "database.connections.{$default}.port" => $request->session()->get('db_port'),
                "database.connections.{$default}.database" => $request->session()->get('db_database'),
                "database.connections.{$default}.username" => $request->session()->get('db_username'),
                "database.connections.{$default}.password" => $request->session()->get('db_password'),
                "database.connections.{$default}.charset" => 'utf8mb4',
                "database.connections.{$default}.collation" => 'utf8mb4_unicode_ci',
            ];

            config($config);

            // Purge the connection to ensure it's re-established with new config
            DB::purge($default);
        }

        return $next($request);
    }
}
