<?php

namespace Cyd622\LaravelApi\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class DataBaseListenMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next)
    {
        $logMaxFiles = config('laravel_api.middleware.database_listen.log_max_files', 30);
        $logFile = storage_path('logs/database/sql.log');
        if (version_compare(App::version(), '5.6.0', '>=')) {
            // 5.6以上
            if (!config('logging.channels.database')) {
                $config = [
                    'driver' => 'daily',
                    'path' => $logFile,
                    'days' => $logMaxFiles
                ];
                config(['logging.channels.database' => $config]);
            }

            $logger = Log::channel('database');

        } else {
            // 低于5.6版本手动分割日志
            $logger = new Logger(App::environment());
            $logger->pushHandler(new RotatingFileHandler($logFile, $logMaxFiles));
        }

        DB::listen(function (QueryExecuted $query) use ($logger, $request) {
            $listenType = config('laravel_api.middleware.database_listen.listen_type', [
                'select',
                'update',
                'delete',
                'insert',
            ]);

            if (Str::startsWith($query->sql, $listenType)) {
                $sqlWithPlaceholders = str_replace(['%', '?'], ['%%', '%s'], $query->sql);
                $bindings = $query->connection->prepareBindings($query->bindings);
                $pdo = $query->connection->getPdo();
                $realSql = vsprintf($sqlWithPlaceholders, array_map([$pdo, 'quote'], $bindings));
                $duration = $this->formatDuration($query->time / 1000);
                $logger->debug(sprintf('[%s] %s | %s: %s', $duration, $realSql, $request->method(), $request->getRequestUri()));
            }
        });

        return $next($request);
    }

    /**
     * Format duration.
     *
     * @param float $seconds
     *
     * @return string
     */
    private function formatDuration($seconds)
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 2) . 'ms';
        }
        return round($seconds, 2) . 's';
    }
}