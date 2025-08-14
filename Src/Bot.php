<?php

namespace RuBot;

use RuBot\Exception\BotException;
use RuBot\Tools\Message;
use RuBot\Enums\{
    updateEndpointType,
    Keypad
};

class Bot
{
    private string $Token;

    public string|false $SecretKey = false;
    public int $TimeOut = 10;
    public bool|string $setWebhookURL = true;

    private string $baseUrl = "https://botapi.rubika.ir/v3/";

    public function __construct(string $token)
    {
        $this->Token = $token;

        if (file_exists("info.json")) {
            return;
        }

        $autoWB = false;
        $url = null;


        if ($this->setWebhookURL === true) {
            $url = $this->getWebHookAddress();
            if (!empty($this->SecretKey)) {
                $url .= "?key=" . $this->SecretKey;
            }
            $autoWB = true;
        } else if (is_string($this->setWebhookURL) && filter_var($this->setWebhookURL, FILTER_VALIDATE_URL)) {
            $url = $this->setWebhookURL;
            $autoWB = false;
        } else {
            throw new BotException("Error: invalid setWebhookURL value");
        }

        if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
            $responseWB = $this->setWebHook($url);
        } else {
            throw new BotException("Error: invalid WebHook URL");
        }

        $bot_info = $this->getMe()["bot"] ?? "error token";
        $data = [
            "rubika bot api - dev:sanfapi | V1.0.1",
            "config" => [
                "autoWB" => $autoWB,
                "secretKey" => "UNK"
            ],
            "webhook" => [
                "url" => $this->removeKeyParam($url),
                "response" => $responseWB
            ],
            "bot" => $bot_info,
        ];
        file_put_contents("info.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function removeKeyParam(string $url): string
    {
        $parts = parse_url($url);
        $query = [];

        if (empty($parts) || !isset($parts['query'], $parts['scheme'], $parts['host']))
            return $url;

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            unset($query['key']);
        }
        $newUrl = $parts['scheme'] . '://' . $parts['host']
            . (isset($parts['path']) ? $parts['path'] : '')
            . (!empty($query) ? '?' . http_build_query($query) : '');

        return $newUrl;
    }

    private function getWebHookAddress(): string
    {
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
        $filePath = realpath($_SERVER['SCRIPT_FILENAME']); // <<< اینجا تغییر دادیم
        if (strpos($filePath, $docRoot) !== 0) {
            throw new BotException("File is outside of web root.");
        }
        $relativePath = str_replace('\\', '/', substr($filePath, strlen($docRoot)));
        $host = $_SERVER['HTTP_HOST'] ?? "error";
        return 'https://' . $host . '/' . ltrim($relativePath, '/');
    }


    /**
     * setSecretKey
     *
     * Validates incoming requests against the predefined Secret Key.
     *
     * @param bool $Block If true, blocks unauthorized requests. Default = true
     * @param array $option {
     *     @type int    $FRCode     HTTP Response code on failure. Default = 403
     *     @type string $ADMessage  Message to show when access is denied. Default = "Forbidden"
     *     @type bool|string $AFRedirect URL to redirect on failure. Default = false (no redirect)
     * }
     *
     * @throws \RuBot\Exception\BotException
     * @return string "ok" if access granted
     */

    public function setSecretKey(bool $Block = true, array $option = [])
    {
        $FRC = $option["FRCode"] ?? 403;
        $ADM = $option["ADMessage"] ?? "Forbidden";
        $AFR = $option["AFRedirect"] ?? false;

        if (!$this->SecretKey) {
            throw new BotException("Please set the access SecretKey first.");
        }

        if ($Block) {
            $sendKey = $_GET["key"] ?? "";
            $sendKey = substr($sendKey, 0, strlen($this->SecretKey));

            $access = strtolower($this->SecretKey) === $sendKey;

            if (!$access) {
                http_response_code(is_numeric($FRC) ? $FRC : 403);

                if ($AFR) {
                    header("Location: $AFR");
                    exit;
                }

                exit($ADM);
            }
        }

        return false;
    }

    public function checkSecretKey(): bool
    {
        if (!$this->SecretKey) {
            throw new BotException("Please set the access SecretKey first.");
        }
        $sendKey = $_GET["key"] ?? "";
        $sendKey = substr($sendKey, 0, strlen($this->SecretKey));

        $access = strtolower($this->SecretKey) === $sendKey;

        if (!$access) {
            return true;
        }

        return false;
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

    public function sendFile(
        string $text,
        string $chat_id,
        string $path,
        string $reply = "",
        array $options = []
    ) {
        if (isset($options["file_id"])) {
            $file_id = $options["file_id"];
        } else {
            $fileType = $this->detectFileType($path);
            $fileName = self::getFileName($path);
            try {
                $uploadURL = $this->getUploadUrl($fileType[0]);
            } catch (BotException $e) {
                return "Error : " . $e->getMessage();
            }
            try {
                $file_id = $this->uploadMediaFile($uploadURL, $fileName, $path);
            } catch (BotException $e) {
                return "Error : " . $e->getMessage();
            }
        }
        $json = [
            "chat_id" => $chat_id,
            "text" => $text,
            "file_id" => $file_id,
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
                parse_url(
                    $path,
                    PHP_URL_PATH
                ),
                PATHINFO_EXTENSION
            )
        );
        $map = [
            "jpg" => "Image",
            "jpeg" => "Image",
            "png" => "Image",
            "webp" => "Image",
            "mp3" => "Music",
            "wav" => "Music",
            "flac" => "Music",
            "aac" => "Music",
            "ogg" => "Voice",
            "opus" => "Voice",
            "amr" => "Voice",
            "gif" => "Gif"
        ];
        $getType = $map[$type] ?? "File";
        return [$getType, $type ?? "unk"];
    }

    public function getFile(string $file_id)
    {
        return $this->_request("getFile", ["file_id" => $file_id]);
    }

    public function getUploadUrl(string $media_type)
    {
        $allowed = ["File", "Image", "Voice", "Music", "Gif"];

        if (!in_array($media_type, $allowed))
            throw new BotException("Invalid media type. Must be one of: " . implode(", ", $allowed));

        $response = $this->_request("requestSendFile", [
            "type" => $media_type
        ]);
        if (!isset($response["upload_url"]))
            throw new BotException("url not found.");

        return $response["upload_url"];
    }

    public function uploadMediaFile(string $upload_url, string $name, string $path)
    {
        $isTempFile = false;
        $maxSize = 49 * 1024 * 1024; // 49MB
        $tempPath = null;

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $content = @file_get_contents($path);
            if ($content === false) {
                throw new BotException("Failed to download file from URL.");
            }
            if (strlen($content) > $maxSize) {
                throw new BotException("File size exceeds the 49MB limit.");
            }

            $tempPath = tempnam(sys_get_temp_dir(), "UPLOADS");
            file_put_contents($tempPath, $content);
            $path = $tempPath;
            $isTempFile = true;
        } else {
            if (!file_exists($path)) {
                throw new BotException("File not found: $path");
            }
            if (filesize($path) > $maxSize) {
                throw new BotException("File size exceeds the 49MB limit.");
            }
        }

        $curlFile = curl_file_create($path, "application/octet-stream", $name);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $upload_url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => ["file" => $curlFile],
        ]);

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

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new BotException("Invalid JSON response: $response");
        }

        $enData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $data["data"]["file_id"] ?? throw new BotException("file_id not found in response. data: $enData");
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
    public function getUpdates(string $offset_id = "", int $limit = 0)
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

    public function setWebHook(string $url): array
    {
        $response = [];
        foreach (updateEndpointType::cases() as $updateType) {
            $setWB = $this->WebHook($url, $updateType);
            $response[$updateType->value] = $setWB["status"] ?? $setWB;
        }
        return $response;
    }

    public function onMessage(callable $callback)
    {
        $update = json_decode(file_get_contents("php://input"), true);
        if ($update) {
            $message = new Message($update, $this);
            $callback($message);
        }
        return $update;
    }

    public function _request(string $method, array $input = [])
    {
        $url = $this->baseUrl . $this->Token . "/" . $method;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->TimeOut,
            CURLOPT_POSTFIELDS => json_encode($input, JSON_UNESCAPED_UNICODE),
        ]);

        $rawResponse = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new BotException("Error: " . $error);
        }

        $response = json_decode($rawResponse, true);

        if (!is_array($response)) {
            curl_close($ch);
            throw new BotException("Invalid JSON response: " . $rawResponse);
        }

        curl_close($ch);
        @http_response_code(200);

        return $response["status"] === "OK" ? $response["data"] : $response;
    }
}
