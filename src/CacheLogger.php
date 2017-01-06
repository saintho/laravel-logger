<?php
/**
 * Created by PhpStorm.
 * User: saint
 * Date: 06/01/2017
 * Time: 11:26 AM
 */

namespace saint\LaravelLogger;

class CacheLogger
{
    protected $version;

    protected $logStatus;

    protected $override;

    protected $directory;

    protected $convertToSeconds;

    protected $separateConsoleLog;

    public function __construct(
        $app,
        $cacheLogStatus,
        $cacheOverride,
        $cacheDirectory,
        $cacheConvertToSeconds,
        $cacheSeparateConsoleLog
    ) {
        $this->app = $app;
        $this->logStatus = $cacheLogStatus;
        $this->override = $cacheOverride;
        $this->directory = rtrim($cacheDirectory, '\\/');
        $this->convertToSeconds = $cacheConvertToSeconds;
        $this->separateConsoleLog = $cacheSeparateConsoleLog;
    }


    public function log($event, $payload)
    {
        static $queryNr = 0;
        ++$queryNr;

        try {
            list($cacheQuery) =
                $this->getCacheQuery($event, $payload);
        } catch (\Exception $e) {
            $this->app->log->notice("cache log has fails");
            return;
        }

        $logData = $this->getLogData($queryNr, $cacheQuery);

        $this->save($logData, $queryNr);
    }

    private function getCacheQuery($event, $payload)
    {
        $key = $tags = $value = $minutes = '';
        foreach ($payload as $pKey => $pValue) {
            $$pKey = $pValue;
        }

        $cacheQuery = 'cache action: ' . $event . "\n";
        $cacheQuery .= 'key:' .$key . "\n";
        !empty($value) && $cacheQuery .= 'value:' . json_encode($value) . "\n";
        !empty($tags) && $cacheQuery .= 'tags:' . json_encode($tags) . "\n";
        !empty($minutes) && $cacheQuery .= 'cache time:' . json_encode($minutes);

        return [$cacheQuery];
    }

    private function save($data, $queryNr)
    {
        $filePrefix = ($this->separateConsoleLog &&
            $this->app->runningInConsole()) ? '-artisan' : '';

        // save normal query to file if enabled
        if ($this->logStatus) {
            $this->saveLog($data, date('Y-m-d') . $filePrefix . '-log.log',
                ($queryNr == 1 && (bool)$this->override));
        }
    }

    protected function saveLog($data, $fileName, $override = false)
    {
        file_put_contents($this->directory . DIRECTORY_SEPARATOR . $fileName,
            $data, $override ? 0 : FILE_APPEND);
    }

    protected function getLogData($queryNr, $query)
    {
        return '/* Query ' . $queryNr . ' - ' . date('Y-m-d H:i:s') . "  */\n" . $query .
        "\n/*==================================================*/\n";
    }
}