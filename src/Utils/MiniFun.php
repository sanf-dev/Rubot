<?php

namespace Rubot\Utils;

use Dotenv\Dotenv;
use Exception;

trait MiniFun
{
    protected function LoadENV(array $path)
    {
        $env = Dotenv::createImmutable(...$path);
        $env->load();
    }
    private function setEnvValue(string $key, $value, ?string $path = null): bool
    {
        $path = $path ?? (base64_decode($this->Settings["env"]) ?: ".env");

        if (!file_exists($path)) {
            touch($path);
        }

        $content = file_get_contents($path);
        $key = trim($key);

        if (str_starts_with($key, "#")) {
            $commentLine = $key . " " . trim((string) $value);
            $content = rtrim($content, "\r\n") . PHP_EOL . $commentLine . PHP_EOL;
            return (bool) file_put_contents($path, $content);
        }

        if (is_bool($value)) {
            $value = $value ? "TRUE" : "FALSE";
        } elseif (is_null($value)) {
            $value = "";
        } else {
            $value = $this->escapeEnvValue($value);
        }

        $pattern = "/^" . preg_quote($key, "/") . "=.*/m";

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, "{$key}={$value}", $content);
        } else {
            $content = rtrim($content, "\r\n") . PHP_EOL . "{$key}={$value}" . PHP_EOL;
        }

        return (bool) file_put_contents($path, $content);
    }

    private function escapeEnvValue(string $value): string
    {
        return preg_match('/\s/', $value) ? '"' . addslashes($value) . '"' : $value;
    }
}

use Rubot\Exception\BotException;
use GuzzleHttp\Exception\RequestException;

trait Config
{
    public function post(string $method, array $input = []): array
    {
        $url = self::BASE_URL . $this->TOKEN . "/" . $method;

        $retryDelay = (int) ($this->Settings["DELAY"] ?? 2);
        $maxRetries = (int) ($this->Settings["RETRIES"] ?? 3);
        $timeout = (int) ($this->Settings["TIME_OUT"] ?? 10);

        // if ($method !== "getUpdates")
        //     $this->log->info("71|CP - POST URL : $url\nDATA: " . json_encode($input, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $retryCount = 0;
        $lastError = null;

        while ($retryCount <= $maxRetries) {
            try {
                $response = $this->request->post($url, [
                    "headers" => [
                        "Content-Type" => "application/json",
                    ],
                    "json" => $input ?? [],
                    "timeout" => $timeout,
                ]);

                $statusCode = $response->getStatusCode();
                $rawData = $response->getBody()->getContents();

                if ($method !== "getUpdates")
                    $this->log->info("86|CP - METHOD: $method | SERVER RESPONSE : $rawData");

                $result = json_decode($rawData, true);
                if (!is_array($result)) {
                    @http_response_code(500);
                    $this->log->warning("91|CP - SERVER STATUS CODE : $statusCode");
                    throw new BotException("Response is not valid JSON", $statusCode);
                }
                if (($result["status"] ?? "ERROR") === "OK") {
                    @http_response_code(200);
                    return $result["data"] ?? $result;
                }
                return $result;

            } catch (RequestException $e) {
                $lastError = $e->getMessage();
                $this->log->error("103|CPRE - {$e->getMessage()}");
            } catch (BotException $e) {
                $lastError = $e->getMessage();
                $this->log->error("106|CPBE - {$e->getMessage()}");
            } catch (Exception $e) {
                $lastError = $e->getMessage();
                $this->log->error("109|CPE - {$e->getMessage()}");
            }
            echo PHP_EOL . "Connection attempts: $retryCount" . PHP_EOL;

            $retryCount++;
            if ($retryCount <= $maxRetries) {
                sleep($retryDelay);
                $retryDelay *= 2;
            }
        }
        throw new BotException("Request failed after $maxRetries retries. Last error: {$lastError}");
    }
}