<?php

namespace saint\LaravelLogger\Providers;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use saint\LaravelLogger\SqlLogger;
use saint\LaravelLogger\CacheLogger;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // files to publish
        $this->publishes($this->getPublished());

        // get sql settings
        $logStatus = $this->getSqlLoggingStatus();
        $slowLogStatus = $this->getSlowSqlLoggingStatus();
        $slowLogTime = $this->getSlowSqlLoggingTime();
        $override = $this->getOverrideStatus();
        $directory = $this->getLogDirectory();
        $convertToSeconds = $this->getConvertToSeconds();
        $separateConsoleLog = $this->getSeparateConsoleLogs();

        // get cache settings
        $cacheLogStatus = $this->getCacheLoggingStatus();
        $cacheOverride = $this->getCacheOverrideStatus();
        $cacheDirectory = $this->getCacheLogDirectory();
        $cacheConvertToSeconds = $this->getCacheConvertToSeconds();
        $cacheSeparateConsoleLog = $this->getCacheSeparateConsoleLogs();


        if ($logStatus || $slowLogStatus) {
            $this->registerSqlEvent(
                $logStatus,
                $slowLogStatus,
                $slowLogTime,
                $override,
                $directory,
                $convertToSeconds,
                $separateConsoleLog
            );
        }

        if ($cacheLogStatus) {
            $this->registerCacheEvent(
                $cacheLogStatus,
                $cacheOverride,
                $cacheDirectory,
                $cacheConvertToSeconds,
                $cacheSeparateConsoleLog
            );
        }
    }

    protected function registerSqlEvent(
        $logStatus,
        $slowLogStatus,
        $slowLogTime,
        $override,
        $directory,
        $convertToSeconds,
        $separateConsoleLog
    ) {
        $logger = $this->app->make(SqlLogger::class,
            [
                $this->app,
                $logStatus,
                $slowLogStatus,
                $slowLogTime,
                $override,
                $directory,
                $convertToSeconds,
                $separateConsoleLog,
            ]);

        // listen to database queries
        $this->app['db']->listen(function (
            $query,
            $bindings = null,
            $time = null
        ) use ($logger) {
            $logger->log($query, $bindings, $time);
        });
    }

    protected function registerCacheEvent(
        $cacheLogStatus,
        $cacheOverride,
        $cacheDirectory,
        $cacheConvertToSeconds,
        $cacheSeparateConsoleLog
    ) {
        $logger = $this->app->make(CacheLogger::class,
            [
                $this->app,
                $cacheLogStatus,
                $cacheOverride,
                $cacheDirectory,
                $cacheConvertToSeconds,
                $cacheSeparateConsoleLog
            ]);

        $this->app['events']->listen(CacheHit::class, function ($payload) use ($logger) {
            $event = 'hit';
            $logger->log($event, $payload);
        });

        $this->app['events']->listen(CacheMissed::class, function ($payload) use ($logger) {
            $event = 'missed';
            $logger->log($event, $payload);
        });

        $this->app['events']->listen(KeyForgotten::class, function ($payload) use ($logger) {
            $event = 'delete';
            $logger->log($event, $payload);
        });

        $this->app['events']->listen(KeyWritten::class, function ($payload) use ($logger) {
            $event = 'write';
            $logger->log($event, $payload);
        });
    }

    protected function getPublished()
    {
        return [
            realpath(__DIR__ .
                '/../../config/sql_logger.php') =>
                (function_exists('config_path') ?
                    config_path('sql_logger.php') :
                    base_path('config/sql_logger.php')),
            realpath(__DIR__ .
                '/../../config/cache_logger.php') =>
                (function_exists('config_path') ?
                    config_path('cache_logger.php') :
                    base_path('config/cache_logger.php')),
        ];
    }


    protected function getSqlLoggingStatus()
    {
        return (bool)$this->app->config->get('sql_logger.log_queries',
            env('SQL_LOG_QUERIES', false));
    }

    protected function getCacheLoggingStatus()
    {
        return (bool)$this->app->config->get('cache_logger.log_queries',
            env('CACHE_LOG_QUERIES', false));
    }

    protected function getSlowSqlLoggingStatus()
    {
        return (bool)$this->app->config->get('sql_logger.log_slow_queries',
            env('SQL_LOG_SLOW_QUERIES', false));
    }

    protected function getSlowSqlLoggingTime()
    {
        return $this->app->config->get('sql_logger.slow_queries_min_exec_time',
            env('SQL_SLOW_QUERIES_MIN_EXEC_TIME', 100));
    }

    protected function getOverrideStatus()
    {
        return (bool)$this->app->config->get('sql_logger.override_log',
            env('SQL_LOG_OVERRIDE', false));
    }

    protected function getCacheOverrideStatus()
    {
        return (bool)$this->app->config->get('cache_logger.override_log',
            env('CACHE_LOG_OVERRIDE', false));
    }

    protected function getLogDirectory()
    {
        return $this->app->config->get('sql_logger.directory',
            storage_path(env('SQL_LOG_DIRECTORY',
                'logs' . DIRECTORY_SEPARATOR . 'sql')));
    }

    protected function getCacheLogDirectory()
    {
        return $this->app->config->get('cache_logger.directory',
            storage_path(env('CACHE_LOG_DIRECTORY',
                'logs' . DIRECTORY_SEPARATOR . 'cache')));
    }

    protected function getConvertToSeconds()
    {
        return (bool)$this->app->config->get('sql_logger.convert_to_seconds',
            env('SQL_CONVERT_TIME_TO_SECONDS', false));
    }

    protected function getCacheConvertToSeconds()
    {
        return (bool)$this->app->config->get('cache_logger.convert_to_seconds',
            env('CACHE_CONVERT_TIME_TO_SECONDS', false));
    }

    protected function getSeparateConsoleLogs()
    {
        return (bool)$this->app->config->get('sql_logger.log_console_to_separate_file',
            env('SQL_LOG_SEPARATE_ARTISAN', false));
    }

    protected function getCacheSeparateConsoleLogs()
    {
        return (bool)$this->app->config->get('cache_logger.log_console_to_separate_file',
            env('CACHE_LOG_SEPARATE_ARTISAN', false));
    }
}
