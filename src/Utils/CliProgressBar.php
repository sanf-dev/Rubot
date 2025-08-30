<?php

namespace Rubot\Utils;

class CliProgressBar
{
    private int $width;
    private float $startTime;
    private int $lastPercent = -1;

    public function __construct(int $width = 40)
    {
        $this->width = $width;
        $this->startTime = microtime(true);
    }

    public function setProgress(int $percent, int $uploaded = 0, int $total = 0): void
    {
        $percent = max(0, min(100, $percent));

        if ($percent === $this->lastPercent && $percent < 100) {
            return;
        }
        $this->lastPercent = $percent;

        $filled = (int) floor(($percent / 100) * $this->width);
        $bar = str_repeat("█", $filled) . str_repeat("░", $this->width - $filled);

        $elapsed = microtime(true) - $this->startTime;
        $speed = $elapsed > 0 ? $uploaded / $elapsed : 0;
        $eta = ($speed > 0 && $total > 0) ? ($total - $uploaded) / $speed : 0;

        $line = sprintf(
            "\r[%s] %6.2f%%  %s / %s  %s/s  ETA %s",
            $bar,
            $percent,
            $this->humanBytes($uploaded),
            $total > 0 ? $this->humanBytes($total) : '?',
            $this->humanBytes((int) $speed),
            $this->humanTime((int) $eta)
        );

        echo $line;

        if ($percent >= 100) {
            echo PHP_EOL;
        }

        flush();
    }

    private function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $v = (float) $bytes;
        while ($v >= 1024 && $i < count($units) - 1) {
            $v /= 1024;
            $i++;
        }
        return sprintf(($i === 0 ? '%.0f %s' : '%.2f %s'), $v, $units[$i]);
    }

    private function humanTime(int $sec): string
    {
        if ($sec >= 3600)
            return sprintf('%02d:%02d:%02d', intdiv($sec, 3600), intdiv($sec % 3600, 60), $sec % 60);
        return sprintf('%02d:%02d', intdiv($sec, 60), $sec % 60);
    }
}
