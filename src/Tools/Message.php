<?php

declare(strict_types=1);

namespace Rubot\Tools;

use Rubot\Utils\Limiter;
use Rubot\Enums\{
    KeypadType,
    Field,
    LockType
};


class Message
{
    private array $data;
    private readonly object $bot;
    public bool|null $active_reply = true;

    private $chat_id = null;
    private $text = null;
    private $message_id = null;

    private const EXT_MAP = [
        "code" => ["php", "js", "ts", "py", "java", "c", "cpp", "cs", "go", "rb", "swift", "kt", "rs", "html", "css", "json", "xml", "yml", "sh", "bat"],
        "file" => ["obb", "pak", "zip", "rar", "7z", "tar", "gz", "xz", "iso", "dmg", "apk", "exe", "msi", "deb", "rpm"],
        "document" => ["pdf", "doc", "docx", "odt", "rtf", "txt", "md", "htm", "ppt", "pptx", "xls", "xlsx", "csv"],
        "image" => ["jpg", "jpeg", "png", "webp", "svg", "bmp", "tiff", "tif", "ico", "heic", "heif"],
        "video" => ["mp4", "mov", "avi", "mkv", "flv", "wmv", "webm", "mpeg", "mpg", "3gp", "m4v"],
        "music" => ["mp3", "wav", "flac", "aac", "m4a", "wma", "alac", "aiff", "dsd"],
        "database" => ["sql", "sqlite", "db", "accdb", "mdb", "bak"],
        "voice" => ["ogg", "opus", "amr", "3ga", "m4r"],
        "font" => ["ttf", "otf", "woff", "woff2", "eot"],
        "gif" => ["gif", "apng"],
        "unk" => ["unk"]
    ];


    public function __construct(array $update, object $bot)
    {
        $this->data = $this->Jsonflatten($update["update"] ?? $update ?? []);
        $this->bot = $bot;
    }

    // ----------- ANALIZ JSON DATA -----------
    private function Jsonflatten(array $array): array
    {
        $flat = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && $this->isAssoc($value)) {
                $flat = array_merge($flat, $value);
            } else {
                $flat[$key] = $value;
            }
        }
        return $flat;
    }

    private function isAssoc(array $arr): bool
    {
        $i = 0;
        foreach ($arr as $k => $_) {
            if ($k !== $i++)
                return true;
        }
        return false;
    }

    // ----------- GET JSON PARAM -----------
    public function text()
    {
        if (is_null($this->text))
            return $this->text = $this->data["text"] ?? false;
        else
            return $this->text;
    }

    public function chat_id()
    {
        if (is_null($this->chat_id))
            return $this->chat_id = $this->data["chat_id"] ?? $this->data["forwarded_from"] ?? false;
        else
            return $this->chat_id;

    }

    public function message_id()
    {
        if (is_null($this->message_id))
            return $this->message_id = $this->data["message_id"] ?? null;
        else
            return $this->message_id;
    }

    public function reply_to_message_id()
    {
        return $this->data["reply_to_message_id"] ?? null;
    }

    public function getTime()
    {
        return $this->data["time"] ?? false;
    }

    public function is_edited()
    {
        return $this->data["is_edited"] ?? false;
    }

    public function sender_type()
    {
        return $this->data["sender_type"] ?? false;
    }

    public function sender_id()
    {
        return $this->data["sender_id"] ?? false;
    }

    public function button_id()
    {
        return $this->data["aux_data"]["button_id"] ?? false;
    }

    public function File(?Field $field = null)
    {
        if (!is_null($field) && in_array($field, Field::fileFields()))
            return $this->data["file"][$field->value] ?? false;
        return $this->data["file"] ?? false;

    }

    public function location(?Field $field = null)
    {
        if (!is_null($field) && in_array($field, Field::locationFields()))
            return $this->data["location"][$field->value] ?? false;
        return $this->data["location"] ?? false;
    }

    public function contact(?Field $field = null)
    {
        if (!is_null($field) && in_array($field, Field::contactFields()))
            return $this->data["contact"][$field->value] ?? false;
        return $this->data["contact"] ?? false;
    }

    public function forwarded(?Field $field = null)
    {
        if (!is_null($field) && in_array($field, Field::contactFields()))
            return $this->data["forwarded_from"][$field->value] ?? $this->data["forwarded_no_link"][$field->value] ?? false;
        return $this->data["forwarded_from"] ?? $this->data["forwarded_no_link"] ?? false;
    }


    public function sticker()
    {
        return $this->data["sticker"] ?? false;
    }

    public function start_id()
    {
        return $this->data["aux_data"]["start_id"] ?? false;
    }

    public function rawData()
    {
        return $this->data;
    }


    // ----------- SEND METHODES -----------
    public function reply(
        string $text,
        array $other = []
    ) {
        return $this->bot->sendMessage(
            $this->chat_id(),
            $text,
            is_null($this->active_reply) ? null : $this->message_id(),
            false,
            $other
        );
    }

    public function deleteMessage(string $message_id = null)
    {
        return $this->bot->deleteMessage(
            $this->chat_id(),
            is_null($message_id) ? $this->message_id() : $message_id
        );
    }

    public function replyFile(
        string $text,
        string $file,
        ?string $file_name = null,
        ?callable $progress = null,
        array $other = []
    ) {
        return $this->bot->sendFile(
            $this->chat_id(),
            $text,
            $file,
            $file_name,
            is_null($this->active_reply) ? null : $this->message_id(),
            false,
            $progress,
            $other
        );
    }

    public function sendPoll(
        string $question,
        array $options,
        array $other = []
    ) {
        return $this->bot->sendPoll(
            $this->chat_id(),
            $question,
            $options,
            is_null($this->active_reply) ? null : $this->message_id(),
            false,
            $other
        );
    }

    public function sendLocation(
        string $latitude,
        string $longitude,
        array $other = []
    ) {
        return $this->bot->sendLocation(
            $this->chat_id(),
            $latitude,
            $longitude,
            is_null($this->active_reply) ? null : $this->message_id(),
            false,
            $other
        );
    }

    public function sendContact(
        string $phone,
        string $first_name,
        string $last_name,
        array $other = []
    ) {
        return $this->bot->sendContact(
            $this->chat_id(),
            $phone,
            $first_name,
            $last_name,
            is_null($this->active_reply) ? null : $this->message_id(),
            false,
            $other
        );
    }

    public function forwardMessage(
        string|int $message_id,
        string $from_chat_id,
        array $other = []
    ) {
        return $this->bot->forwardMessage(
            $this->chat_id(),
            $from_chat_id,
            $message_id,
            false,
            $other
        );
    }

    public function editChatKeypad(
        KeypadType $type = KeypadType::New ,
        array $other = []
    ) {
        return $this->bot->editChatKeypad(
            $this->chat_id(),
            $other,
            $type
        );
    }

    public function removeChatKeypad(
        KeypadType $type = KeypadType::Remove,
        array $other = []
    ) {
        return $this->bot->editChatKeypad(
            $this->chat_id(),
            $other,
            $type
        );
    }

    public function DownloadFile(
        ?string $file_name = null,
        ?callable $progress = null
    ) {
        return $this->bot->download(
            $this->File(Field::FILE_ID),
            is_null($file_name) ? $this->File(Field::FILE_NAME) : $file_name,
            $progress
        );
    }

    // ----------- FILTERS -----------

    public function is_command(...$commands): bool
    {
        $text = $this->text();
        if (!$text) {
            return false;
        }

        if (empty($commands)) {
            return str_starts_with($text, "/");
        }

        foreach ($commands as $cmd) {
            if ($text === "/" . ltrim($cmd, "/")) {
                return true;
            }
        }

        return false;
    }

    public function is_button_id(...$buttons)
    {
        $btn = $this->button_id();
        return $btn ? in_array($btn, $buttons, true) : false;
    }

    public function has_reply_to()
    {
        return $this->reply_to_message_id();
    }

    public function is_file()
    {
        return $this->File() ? true : false;
    }

    public function is_user()
    {
        $chat_id = $this->chat_id();
        return $chat_id && str_starts_with($chat_id, "b");
    }
    public function is_group()
    {
        $chat_id = $this->chat_id();
        return $chat_id && str_starts_with($chat_id, "g");
    }

    public function is_channel()
    {
        $chat_id = $this->chat_id();
        return $chat_id && str_starts_with($chat_id, "c");
    }

    public function is_sticker()
    {
        return $this->sticker() ? true : false;
    }

    public function Filelocker(bool $autoDel = true, array|null $customType = null, LockType ...$types): bool
    {
        $file_name = $this->File(Field::FILE_NAME);
        if (!$file_name) {
            return false;
        }

        $ext = strtolower((string) pathinfo($file_name, PATHINFO_EXTENSION));
        if (empty($ext)) {
            $ext = "unk";
        }

        static $extMap = [
        LockType::Code => ["php", "js", "ts", "py", "java", "c", "cpp", "cs", "go", "rb", "swift", "kt", "rs", "html", "css", "json", "xml", "yml", "sh", "bat"],
        LockType::File => ["obb", "pak", "zip", "rar", "7z", "tar", "gz", "xz", "iso", "dmg", "apk", "exe", "msi", "deb", "rpm"],
        LockType::Document => ["pdf", "doc", "docx", "odt", "rtf", "txt", "md", "htm", "ppt", "pptx", "xls", "xlsx", "csv"],
        LockType::Image => ["jpg", "jpeg", "png", "webp", "svg", "bmp", "tiff", "tif", "ico", "heic", "heif"],
        LockType::Video => ["mp4", "mov", "avi", "mkv", "flv", "wmv", "webm", "mpeg", "mpg", "3gp", "m4v"],
        LockType::Music => ["mp3", "wav", "flac", "aac", "m4a", "wma", "alac", "aiff", "dsd"],
        LockType::Database => ["sql", "sqlite", "db", "accdb", "mdb", "bak"],
        LockType::Voice => ["ogg", "opus", "amr", "3ga", "m4r"],
        LockType::Font => ["ttf", "otf", "woff", "woff2", "eot"],
        LockType::Gif => ["gif", "apng"],
        LockType::Unk => ["unk"]
        ];

        static $lookup = null;
        if ($lookup === null) {
            $lookup = [];
            foreach ($extMap as $lockType => $exts) {
                foreach ($exts as $e) {
                    $lookup[$e] = $lockType;
                }
            }
        }

        if ($customType && in_array($ext, $customType, true)) {
            return $autoDel ? (bool) $this->deleteMessage() : true;
        }

        $map = $lookup[$ext] ?? LockType::Unk;

        foreach ($types as $t) {
            if ($map === $t) {
                return $autoDel ? (bool) $this->deleteMessage() : true;
            }
        }

        return false;
    }



    /**
     * User Limiter
     * @param int $limit تعداد درخواست
     * @param int $baseInterval تایم در خواست به ثانیه
     * @param int $maxExtraInterval حد اکتر تایم بلاک
     * @param mixed $incrementPerBlock تایم اضافه برای بعد هر بلاک
     * @param mixed $chat_id اطلاعات کاربر برای لیمیت دیفایت چت ایدی
     * @param string $path ادرس ذخیره سازی فایل
     * @return array|array {allowed: bool, currentInterval: int, remaining: int, sendMessage: bool}
     * allowed :  مجاز به درخواست مجدد؟
     * currentInterval : زمان بلاک بودن کاربر
     * remaining : تعداد درخواست های باقی مانده
     * sendMessage پیام بلاک ارسال شود؟
     */
    public function Limiter(
        int $limit = 10,                   // تعداد درخواست
        int $baseInterval = 60,           // تایم در خواست به ثانیه
        ?int $incrementPerBlock = null,  // تایم اضافه برای بعد هر بلاک
        int $maxExtraInterval = 600,    // حد اکتر تایم بلاک
        ?string $chat_id = null,       // اطلاعات کاربر برای لیمیت دیفایت چت ایدی
        string $path = "limit.json"   // ادرس ذخیره سازی فایل
    ) {
        return (new Limiter(
            $path,
            $limit,
            $baseInterval,
            $maxExtraInterval

        ))->check(
                is_null($chat_id) || empty($chat_id) ? $this->chat_id() : $chat_id,
                $incrementPerBlock
            )
        ;
    }
}