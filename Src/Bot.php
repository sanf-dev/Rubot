<?php

declare(strict_types=1);


namespace RuBot;

use RuBot\Exception\BotException;
use RuBot\Tools\Message;
use RuBot\Enums\{
    updateEndpointType,
    Keypad
};

class Bot
{
    /** @var \CurlHandle|null */
    private $ch = null;

    private function getCurl()
    {
        if (!$this->ch) {
            $this->ch = curl_init();
        }
        if (function_exists('curl_reset')) {
            curl_reset($this->ch);
        }
        return $this->ch;
    }

    private function baseCurlOptions(): array
    {
        return [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_TCP_KEEPALIVE => 1,
            CURLOPT_TCP_KEEPIDLE => 30,
            CURLOPT_TCP_KEEPINTVL => 15,
            CURLOPT_ENCODING => "",
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Accept-Charset: UTF-8",
                "Expect:"
            ],
            CURLOPT_USERAGENT => $this->userAgent ?? "RuBot/1.1.0",
        ];
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

    private const BASE_URL = "https://botapi.rubika.ir/v3/";
    private const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB
    private const ALLOWED_MEDIA_TYPES = ["File", "Image", "Voice", "Music", "Video", "Gif"];

    private string $TOKEN;
    public int $MAX_RETRIES = 3;
    private int $RETRYCOUNT = 0;
    public int $TimeOut = 10;

    public function __construct(string $token)
    {
        $this->TOKEN = $token;

        if (empty($token)) {
            throw new BotException("Token cannot be empty.");
        }
    }

    /**
     * get Bot Info
     */
    public function getMe()
    {
        return $this->_request("getMe", [
            "getMe" => true
        ]);
    }

    /**
     * Send Message
     * @param string $text | message
     * @param string $chat_id | target chat id
     * @param string $reply | reply to message id
     * @param array $options | method : [disable_notification:bool , inline_keypad:array ,chat_keypad:array, chat_keypad_type:str]
     */
    public function sendMessage(
        string $text,
        string $chat_id,
        string $reply = "",
        array $options = []
    ) {
        if (empty($text) || empty($chat_id)) {
            throw new BotException("Text and chat_id cannot be empty.");
        }

        $json = [
            "chat_id" => $chat_id,
            "text" => $text,
            "parse_mode" => "Markdown"
        ];

        if (!empty($reply)) {
            $json["reply_to_message_id"] = $reply;
        }
        if (!empty($options)) {
            if (isset($options["disable_notification"])) {
                $json["disable_notification"] = $options["disable_notification"];
            }
            if (isset($options["inline_keypad"])) {
                $json["inline_keypad"] = $options["inline_keypad"];
            }
            if (isset($options["chat_keypad"])) {
                $json["chat_keypad"] = $options["chat_keypad"];
                $json["chat_keypad_type"] = "New";
            }
            if (isset($options["chat_keypad_type"])) {
                $json["chat_keypad_type"] = $options["chat_keypad_type"];
            }
        }
        return $this->_request("sendMessage", $json);
    }

    /**
     * Sends a file.
     *
     * @param string $text Caption
     * @param string $chat_id Target chat ID
     * @param string $path File path or URL
     * @param string $reply Reply to message ID
     * @param array $options Options [file_id:string, disable_notification:bool, inline_keypad:array, chat_keypad:array, chat_keypad_type:str]
     * @return array|string
     * @throws BotException
     */
    public function sendFile(
        string $text,
        string $chat_id,
        ?string $path = null,
        ?string $reply = "",
        ?callable $progress = null,
        array $options = []
    ) {
        if (isset($options["file_id"])) {
            $file_id = $options["file_id"];
        } else {
            if (is_null($path) || empty($path)) {
                return "File Not Found";
            }
            $fileType = $this->detectFileType(
                isset($options["file_name"]) ? $options["file_name"] : $path
            );
            $fileName = self::getFileName(
                isset($options["file_name"]) ? $options["file_name"] : $path
            );

            if (preg_match('#^https?://#i', $path)) {
                try {
                    $file_id = $this->streamUpload($path, $fileName, $progress, $options);
                } catch (BotException $e) {
                    return "Error Upload File : " . $e->getMessage();
                }
            } elseif (file_exists($path)) {
                try {
                    $uploadURL = $this->getUploadUrl($fileType[0]);
                } catch (BotException $e) {
                    return "Error : " . $e->getMessage();
                }
                try {
                    $file_id = $this->uploadMediaFile($uploadURL, $fileName, $path, $progress);
                } catch (BotException $e) {
                    return "Error : " . $e->getMessage();
                }
            } else
                return "File Not Found";

        }
        $json = [
            "chat_id" => $chat_id,
            "text" => $text,
            "file_id" => $file_id,
        ];
        if (!empty($reply) || !is_null($reply)) {
            $json["reply_to_message_id"] = $reply;
        }

        if (!empty($options)) {
            if (isset($options["disable_notification"])) {
                $json["disable_notification"] = $options["disable_notification"];
            }
            if (isset($options["inline_keypad"])) {
                $json["inline_keypad"] = $options["inline_keypad"];
            }
            if (isset($options["chat_keypad"])) {
                $json["chat_keypad"] = $options["chat_keypad"];
                $json["chat_keypad_type"] = "New";
            }
            if (isset($options["chat_keypad_type"])) {
                $json["chat_keypad_type"] = $options["chat_keypad_type"];
            }
        }
        $response = $this->_request("sendFile", $json);
        if (is_array($response)) {
            $response["file_id"] = $file_id;
        }
        return $response;
    }

    private static function getFileName(string $path): string
    {
        $type = self::detectFileType($path)[1];
        $cleanPath = parse_url($path, PHP_URL_PATH);
        return basename($cleanPath) ?? "sanfBotUpload.$type";
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
            "mp3", "wav", "flac", "aac", "m4a", "wma" => "Music",
            "ogg", "opus", "amr" => "Voice",
            "gif", "apng" => "Gif",
            "mp4", "mov", "avi", "mkv" => "Video",
            "zip", "rar" => "File",
            default => "File",
        };

        return [$map, $type ?: "unk"];
    }

    public function getFile(string $file_id)
    {
        return $this->_request("getFile", ["file_id" => $file_id]);
    }

    private function getUploadUrl(string $media_type)
    {
        $allowed = self::ALLOWED_MEDIA_TYPES;

        if (!in_array($media_type, $allowed))
            throw new BotException("Invalid media type. Must be one of: " . implode(", ", $allowed));

        $response = $this->_request("requestSendFile", [
            "type" => $media_type
        ]);
        if (!isset($response["upload_url"]))
            throw new BotException("url not found.");

        return $response["upload_url"];
    }

    private function uploadMediaFile(string $upload_url, string $name, string $path, callable $progress = null)
    {
        $isTempFile = false;
        $maxSize = self::MAX_FILE_SIZE;
        $tempPath = null;

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $context = stream_context_create(['http' => ['timeout' => $this->TimeOut + 20]]);
            $content = file_get_contents($path, false, $context);

            if ($content === false) {
                throw new BotException("Failed to download file from URL.");
            }
            if (strlen($content) > $maxSize) {
                throw new BotException("File size exceeds the " . (self::MAX_FILE_SIZE / 1024 / 1024) . "MB limit.");
            }

            $tempPath = tempnam(sys_get_temp_dir(), "_upload");
            if ($tempPath === false) {
                mkdir("_upload");
                $tempPath = tempnam(sys_get_temp_dir(), "_upload");
            }

            file_put_contents($tempPath, $content);
            $path = $tempPath;
            $isTempFile = true;
        } else {
            if (!file_exists($path)) {
                throw new BotException("File not found: $path");
            }
            if (filesize($path) > self::MAX_FILE_SIZE) {
                throw new BotException("File size exceeds the " . (self::MAX_FILE_SIZE / 1024 / 1024) . "MB limit.");
            }
        }

        $curlFile = curl_file_create($path, "application/octet-stream", $name);

        $ch = $this->getCurl();
        curl_setopt_array($ch, $this->baseCurlOptions() + [
            CURLOPT_URL => $upload_url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_POSTFIELDS => ["file" => $curlFile],
            CURLOPT_NOPROGRESS => false
        ]);

        if ($progress !== null) {
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $download_size, $downloaded, $upload_size, $uploaded) use ($progress) {
                if ($upload_size > 0) {
                    $pupload = round(($uploaded / $upload_size) * 100, 2);
                    $progress($pupload, $uploaded, $upload_size);
                }
            });
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            if ($isTempFile && file_exists($path))
                unlink($path);
            throw new BotException("cURL error: $error");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($isTempFile && file_exists($path)) {
            unlink($path);
        }

        if ($httpCode !== 200) {
            throw new BotException("Upload failed ($httpCode): $response");
        }

        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new BotException("Invalid JSON response: $response");
        }

        $rawData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $data["data"]["file_id"] ?? throw new BotException("file_id not found in response. data: $rawData");
    }



    /**
     * send Poll
     * @param string $chat_id | target chat id
     * @param string $question | question 
     * @param array $options | list[str]
     */
    public function sendPoll(string $chat_id, string $question, array $options, $reply = "")
    {
        $json = [
            "chat_id" => $chat_id,
            "question" => $question,
            "options" => $options
        ];
        if (!empty($reply)) {
            $json["reply_to_message_id"] = $reply;
        }
        return $this->_request("sendPoll", $json);
    }

    /**
     * send Location
     * @param string $chat_id | target chat id
     * @param string $latitude  | φ - N
     * @param string $longitude | λ - E
     * @param array $options | method : [disable_notification:bool , inline_keypad:array ,chat_keypad:array, chat_keypad_type:str]
     */
    public function sendLocation(string $chat_id, string $latitude, string $longitude, string $reply = "", array $options = [])
    {
        $json = [
            "chat_id" => $chat_id,
            "latitude" => $latitude,
            "longitude" => $longitude
        ];
        if (!empty($reply)) {
            $json["reply_to_message_id"] = $reply;
        }
        if (!empty($options)) {
            if (isset($options["disable_notification"])) {
                $json["disable_notification"] = $options["disable_notification"];
            }
            if (isset($options["inline_keypad"])) {
                $json["inline_keypad"] = $options["inline_keypad"];
            }
            if (isset($options["chat_keypad"])) {
                $json["chat_keypad"] = $options["chat_keypad"];
                $json["chat_keypad_type"] = "New";
            }
            if (isset($options["chat_keypad_type"])) {
                $json["chat_keypad_type"] = $options["chat_keypad_type"];
            }
        }
        return $this->_request("sendLocation", $json);
    }

    /**
     * Send Contact
     * @param string $chat_id | target chat id
     * @param string $phone | phone number
     * @param string $first_name | first name
     * @param string $last_name | last name
     * @param array $options | method : [disable_notification:bool , inline_keypad:array ,chat_keypad:array, chat_keypad_type:str]
     */
    public function sendContact(string $chat_id, string $phone, string $first_name, string $last_name, string $reply = "", array $options = [])
    {
        $json = [
            "chat_id" => $chat_id,
            "first_name" => $first_name,
            "last_name" => $last_name,
            "phone_number" => $phone
        ];
        if (!empty($reply)) {
            $json["reply_to_message_id"] = $reply;
        }
        if (!empty($options)) {
            if (isset($options["disable_notification"])) {
                $json["disable_notification"] = $options["disable_notification"];
            }
            if (isset($options["inline_keypad"])) {
                $json["inline_keypad"] = $options["inline_keypad"];
            }
            if (isset($options["chat_keypad"])) {
                $json["chat_keypad"] = $options["chat_keypad"];
                $json["chat_keypad_type"] = "New";
            }
            if (isset($options["chat_keypad_type"])) {
                $json["chat_keypad_type"] = $options["chat_keypad_type"];
            }
        }
        return $this->_request("sendContact", $json);
    }

    /**
     * get Chat Info
     * @param string $chat_id | target chat id
     */
    public function getChat(string $chat_id)
    {
        $json = [
            "chat_id" => $chat_id,
        ];

        return $this->_request("getChat", $json);
    }

    /**
     * get Updates
     * @param string $offset_id | next page id
     * @param int $limit | limit
     */
    public function getUpdates(int $limit = 0, string $offset_id = "")
    {
        $json = [
            "getUpdates" => true
        ];
        $limit == 0 ?: $json["limit"] = $limit;
        empty($offset_id) ?: $json["offset_id"] = $offset_id;

        return $this->_request("getUpdates", $json);
    }

    /**
     * forward Message
     * @param string $to_chat_id | target chat id
     * @param string $message_id | forwarded message id
     * @param string $from_chat_id | chat id
     * @param array $options | method [disable_notification:bool]
     */
    public function forwardMessage(string $to_chat_id, string $message_id, string $from_chat_id, array $options = [])
    {
        $json = [
            "from_chat_id" => $from_chat_id,
            "message_id" => $message_id,
            "to_chat_id" => $to_chat_id
        ];
        if (!empty($options)) {
            if (isset($options["disable_notification"])) {
                $json["disable_notification"] = $options["disable_notification"];
            }
        }
        return $this->_request("forwardMessage", $json);
    }

    /**
     * edit Message
     * @param string $text
     * @param string $chat_id
     * @param string $message_id
     * @param array $options | method : [ inline_keypad:array ,chat_keypad:array, chat_keypad_type:str]
     */
    public function editMessage(string $chat_id, string $message_id, string $text = "", array $options = [])
    {
        if (empty($text) && empty($options)) {
            throw new BotException("error input is null");
        }
        $editMessageBool = !empty($text);
        $editKeypadBool = false;
        $editText = null;
        $editKeypad = null;

        $json = [
            "chat_id" => $chat_id,
            "message_id" => $message_id,
        ];
        if ($editMessageBool) {
            $json["text"] = $text;
            $editText = $this->_request("editMessageText", $json);
        }
        if (!empty($options)) {
            if (isset($options["inline_keypad"])) {
                $json["inline_keypad"] = $options["inline_keypad"];
                $editKeypadBool = true;
            }
            if (isset($options["chat_keypad"])) {
                $json["chat_keypad"] = $options["chat_keypad"];
                $json["chat_keypad_type"] = "New";
                $editKeypadBool = true;
            }
            if (isset($options["chat_keypad_type"])) {
                $json["chat_keypad_type"] = $options["chat_keypad_type"];
                $editKeypadBool = true;
            }
        }

        if ($editKeypadBool) {
            $editKeypad = $this->_request("editMessageKeypad", $json);
        }
        if ($editMessageBool && !$editKeypadBool) {
            return $editText;
        }

        if ($editKeypadBool && !$editMessageBool) {
            return $editKeypad;
        }
        return [
            "editMessageResponse" => $editText,
            "editKeypadResponse" => $editKeypad
        ];
    }

    /**
     * delete Message
     * @param string $chat_id | target chat id
     * @param string $message_id | message id
     */
    public function deleteMessage(string $chat_id, string $message_id)
    {
        $json = [
            "chat_id" => $chat_id,
            "message_id" => $message_id
        ];
        return $this->_request("deleteMessage", $json);
    }

    /**
     * set Commands
     * @param array $commands | list [command:str , description:str]
     */
    public function setCommands(array $commands)
    {
        $json = [
            "bot_commands" => $commands,
        ];
        return $this->_request("setCommands", $json);
    }

    public function WebHook(string $url, updateEndpointType $type = updateEndpointType::GetSelectionItem)
    {
        $json = [
            "url" => $url,
            "type" => $type->value
        ];
        return $this->_request("updateBotEndpoints", $json);
    }

    public function editChatKeypad(string $chat_id, Keypad $type = Keypad::New , array $options = [])
    {
        $json = [
            "chat_id" => $chat_id,
            "chat_keypad_type" => $type->value
        ];
        if (!empty($options) && $type->value == "Remove") {
            throw new BotException("error inpute | Option input cannot have a value of this type - Remove type");
        }
        if (empty($options) && $type->value == "New") {
            throw new BotException("error inpute | Option input cannot be empty in this type - New type");
        }
        if (isset($options["chat_keypad"])) {
            $json["chat_keypad"] = $options["chat_keypad"];
        }
        return $this->_request("editChatKeypad", $json);
    }

    public function setWebHook(string $url)
    {
        $response = [];
        foreach (updateEndpointType::cases() as $updateType) {
            $setWB = $this->WebHook($url, $updateType);
            $response[$updateType->value] = $setWB["status"] ?? $setWB;
        }
        return $response;
    }

    /**
     * download file from file_id or url
     * @param string $url
     * @param mixed $fileName
     * @param callable $progress
     * @throws \RuBot\Exception\BotException
     * @return string
     */
    public function download(string $file_id, ?string $fileName = null, callable $progress = null): string
    {
        if (substr($file_id, 0, 7) == "http://" || substr($file_id, 0, 8) == "https://") {
            $url = $file_id;
        } else {
            $getUrl = $this->getFile($file_id);
            if (!isset($getUrl["download_url"])) {
                return "error get url from file_id" . json_encode($getUrl);
            }
            $url = $getUrl["download_url"];
        }
        $downloadDir = '_download';
        if (!is_dir($downloadDir)) {
            mkdir($downloadDir, 0777, true);
        }

        if ($fileName === null) {
            $fileName = basename(parse_url($url, PHP_URL_PATH));
            if (empty($fileName)) {
                $fileName = 'download_' . time();
            }
        }

        $savePath = $downloadDir . DIRECTORY_SEPARATOR . $fileName;
        $fp = fopen($savePath, 'ab');
        if (!$fp) {
            throw new BotException("Cannot open file: $savePath");
        }

        $ch = curl_init($url);

        $fileSize = filesize($savePath);
        if ($fileSize > 0) {
            curl_setopt($ch, CURLOPT_RANGE, $fileSize . "-");
        }

        curl_setopt_array($ch, $this->baseCurlOptions() + [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => "RuBotDl/1.1.0",
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => function ($resource, $download_size, $downloaded, $upload_size, $uploaded) use ($progress, $fileSize) {
                if ($download_size > 0 && is_callable($progress)) {
                    $total = $download_size + $fileSize;
                    $current = $downloaded + $fileSize;
                    $percent = (int) round(($current / $total) * 100);
                    $progress((int) $percent, $current, $total);
                }
            }
        ]);

        $success = curl_exec($ch);

        if ($success === false) {
            throw new BotException("cURL Error: " . curl_error($ch));
        }

        curl_close($ch);
        fclose($fp);

        return $savePath;
    }

    /**
     * stream Upload file from url
     * @param string $downloadUrl
     * @param mixed $fileName
     * @param callable $progress
     * @param mixed $options
     * @throws \RuBot\Exception\BotException
     * @return string
     */
    public function streamUpload(string $downloadUrl, ?string $fileName = null, callable $progress = null, $options = []): string
    {
        $fileType = $this->detectFileType(
            isset($options["file_name"]) ? $options["file_name"] : $downloadUrl
        );
        $fileName = self::getFileName(
            isset($options["file_name"]) ? $options["file_name"] : $downloadUrl
        );
        try {
            $uploadUrl = $this->getUploadUrl($fileType[0]);
        } catch (BotException $e) {
            return "Error : " . $e->getMessage();
        }

        if ($fileName === null) {
            $fileName = basename(parse_url($downloadUrl, PHP_URL_PATH)) ?: 'file_' . time();
        }

        $downloadStream = fopen($downloadUrl, 'rb');
        if (!$downloadStream)
            throw new BotException("Cannot open download URL");
        $pipe = tmpfile();
        if (!$pipe) {
            fclose($downloadStream);
            throw new BotException("Cannot create temp upload stream");
        }

        $chunkSize = 1024 * 1024;
        $totalDownloaded = 0;
        $totalSize = 0;

        $headers = get_headers($downloadUrl, true);
        if (isset($headers['Content-Length']))
            $totalSize = (int) $headers['Content-Length'];

        while (!feof($downloadStream)) {
            $chunk = fread($downloadStream, $chunkSize);
            if ($chunk === false)
                break;
            $len = strlen($chunk);
            $totalDownloaded += $len;
            fwrite($pipe, $chunk);

            if ($progress && $totalSize > 0) {
                $percent = (int) (($totalDownloaded / $totalSize) * 50);
                $progress($percent, $totalDownloaded, 0, $totalSize);
            }
        }

        rewind($pipe);
        fclose($downloadStream);

        $chUpload = curl_init($uploadUrl);
        curl_setopt($chUpload, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chUpload, CURLOPT_POST, true);
        curl_setopt($chUpload, CURLOPT_POSTFIELDS, [
            'file' => curl_file_create(stream_get_meta_data($pipe)['uri'], 'application/octet-stream', $fileName)
        ]);

        if ($progress) {
            curl_setopt($chUpload, CURLOPT_NOPROGRESS, false);
            curl_setopt($chUpload, CURLOPT_PROGRESSFUNCTION, function ($ch, $dlTotal, $dlNow, $upTotal, $upNow) use ($progress, $totalDownloaded, $totalSize) {
                if ($upTotal > 0) {
                    $percent = 50 + (int) (($upNow / $upTotal) * 50);
                    $progress($percent, $totalDownloaded, $upNow, $totalSize + $upTotal);
                }
            });
        }

        $response = curl_exec($chUpload);
        if ($response === false) {
            $err = curl_error($chUpload);
            curl_close($chUpload);
            fclose($pipe);
            throw new BotException("Upload error: $err");
        }

        curl_close($chUpload);
        fclose($pipe);

        $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($json))
            throw new BotException("Invalid JSON response: $response");

        if (!isset($json['data']['file_id']) || !is_string($json['data']['file_id'])) {
            throw new BotException("Invalid JSON response: $response");
        }

        return $json['data']['file_id'];
    }

    public function hasTime(int $timestamp, int $seconds = 10): bool
    {
        return (time() - $timestamp) <= $seconds;
    }

    /**
     * onMessage
     * @param callable[] $handlers
     * @return array
     */
    public function onMessage(callable ...$handlers)
    {
        $data =
            $update = json_decode(
                file_get_contents("php://input"),
                true
            );
        if (!is_array($update)) {
            return [];
        }
        $message = new Message($update, $this);
        return array_map(fn($handler) => $handler($message), $handlers);
    }
    /**
     * Listen for new updates and dispatch them to handlers.
     *
     * @param array{
     *     limit?: 'maximum number of updates to fetch (int-default = 100)',
     *     timeout?: 'long-polling timeout in seconds (int-default = 1)',
     *     offset_id?: 'last processed update id (string-default = "")'
     * } $config Configuration options for update polling
     *
     * @param callable[] $handlers List of handler callbacks that process each update
     *
     * @return never This method runs an infinite loop and never returns
     */
    public function onUpdate(array $config = [], callable ...$handlers): never
    {
        $limit = $config["limit"] ?? 100;
        $timeout = $config["timeout"] ?? 0;
        $offset_id = $config["offset_id"] ?? "";

        while (true) {
            $data = $this->getUpdates($limit, $offset_id);

            if (isset($data["next_offset_id"])) {
                $offset_id = $data["next_offset_id"];
            } else {
                continue;
            }

            if (!empty($data["updates"])) {
                foreach ($data["updates"] as $update) {
                    if (!isset($update["new_message"]["time"]))
                        continue;

                    $timestamp = (int) $update["new_message"]["time"];
                    if ($this->hasTime($timestamp)) {
                        $message = new Message(["update" => $update], $this);
                        // $handlers($message);
                        array_map(fn($handler) => $handler($message), $handlers);
                    } else {
                        continue;
                    }
                }
            }

            $this->jitterSleepSeconds($timeout);
        }
    }

    public function _request(string $method, array $input = [])
    {
        $url = self::BASE_URL . $this->TOKEN . "/" . $method;
        $maxRetries = $this->MAX_RETRIES;
        $retryCount = $this->RETRYCOUNT;
        $lastError = null;

        while ($retryCount < $maxRetries) {
            $ch = curl_init($url);
            curl_setopt_array($ch, $this->baseCurlOptions() + [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->TimeOut,
                CURLOPT_POSTFIELDS => json_encode($input, JSON_UNESCAPED_UNICODE),
            ]);

            $rawResponse = curl_exec($ch);
            $curlErr = curl_errno($ch) ? curl_error($ch) : null;
            curl_close($ch);

            if ($curlErr) {
                $lastError = $curlErr;
                $retryCount++;
                echo "Trying to connect... - $retryCount\n";
                if ($retryCount < $maxRetries) {
                    sleep(pow(2, $retryCount - 1));
                    continue;
                }
                throw new BotException("cURL error after $retryCount retries: " . $lastError);
            }

            $response = json_decode($rawResponse, true);

            if (!is_array($response)) {
                $lastError = "Invalid JSON: " . $rawResponse;
                $retryCount++;
                echo "Trying to connect... - $retryCount";
                if ($retryCount < $maxRetries) {
                    sleep(pow(2, $retryCount - 1));
                    continue;
                }
                throw new BotException("Invalid JSON response after $retryCount retries: " . $rawResponse);
            }
            return $response["status"] === "OK" ? $response["data"] : $response;
        }
        throw new BotException("Request failed after $maxRetries retries: " . $lastError);
    }

}