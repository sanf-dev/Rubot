<?php

namespace Rubot\Tools;

use Rubot\Enums\{
    KeypadType,
    Field
};


class Message
{
    private array $data;
    private readonly object $bot;

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
        return $this->data["text"] ?? false;
    }

    public function chat_id()
    {
        return $this->data["chat_id"] ?? $this->data["forwarded_from"] ?? false;
    }

    public function message_id()
    {
        return $this->data["message_id"] ?? null;
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
            $this->message_id(),
            false,
            $other
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
            $this->message_id(),
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
            $this->message_id(),
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
            $this->message_id(),
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
            $this->message_id(),
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
}