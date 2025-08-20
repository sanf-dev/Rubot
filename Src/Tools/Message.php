<?php

namespace RuBot\Tools;

use RuBot\Tools\{
    Param,
    NullParam
};
use RuBot\Enums\{
    Keypad,
    Filter
};

class Message
{
    private array $data;
    private object $bot;

    public function __construct(array $update, object $bot)
    {
        $this->data = $update;
        $this->bot = $bot;
    }

    private function getParam(array $paths, $default = null)
    {
        foreach ($paths as $path) {
            $temp = $this->data;
            foreach ($path as $key) {
                if (!isset($temp[$key])) {
                    continue 2;
                }
                $temp = $temp[$key];
            }
            return $temp;
        }
        return $default;
    }

    public function text(): ?string
    {
        return $this->getParam([
            ["update", "new_message", "text"],
            ["update", "updated_message", "text"],
            ["inline_message", "text"]
        ]);
    }

    public function chat_id(): string|int|null
    {
        return $this->getParam([
            ["update", "chat_id"],
            ["inline_message", "chat_id"],
            ["update", "new_message", "forwarded_from", "from_chat_id"]
        ]);
    }

    public function message_id(): string|int|null
    {
        return $this->getParam([
            ["update", "new_message", "message_id"],
            ["update", "updated_message", "message_id"],
            ["inline_message", "message_id"]
        ]);
    }

    public function reply_to_message_id(): string|int|null
    {
        return $this->getParam([
            ["update", "new_message", "reply_to_message_id"],
            ["update", "updated_message", "reply_to_message_id"],
            ["inline_message", "reply_to_message_id"]
        ]);
    }

    public function getTime(): int|string|null
    {
        return $this->getParam([
            ["update", "new_message", "time"],
            ["update", "updated_message", "time"]
        ]);
    }

    public function is_edit(): ?bool
    {
        return $this->getParam([
            ["update", "new_message", "is_edited"],
            ["update", "updated_message", "is_edited"]
        ], false);
    }

    public function sender_type(): ?string
    {
        return $this->getParam([
            ["update", "new_message", "sender_type"],
            ["update", "updated_message", "sender_type"]
        ]);
    }


    public function sender_id(): string|int|null
    {
        return $this->getParam([
            ["update", "new_message", "sender_id"],
            ["update", "updated_message", "sender_id"],
            ["inline_message", "sender_id"]
        ]);
    }

    public function button_id(): string|int|null
    {
        return $this->getParam([
            ["update", "new_message", "aux_data", "button_id"],
            ["update", "updated_message", "aux_data", "button_id"],
            ["inline_message", "aux_data", "button_id"]
        ]);
    }

    public function getFile(): ?Param
    {
        $data = $this->getParam([
            ["update", "new_message", "file"],
            ["update", "updated_message", "file"],
            ["inline_message", "file"]
        ]);
        return $data ? new Param($data) : new NullParam();
    }


    public function location(): ?Param
    {
        $data = $this->getParam([
            ["update", "new_message", "location"],
            ["update", "updated_message", "location"],
            ["inline_message", "location"]
        ]);
        return $data ? new Param($data) : new NullParam();
    }

    public function contact(): ?Param
    {
        $data = $this->getParam([
            ["update", "new_message", "contact_message"],
            ["update", "updated_message", "contact_message"],
            ["inline_message", "contact_message"]
        ]);
        return $data ? new Param($data) : new NullParam();
    }

    public function forwarded(): ?Param
    {
        $data = $this->getParam([
            ["update", "new_message", "forwarded_from"],
            ["update", "new_message", "forwarded_no_link"]
        ]);
        return $data ? new Param($data) : new NullParam();
    }

    public function start_id(): int|string|null
    {
        return $this->getParam([
            ["update", "new_message", "aux_data", "start_id"],
            ["update", "updated_message", "aux_data", "start_id"],
            ["inline_message", "aux_data", "start_id"]
        ]);
    }

    public function inline_message(): ?array
    {
        return $this->getParam([
            ["inline_message"]
        ], []);
    }

    public function rawData(): array
    {
        return $this->data;
    }

    public function reply(string $text, array $options = [])
    {
        return $this->bot->sendMessage($text, $this->chat_id(), $this->message_id(), $options);
    }

    public function replyFile(string $text, string $path, array $options = [])
    {
        return $this->bot->sendFile($text, $this->chat_id(), $path, $this->message_id(), $options);
    }

    public function sendPoll(string $question, array $options)
    {
        return $this->bot->sendPoll($this->chat_id(), $question, $options, $this->message_id());
    }

    public function sendLocation(string $latitude, string $longitude, array $options = [])
    {
        return $this->bot->sendLocation($this->chat_id(), $latitude, $longitude, $this->message_id(), $options);
    }

    public function sendContact(string $phone, string $first_name, string $last_name, array $options = [])
    {
        return $this->bot->sendContact($this->chat_id(), $phone, $first_name, $last_name, $this->message_id(), $options);
    }

    public function forwardMessage(string $message_id, string $from_chat_id, array $options = [])
    {
        return $this->bot->forwardMessage($this->chat_id(), $message_id, $from_chat_id, $options);
    }

    public function editChatKeypad(Keypad $type = Keypad::New , array $options = [])
    {
        return $this->bot->editChatKeypad($this->chat_id(), $type, $options);
    }

    public function removeChatKeyPad()
    {
        return $this->bot->editChatKeypad($this->chat_id(), Keypad::Remove);
    }

    public function filter(Filter|array|callable $filter, string $mode = "or"): bool
    {
        if (is_callable($filter)) {
            return $filter($this);
        }
        if (is_array($filter)) {
            if ($mode === "&&")
                $mode = "and";
            if ($mode === "||")
                $mode = "or";

            if ($mode === "or") {
                foreach ($filter as $f) {
                    if ($this->filter($f)) {
                        return true;
                    }
                }
                return false;
            }

            if ($mode === "and") {
                foreach ($filter as $f) {
                    if (!$this->filter($f)) {
                        return false;
                    }
                }
                return true;
            }

            if ($mode === "xor") {
                $results = array_map(fn($f) => $this->filter($f), $filter);
                return count(array_filter($results)) === 1;
            }

            throw new \InvalidArgumentException("Invalid filter mode: [and,or,xor,||,&&]");
        }

        $type = $filter->value;

        return match ($type) {
            "has_reply_to" => !is_null($this->reply_to_message_id()),
            "is_command" => str_starts_with($this->text() ?? "", "/"),
            "is_buttonId" => !is_null($this->button_id()),
            "is_edited" => $this->is_edit(),
            "is_file" => !$this->getFile()->is_empty(),
            "is_location" => !$this->location()->is_empty(),
            "is_contact" => !$this->contact()->is_empty(),
            "is_forward" => !$this->forwarded()->is_empty(),
            "is_user" => !is_null($this->sender_type()) && substr($this->chat_id(), 0, 1) == "b",
            "is_group" => !is_null($this->chat_id()) && substr($this->chat_id(), 0, 1) == "g",
            "is_channel" => !is_null($this->chat_id()) && substr($this->chat_id(), 0, 1) == "c",
            default => false,
        };
    }
}
