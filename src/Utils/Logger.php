<?php

namespace Rubot\Utils;

class Logger
{
    private string $logFile;
    private bool $debug;

    public function __construct(string $logFile = __DIR__ . '/bot.log', bool $debug = false)
    {
        $this->logFile = $logFile;
        $this->debug = $debug;
    }


    public function info(string $message): void
    {
        $this->write("INFO", $message);
    }


    public function error(string $message): void
    {
        $this->write("ERROR", $message);
    }

    public function warning(string $message): void
    {
        $this->write("WARNING", $message);
    }

    private function write(string $level, string $message): void
    {
        if (!$this->debug)
            return;

        $date = date("Y-m-d H:i:s");
        $line = "[{$date}] [{$level}] {$message}\n\n";
        file_put_contents($this->logFile, $line, FILE_APPEND);
    }
}
