<?php

namespace Rubot\Tools;


class Security
{
    private string $securit_key;
    private string $key = "key";

    private function __construct(string $securit_key, array $config = [])
    {
        $this->securit_key = $securit_key;
        $this->key = $config["key"] ?? "key";

        if (filter_var($config["log"] ?? false, FILTER_VALIDATE_BOOL)) {
            $this->log($config["log_file_name"] ?? ".request_log");
        }
    }

    public static function create(string $securit_key, array $config = []): Security
    {
        return new Security($securit_key, $config);
    }

    public function set(
        ?string $message = null,
        int $status_code = 403,
        ?string $header = null,
    ) {
        if (!$this->check()) {
            http_response_code($status_code);
            if ($header !== null) {
                header($header);
                exit;
            }
            die($message ?? "<h1>Page Not Found</h1>");
        }
    }

    public function check(?string $securit_key = null): bool
    {
        $value = $_GET[$this->key] ?? null;
        if (empty($value) || is_null($value)) {
            return false;
        }

        $securit_key = $securit_key ?? $this->securit_key;
        return hash_equals($securit_key, $value);
    }

    public function getInput(): ?array
    {
        $data = $_GET;
        return empty($data) ? null : $data;
    }

    public function log(string $file_name = ".request_log"): void
    {
        $data = [
            "data" => [
                "get" => $_GET,
                "post" => $_POST,
                "body" => json_decode(file_get_contents("php://input"), true),
            ],
            "headers" => getallheaders(),
            "server" => $_SERVER,
        ];

        $fh = fopen($file_name, "ab");
        if ($fh) {
            fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL);
            fclose($fh);
        }
    }
}
