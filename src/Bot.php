<?php

namespace Rubot;

use Rubot\Exception\BotException;
use Rubot\Tools\Message;
use Rubot\Utils\{
    MiniFun,
    Config,
    Logger,
    Markdown,
    InsConverter,
    DelConverter
};
use Rubot\Enums\{
    KeypadType,
    ParseMode
};


use GuzzleHttp\Client;
use GuzzleHttp\Client as request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Psr7\MultipartStream;

use League\HTMLToMarkdown\HtmlConverter;

class Bot
{
    private const BASE_URL = "https://botapi.rubika.ir/v3/";
    private const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB
    private const ALLOWED_MEDIA_TYPES = ["File", "Image", "Voice", "Music", "Video", "Gif"];
    private string $TOKEN;
    private request $request;
    private Markdown $markdown;
    private array $Settings = [];
    public Logger $log;
    public ?ParseMode $parseMode = null;

    public array $H2MConfig = ["strip_tags" => true, "hard_break" => true, "preserve_comments" => true];

    use MiniFun;
    use Config;

    public function __construct(string $token, ?ParseMode $setParseMode = null)
    {
        $this->TOKEN = $token;
        $this->request = new request();
        $this->markdown = new Markdown();
        $this->LoadENV($this->loadEnvFile());
        $this->loadSettings();
        $this->parseMode = $setParseMode;

        $path = dirname(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[0]["file"] ?? "bot.log") . DIRECTORY_SEPARATOR . "bot.log";
        $this->log = new Logger($path, filter_var($_ENV["DEBUG"] ?? false, FILTER_VALIDATE_BOOL));

        if (empty($this->TOKEN) || is_null($this->TOKEN)) {
            $this->log->error("49|B_C - Token cannot be empty.");
            throw new BotException("Token cannot be empty.");
        }
    }

    /**
     * get bot and settings
     * @return string
     */
    public function __tostring()
    {
        try {
            $result = $this->getMe();

            if (!isset($result["bot"]) || !is_array($result["bot"])) {
                return "Error: Unable to retrieve bot info.";
            }

            $bot = $result["bot"];
            $bot_info = "\nTOKEN: " . ($this->TOKEN ?? "N/A") . "\n"
                . "username: " . ($bot["username"] ?? "N/A") . "\n"
                . "bot id: " . ($bot["bot_id"] ?? "N/A") . "\n"
                . "bot title: " . ($bot["bot_title"] ?? "N/A") . "\n"
                . "share url: " . ($bot["share_url"] ?? "N/A") . "\n"
                . "description: " . ($bot["description"] ?? "N/A") . "\n"
                . "start message: " . ($bot["start_message"] ?? "N/A");

            return $bot_info;
        } catch (BotException $e) {
            $this->log->error("80|B_T - Error:  {$e->getMessage()}");
            return "Error: " . $e->getMessage();
        }
    }


    public function onMessage(callable ...$handlers)
    {
        static $update = null;
        if ($update === null) {
            $input = file_get_contents("php://input");
            $this->log->info("WEBHOOK INPUT : {$input}");
            if ($input === false)
                return [];
            $update = json_decode($input, true);
        }
        if (!is_array($update))
            return [];
        $message = new Message($update, $this);
        foreach ($handlers as $handler) {
            $handler($message);
        }
    }


    public function onUpdate(array $config = [], callable ...$handlers): never
    {
        $limit = $config["limit"] ?? 100;
        $timeout = $config["timeout"] ?? 1;
        $offset_id = $config["offset_id"] ?? "";
        $seconds = $config["seconds"] ?? 5;

        while (true) {
            $data = $this->getUpdates($limit, $offset_id);

            if (isset($data["next_offset_id"])) {
                $offset_id = $data["next_offset_id"];
            } else {
                $this->jitterSleepSeconds($timeout);
                continue;
            }

            if (!empty($data["updates"])) {
                foreach ($data["updates"] as $update) {
                    if (!isset($update["new_message"]["time"]))
                        continue;
                    $timestamp = (int) $update["new_message"]["time"];
                    if ($this->hasTime($timestamp, $seconds)) {
                        $message = new Message(["update" => $update], $this);
                        foreach ($handlers as $handler) {
                            $handler($message);
                        }
                    }
                }
            } else {
                $this->jitterSleepSeconds($timeout);
            }
        }
    }

    private function hasTime(int $timestamp, int $seconds = 5): bool
    {
        return (time() - $timestamp) <= $seconds;
    }

    private function jitterSleepSeconds($seconds): void
    {
        $base = max(250000, (int) ($seconds * 1000000));
        $cap = 5000000;
        $delay = min($cap, $base);
        try {
            $j = random_int(0, (int) ($delay * 0.25));
        } catch (\Throwable $e) {
            $j = 0;
        }
        usleep($delay + $j);
    }


    public function getToken(): string
    {
        return $this->TOKEN;
    }

    public function getMe(): ?array
    {
        return $this->post("getMe", [
            "getMe" => true
        ]);
    }

    public function sendMessage(
        string $chat_id,
        string $text,
        string|int|null $reply = null,
        bool $disable_notification = false,
        array $other = []
    ) {
        $data = [
            "chat_id" => $chat_id,
            "text" => $text,
            "disable_notification" => $disable_notification
        ];

        if (!is_null($reply) || !empty($reply))
            $data["reply_to_message_id"] = $reply;
        if (!is_null($this->parseMode)) {
            if ($this->parseMode == ParseMode::Auto)
                $text = $this->SmartH2M($text);
            else {
                if ($this->parseMode == ParseMode::HTML)
                    $text = $this->html_to_markdown($text);
            }
            $metadata = $this->markdown->toMetadata($text);
            $data["text"] = $metadata["text"];
            if (isset($metadata["metadata"]))
                $data["metadata"] = $metadata["metadata"];
        }

        $result = $this->post(
            "sendMessage",
            array_merge($data, $other)
        );
        $result["chat_id"] = $chat_id;
        $result["reply_to_message_id"] = $reply;
        return $result;
    }

    public function editMessage(
        string $chat_id,
        string|int $message_id,
        string $text,
        array $other = []
    ) {
        $data = [
            "text" => $text,
            "chat_id" => $chat_id,
            "message_id" => $message_id,
        ];

        if (!is_null($this->parseMode)) {
            if ($this->parseMode == ParseMode::Auto)
                $text = $this->SmartH2M($text);
            else {
                if ($this->parseMode == ParseMode::HTML)
                    $text = $this->html_to_markdown($text);
            }
            $metadata = $this->markdown->toMetadata($text);
            $data["text"] = $metadata["text"];
            if (isset($metadata["metadata"]))
                $data["metadata"] = $metadata["metadata"];
        }

        $result = $this->post(
            "editMessageText",
            array_merge($data, $other)
        );

        $result["chat_id"] = $chat_id;
        $result["message_id"] = $message_id;
        return $result;
    }

    public function deleteMessage(
        string $chat_id,
        string|int $message_id,
        array $other = []
    ) {
        $data = [
            "chat_id" => $chat_id,
            "message_id" => $message_id
        ];
        $result = $this->post(
            "deleteMessage",
            array_merge($data, $other)
        );

        $result["chat_id"] = $chat_id;
        $result["message_id"] = $message_id;
        return $result;
    }

    public function sendFile(
        string $chat_id,
        string $text,
        string $path,
        ?string $file_name = null,
        string|int|null $reply = null,
        bool $disable_notification = false,
        ?callable $progress = null,
        array $other = []

    ) {
        if (!is_null($file_name)) {
            $other["file_name"] = $file_name;
        }
        if (!isset($other["file_id"])) {
            try {
                $file_id = $this->streamUpload($path, $progress, $other);
            } catch (BotException $e) {
                $this->log->error("225|BF - UPLOAD ERROR: {$e->getMessage()}");
                return $e->getMessage();
            }
        } else
            $file_id = $other["file_id"];

        $data = [
            "chat_id" => $chat_id,
            "text" => $text,
            "file_id" => $file_id,
            "disable_notification" => $disable_notification
        ];
        if (!is_null($reply) || !empty($reply))
            $data["reply_to_message_id"] = $reply;
        if (!is_null($this->parseMode)) {
            if (!empty($text)) {
                if ($this->parseMode == ParseMode::Auto)
                    $text = $this->SmartH2M($text);
                else {
                    if ($this->parseMode == ParseMode::HTML)
                        $text = $this->html_to_markdown($text);
                }
                $metadata = $this->markdown->toMetadata($text);
                $data["text"] = $metadata["text"];
                if (isset($metadata["metadata"]))
                    $data["metadata"] = $metadata["metadata"];
            }
        }

        $result = $this->post(
            "sendFile",
            array_merge($data, $other)
        );
        if (is_array($result)) {
            $result["chat_id"] = $chat_id;
            $result["reply_to_message_id"] = $reply;
            $result["file_id"] = $file_id;
        }
        return $result;
    }

    public function sendPoll(
        string $chat_id,
        string $question,
        array $options,
        string|int|null $reply = null,
        bool $disable_notification = false,
        array $other = []
    ) {
        $data = [
            "chat_id" => $chat_id,
            "question" => $question,
            "options" => $options,
            "disable_notification" => $disable_notification
        ];

        if (!is_null($reply) || !empty($reply))
            $data["reply_to_message_id"] = $reply;

        $result = $this->post(
            "sendPoll",
            array_merge($data, $other)
        );
        $result["chat_id"] = $chat_id;
        $result["reply_to_message_id"] = $reply;
        return $result;
    }

    public function sendLocation(
        string $chat_id,
        string $latitude,
        string $longitude,
        string|int|null $reply = null,
        bool $disable_notification = false,
        array $other = []
    ) {
        $data = [
            "chat_id" => $chat_id,
            "latitude" => $latitude,
            "longitude" => $longitude,
            "disable_notification" => $disable_notification
        ];

        if (!is_null($reply) || !empty($reply))
            $data["reply_to_message_id"] = $reply;

        $result = $this->post(
            "sendLocation",
            array_merge($data, $other)
        );
        $result["chat_id"] = $chat_id;
        $result["reply_to_message_id"] = $reply;
        return $result;
    }


    public function sendContact(
        string $chat_id,
        string $phone,
        string $first_name,
        string $last_name,
        ?string $reply = null,
        bool $disable_notification = false,
        array $other = []
    ) {
        $data = [
            "chat_id" => $chat_id,
            "first_name" => $first_name,
            "last_name" => $last_name,
            "phone_number" => $phone,
            "disable_notification" => $disable_notification
        ];

        if (!is_null($reply) || !empty($reply))
            $data["reply_to_message_id"] = $reply;

        $result = $this->post(
            "sendContact",
            array_merge($data, $other)
        );
        $result["chat_id"] = $chat_id;
        $result["reply_to_message_id"] = $reply;
        return $result;
    }

    public function forwardMessage(
        string $chat_id,
        string $from_chat_id,
        string|int $message_id,
        bool $disable_notification,
        array $other = []
    ) {
        $data = [
            "from_chat_id" => $from_chat_id,
            "message_id" => $message_id,
            "to_chat_id" => $chat_id,
            "disable_notification" => $disable_notification
        ];

        $result = $this->post(
            "forwardMessage",
            array_merge($data, $other)
        );
        $result["chat_id"] = $chat_id;
        return $result;
    }

    public function getChat(
        string|array $chat_ids,
        array $other = []
    ): ?array {
        if (is_string($chat_ids)) {
            $result = $this->post(
                "getChat",
                array_merge(
                    [
                        "chat_id" => $chat_ids,
                    ],
                    $other
                )
            );
            return $result;
        }
        if (is_array($chat_ids)) {
            $data = [];
            foreach ($chat_ids as $id) {
                $data[] = $this->getChat($id);
            }
            return $data;
        }
        return [];
    }


    public function getUpdates(
        int $limit = 100,
        string $offset_id = "",
        array $other = []
    ) {
        $data = [
            "limit" => $limit,
            "offset_id" => $offset_id
        ];

        $result = $this->post(
            "getUpdates",
            array_merge($data, $other)
        );
        return $result;
    }

    public function setCommands(
        array $lists,
        array $other = []
    ) {
        $commands = [];
        foreach ($lists as $item) {
            $commands[] = [
                "command" => $item[0],
                "description" => $item[1]
            ];
        }
        $result = $this->post(
            "setCommands",
            array_merge(["bot_commands" => $commands], $other)
        );
        return $result;
    }

    public function setWebHook(
        string $url,
        string|null $type = null
    ) {
        $endPointType = [
            "ReceiveUpdate",
            "ReceiveInlineMessage",
            "ReceiveQuery",
            "GetSelectionItem",
            "SearchSelectionItems"
        ];
        $data = [
            "url" => $url,
            "type" => "ReceiveUpdate"
        ];
        if (!is_null($type)) {
            if (!in_array($type, $endPointType)) {
                $this->log->error("465|BW - invalid type : $type - types : " . json_encode($endPointType));
                throw new BotException("invalid type : $type - types : " . json_encode($endPointType));
            }
            $result = $this->post(
                "updateBotEndpoints",
                $data
            );
            return $result;
        }
        $response = [];
        foreach ($endPointType as $updateType) {
            $result = $this->setWebHook($url, $updateType);
            $response[$updateType] = $result["data"]["status"] ?? $result;
        }
        return $response;
    }

    public function editChatKeypad(
        string $chat_id,
        array $other = [],
        KeypadType $type = KeypadType::Remove
    ) {
        $data = [
            "chat_id" => $chat_id,
            "chat_keypad_type" => $type->value
        ];
        $result = $this->post(
            "editChatKeypad",
            array_merge($data, $other)
        );
        return $result;
    }



    public function download(
        string $file_id,
        ?string $fileName = null,
        ?callable $progress = null
    ): string {
        if (str_starts_with($file_id, "http://") || str_starts_with($file_id, "https://")) {
            $url = $file_id;
        } else {
            $getUrl = $this->getFile($file_id);
            if (!isset($getUrl["download_url"])) {
                return "error get url from file_id" . json_encode($getUrl);
            }
            $url = $getUrl["download_url"];
        }

        $downloadDir = "_download";
        if (!is_dir($downloadDir)) {
            mkdir($downloadDir, 0777, true);
        }

        if ($fileName === null) {
            $fileName = basename(parse_url($url, PHP_URL_PATH));
            if (empty($fileName)) {
                $fileName = "download_" . (string) (time());
            }
        }

        $savePath = $downloadDir . DIRECTORY_SEPARATOR . $fileName;
        $timeout = $_ENV["DOWNLOAD_TIME_OUT"] ?? 60;
        $client = new Client([
            "timeout" => is_numeric($timeout) ? (int) $timeout : 60,
            "connect_timeout" => 15,
            "headers" => [
                "User-Agent" => "RubotDl/1.1.0"
            ]
        ]);

        try {
            $fileSize = file_exists($savePath) ? filesize($savePath) : 0;

            $response = $client->request("GET", $url, [
                "sink" => fopen($savePath, $fileSize > 0 ? "ab" : "wb"),
                "progress" => function ($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) use ($progress, $fileSize) {
                    if ($downloadTotal > 0 && is_callable($progress)) {
                        $total = $downloadTotal + $fileSize;
                        $current = $downloadedBytes + $fileSize;
                        $percent = (int) round(($current / $total) * 100);
                        $progress($percent, $current, $total);
                    }
                },
                "headers" => $fileSize > 0 ? ["Range" => "bytes={$fileSize}-"] : []
            ]);

            if ($response->getStatusCode() >= 400) {
                $this->log->error("554|BD - Download failed with status: " . $response->getStatusCode());
                throw new BotException("Download failed with status: " . $response->getStatusCode());
            }

        } catch (RequestException $e) {
            $this->log->error("559|BD - {$e->getMessage()}");
            throw new BotException("Error: " . $e->getMessage());
        }

        return $savePath;
    }

    public function getFile(string $file_id)
    {
        return $this->post(
            "getFile",
            ["file_id" => $file_id]
        );
    }

    public function streamUpload(string $source, ?callable $progress = null, $options = []): string
    {
        $fileType = $this->detectFileType(
            $options["file_name"] ?? $source
        );
        if (isset($options["upload_type"]) && in_array($options["upload_type"], self::ALLOWED_MEDIA_TYPES)) {
            $fileType = [$options["upload_type"]];
        }
        $fileName = self::getFileName(
            $options["file_name"] ?? $source
        );

        try {
            $uploadUrl = $this->getUploadUrl($fileType[0]);
        } catch (BotException $e) {
            $this->log->error("589|BU - {$e->getMessage()}");
            return "Error : " . $e->getMessage();
        }

        if ($fileName === null) {
            $fileName = basename(parse_url($source, PHP_URL_PATH)) ?: "file_" . time();
        }

        $client = new Client([
            "verify" => false,
            "timeout" => 0,
            "connect_timeout" => 15,
            "headers" => [
                "User-Agent" => "RubotUpload/2.1.0"
            ]
        ]);

        if (preg_match('/^https?:\/\//i', $source)) {
            // ----------- URL -----------
            try {
                $downloadResponse = $client->request("GET", $source, ["stream" => true]);
            } catch (RequestException $e) {
                $this->log->error("611|BU - {$e->getMessage()}");
                throw new BotException("Download error: " . $e->getMessage());
            }

            $bodyStream = $downloadResponse->getBody();
            $totalSize = (int) $downloadResponse->getHeaderLine("Content-Length");
            $contents = Utils::streamFor($bodyStream);
        } else {
            // ----------- FILE PATH -----------
            if (!is_file($source) || !is_readable($source)) {
                $this->log->error("621|BU - File not found or not readable: $source");
                throw new BotException("File not found or not readable: $source");
            }

            $totalSize = filesize($source);
            $contents = fopen($source, "rb");
        }

        try {
            $multipart = new MultipartStream([
                [
                    "name" => "file",
                    "contents" => $contents,
                    "filename" => $fileName,
                    "headers" => ["Content-Type" => "application/octet-stream"]
                ]
            ]);

            $response = $client->request("POST", $uploadUrl, [
                "body" => $multipart,
                "progress" => function (int $dl_total_size, int $dl_size_so_far, int $ul_total_size, int $ul_size_so_far) use ($progress, $totalSize) {
                    if (is_callable($progress)) {
                        $progress(
                            $dl_total_size,
                            $dl_size_so_far,
                            $ul_total_size,
                            $ul_size_so_far
                        );
                    }
                }
            ]);

        } catch (RequestException $e) {
            $this->log->error("654|BU - Upload error: {$e->getMessage()}");
            throw new BotException("Upload error: " . $e->getMessage());
        }

        $json = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($json) || !isset($json["data"]["file_id"]) || !is_string($json["data"]["file_id"])) {
            $this->log->error("661|BF - Invalid JSON response: " . (string) $response->getBody());
            throw new BotException("Invalid JSON response: " . (string) $response->getBody());
        }

        return $json["data"]["file_id"];
    }



    private function getUploadUrl(string $media_type)
    {
        $allowed = self::ALLOWED_MEDIA_TYPES;

        if (!in_array($media_type, $allowed)) {
            $this->log->error("657|BUU - Invalid media type. Must be one of: " . implode(", ", $allowed));
            throw new BotException("Invalid media type. Must be one of: " . implode(", ", $allowed));
        }

        $response = $this->post("requestSendFile", [
            "type" => $media_type
        ]);
        if (!isset($response["upload_url"])) {
            $this->log->error("683|BUU - url not found.");
            throw new BotException("url not found.");

        }

        return $response["upload_url"];
    }


    private static function detectFileType(string $path): array
    {
        $type = strtolower(
            pathinfo(
                parse_url($path, PHP_URL_PATH) ?: $path,
                PATHINFO_EXTENSION
            )
        );

        $map = match ($type) {
            "jpg", "jpeg", "png", "webp", "svg" => "Image",
            "mp3", "wav", "flac", "aac", "m4a", "wma" => "File",
            "ogg", "opus", "amr" => "File",
            "gif", "apng" => "Gif",
            "mp4", "mov", "avi", "mkv" => "Video",
            "zip", "rar" => "File",
            default => "File",
        };

        return [$map, $type ?: "unk"];
    }
    private static function getFileName(string $path): string
    {
        $type = self::detectFileType($path)[1];
        $cleanPath = parse_url($path, PHP_URL_PATH);
        return basename($cleanPath) ?? "sanfBotUpload.$type";
    }


    private function loadEnvFile()
    {
        $envPath = dirname(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["file"]) . DIRECTORY_SEPARATOR;
        $format = basename($this->Settings["env"] ?? ".env");

        $this->Settings["env"] = base64_encode($envPath . $format);

        if (file_exists($envPath . $format)) {
            return [$envPath, $format];
        }

        $this->setEnvValue("# ", " Bot Settings");
        $this->setEnvValue("TOKEN", $this->TOKEN);//set
        $this->setEnvValue("AUTH", FALSE);//set
        $this->setEnvValue("RUN", FALSE); // set
        $this->setEnvValue("OFFSET_ID", FALSE);
        $this->setEnvValue("WEB_HOOK", FALSE);
        $this->setEnvValue("TIME_OUT", 10); //set
        $this->setEnvValue("DOWNLOAD_TIME_OUT", 60); //set
        $this->setEnvValue("RETRIES", 3);//set
        $this->setEnvValue("DELAY", 2);//set
        $this->setEnvValue("# ", "Developer Settings");
        $this->setEnvValue("DEBUG", FALSE); // set
        $this->setEnvValue("ENV_LOCATION", $this->Settings["env"]); // set


        return [$envPath, $format];
    }
    private function loadSettings()
    {
        $this->Settings = array_merge($this->Settings, $_ENV);

        $run = filter_var($_ENV["RUN"] ?? false, FILTER_VALIDATE_BOOL);
        if (isset($run) && is_bool($run) && (bool) $run) {
            $this->TOKEN = $_ENV["TOKEN"] ?? null;
        }

    }

    private function SmartH2M($text)
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $out = [];
        foreach ($lines as $line) {
            if (str_contains($line, '<') && str_contains($line, '>')) {
                $out[] = $this->html_to_markdown($line);
            } else {
                $out[] = $line;
            }
        }
        return implode("\n", $out);
    }

    private function html_to_markdown($text)
    {
        if (strpos($text, '<') !== false && strpos($text, '>') !== false) {
            $converter = new HtmlConverter($this->H2MConfig);
            $converter->getEnvironment()->addConverter(new InsConverter());
            $converter->getEnvironment()->addConverter(new DelConverter());
            $converter->getConfig()->setOption("italic_style", "__");
            $converter->getConfig()->setOption('use_autolinks', false);
            return $converter->convert($text);
        }
        return $text;
    }

}