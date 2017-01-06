<?php
/**
 * Created by PhpStorm.
 * User: saint
 * Date: 06/01/2017
 * Time: 11:24 AM
 */

return [
    'cache_log_queries' => env('CACHE_LOG_QUERIES', true),

    'cache_log_console_to_separate_file' => env('CACHE_LOG_SEPARATE_ARTISAN', false),

    'cache_override_log' => env('CACHE_LOG_OVERRIDE', false),

    'cache_directory' => storage_path(env('CACHE_LOG_DIRECTORY', 'logs/cache')),

    'cache_convert_to_seconds' => env('CACHE_CONVERT_TIME_TO_SECONDS', false),
];