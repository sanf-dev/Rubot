<?php

namespace RubCli;

use Rubot\Exception\BotException;
use Rubot\Utils\{MiniFun, Logger};
// --------- guzzle ---------
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Rubino
{
    private string $auth;
    private Client $client;
    public Logger $log;
    use MiniFun;

    public function __construct(?string $auth, bool $useAuthENV = false)
    {
        $this->loadEnvFile();

        $path = dirname(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[0]["file"] ?? "bot.log") . DIRECTORY_SEPARATOR . "bot.log";
        $this->log = new Logger($path, filter_var($_ENV["DEBUG"] ?? false, FILTER_VALIDATE_BOOL));

        $AUTH = $useAuthENV ? $_ENV["AUTH"] ?? null : $auth;
        if (is_null($AUTH) || empty($AUTH)) {
            $this->log->error("21|R_C - Auth cannot be empty.");
            throw new BotException("Auth cannot be empty.");
        }
        $this->auth = $AUTH;
        $this->client = new Client();
    }



    public function getPostByLink(
        string $link,
        $profile_id = ""
    ) {
        return $this->run("getPostByShareLink", [
            "share_string" => str_starts_with($link, "https://rubika.ir/post/") ? ltrim($link, "https://rubika.ir/post/") : $link,
            "profile_id" => $profile_id
        ]);
    }

    public function getProfileList(
        bool $equal = false,
        int $limit = 10,
        $sort = "FromMax"
    ) {
        return $this->run("getProfileList", [
            "equal" => $equal,
            "limit" => $limit,
            "sort" => $sort
        ]);
    }

    public function getMyProfileInfo(string $profile_id = "")
    {
        return self::run("getMyProfileInfo", ["profile_id" => $profile_id]);
    }

    public function getProfilesStories(string|null $profile_id = null, int $limit = 100)
    {
        return self::run("getProfilesStories", [
            "limit" => $limit,
            "profile_id" => $profile_id
        ]);
    }

    public function getMyProfilePosts(
        string $profile_id = null,
        string|null $max_id = null,
        bool $equal = false,
        int $limit = 51,
        string $sort = "FromMax"
    ) {
        $json = [
            "equal" => $equal,
            "limit" => $limit,
            "sort" => $sort,
            "profile_id" => $profile_id
        ];
        is_null($max_id) ?: $json["max_id"] = $max_id;
        return self::run("getMyProfilePosts", $json);
    }

    public function getUsernameInfo(string $username)
    {
        $json = [
            "username" => $username
        ];
        return self::run("isExistUsername", $json);
    }
    public function getBookmarkedPosts(
        string $profile_id = "",
        bool $equal = false,
        int $limit = 51,
        string $sort = "FromMax"
    ) {
        $json = [
            "equal" => $equal,
            "limit" => $limit,
            "sort" => $sort,
            "profile_id" => $profile_id
        ];
        return self::run("getBookmarkedPosts", $json);
    }
    public function getExplorePosts(
        string $profile_id = "",
        string|null $max_ix = null,
        string $sort = "FromMax",
        bool $equal = false,
        int $limit = 51
    ) {
        $json = [
            "equal" => $equal,
            "limit" => $limit,
            "sort" => $sort,
            "profile_id" => $profile_id
        ];
        is_null($max_ix) ?: $json["max_id"] = $max_ix;
        return self::run("getExplorePosts", $json);
    }

    private function post($data)
    {
        $url = 'https://rubino15.iranlms.ir/';
        $headers = [
            "Accept-Encoding" => "gzip",
            "Connection" => "Keep-Alive",
            "Content-Length" => strlen(trim(json_encode($data))),
            "Content-Type" => "application/json; charset=UTF-8",
            "Host" => parse_url($url, PHP_URL_HOST),
            "User-Agent" => "okhttp/3.12.12",
        ];

        try {
            $response = $this->client->post($url, [
                'headers' => $headers,
                'json' => $data
            ]);
            $result = json_decode($response->getBody(), true);
            return (isset($result["data"]) && isset($result["status"]) && $result["status"]) ? $result["data"] : $result;
        } catch (RequestException $e) {
            return [
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }
    private function run($method, $input)
    {
        $client = array(
            "app_name" => "Main",
            "app_version" => "3.8.1",
            "lang_code" => "fa",
            "package" => "app.rbmain.a",
            "platform" => "Android",
            "store" => "Direct",
            "temp_code" => "30"
        );

        $json_data = array(
            "api_version" => "0",
            "auth" => $this->auth,
            "client" => $client,
            "data" => $input,
            "method" => $method
        );

        return $this->post($json_data);
    }
    private function loadEnvFile()
    {
        $envPath = dirname(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["file"]) . DIRECTORY_SEPARATOR;
        $format = basename($this->Settings["env"] ?? ".env");

        if (file_exists($envPath . $format)) {
            $this->LoadENV([$envPath, $format]);
        }
        return false;
    }
}