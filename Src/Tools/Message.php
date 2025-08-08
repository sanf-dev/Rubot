<?php

namespace RuBot\Tools;

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

    public function text()
    {
        $data = $this->data;
        if (isset($data["update"]["new_message"]["text"]))
            return $data["update"]["new_message"]["text"];
        if (isset($data["update"]["updated_message"]["text"]))
            return $data["update"]["updated_message"]["text"];
        if (isset($data["inline_message"]["text"]))
            return $data["inline_message"]["text"];

        return null;
    }

    public function chat_id()
    {
        $data = $this->data;
        if (isset($data["update"]["chat_id"]))
            return $data["update"]["chat_id"];
        if (isset($data["inline_message"]["chat_id"]))
            return $data["inline_message"]["chat_id"];
        if (isset($data["update"]["new_message"]["forwarded_from"]["from_chat_id"]))
            return $data["update"]["new_message"]["forwarded_from"]["from_chat_id"];
        return null;
    }

    public function message_id()
    {
        $data = $this->data;
        if (isset($data["update"]["new_message"]["message_id"]))
            return $data["update"]["new_message"]["message_id"];
        if (isset($data["update"]["updated_message"]["message_id"]))
            return $data["update"]["updated_message"]["message_id"];
        if (isset($data["inline_message"]["message_id"]))
            return $data["inline_message"]["message_id"];

        return null;
    }

    public function getTime()
    {
        $data = $this->data;
        if (isset($data["update"]["new_message"]["new_message"]))
            return $data["update"]["new_message"]["new_message"];
        if (isset($data["update"]["updated_message"]["time"]))
            return $data["update"]["updated_message"]["time"];

        return null;
    }

    public function is_edit()
    {
        $data = $this->data;
        if (isset($data["update"]["new_message"]["is_edited"]))
            return $data["update"]["new_message"]["is_edited"];
        if (isset($data["update"]["updated_message"]["is_edited"]))
            return $data["update"]["updated_message"]["is_edited"];

        return false;
    }

    public function sender_type()
    {
        $data = $this->data;
        if (isset($data["update"]["new_message"]["sender_type"]))
            return $data["update"]["new_message"]["sender_type"];
        if (isset($data["update"]["updated_message"]["sender_type"]))
            return $data["update"]["updated_message"]["sender_type"];

        return null;
    }


    public function sender_id()
    {
        $data = $this->data;
        if (isset($data["update"]["new_message"]["sender_id"]))
            return $data["update"]["new_message"]["sender_id"];
        if (isset($data["update"]["updated_message"]["sender_id"]))
            return $data["update"]["updated_message"]["sender_id"];
        if (isset($data["inline_message"]["sender_id"]))
            return $data["inline_message"]["sender_id"];

        return null;
    }

    public function button_id()
    {
        $data = $this->data;
        if (isset($data["update"]["new_message"]["aux_data"]["button_id"]))
            return $data["update"]["new_message"]["aux_data"]["button_id"];
        if (isset($data["update"]["updated_message"]["aux_data"]["button_id"]))
            return $data["update"]["updated_message"]["aux_data"]["button_id"];
        if (isset($data["inline_message"]["aux_data"]["button_id"]))
            return $data["inline_message"]["aux_data"]["button_id"];

        return null;
    }

    public function getFile()
    {
        $data = $this->data;
        if (isset($data["update"]["new_message"]["file"]))
            return $data["update"]["new_message"]["file"];
        if (isset($data["update"]["updated_message"]["file"]))
            return $data["update"]["updated_message"]["file"];
        if (isset($data["inline_message"]["file"]))
            return $data["inline_message"]["file"];

        return null;
    }

    public function location()
    {
        $data = $this->data;
        if (isset($data["update"]["new_message"]["location"]))
            return $data["update"]["new_message"]["location"];
        if (isset($data["update"]["updated_message"]["location"]))
            return $data["update"]["updated_message"]["location"];
        if (isset($data["inline_message"]["location"]))
            return $data["inline_message"]["location"];

        return null;
    }

    public function contact()
    {
        $data = $this->data;
        if (isset($data["update"]["new_message"]["contact_message"]))
            return $data["update"]["new_message"]["contact_message"];
        if (isset($data["update"]["updated_message"]["contact_message"]))
            return $data["update"]["updated_message"]["contact_message"];
        if (isset($data["inline_message"]["contact_message"]))
            return $data["inline_message"]["contact_message"];

        return null;
    }

    public function forwarded()
    {
        $data = $this->data;
        if (isset($data["update"]["new_message"]["forwarded_from"]))
            return $data["update"]["new_message"]["forwarded_from"];
        return null;
    }

    public function start_id()
    {
        $data = $this->data;
        if (isset($data["update"]["new_message"]["aux_data"]["start_id"]))
            return $data["update"]["new_message"]["aux_data"]["start_id"];
        if (isset($data["update"]["updated_message"]["aux_data"]["start_id"]))
            return $data["update"]["updated_message"]["aux_data"]["start_id"];
        if (isset($data["inline_message"]["aux_data"]["start_id"]))
            return $data["inline_message"]["aux_data"]["start_id"];

        return null;
    }

    public function inline_message()
    {
        $data = $this->data;
        if (isset($data["inline_message"]))
            return $data["inline_message"];
        return null;
    }

    public function rawData()
    {
        return $this->data;
    }

    public function reply(string $text, array $options = [])
    {
        return $this->bot->sendMessage($text, $this->chat_id(), $this->message_id(), $options);
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

    public function filter(Filter|callable $filter): bool
    {
        if (is_callable($filter)) {
            return $filter($this);
        }

        $type = $filter->value;

        return match ($type) {
            "Command" => str_starts_with($this->text() ?? '', "/"),
            "Button_Id" => !is_null($this->button_id()),
            "Edit_Message" => $this->is_edit(),
            "User" => !is_null($this->sender_type()),
            "File" => !is_null($this->getFile()),
            "location" => !is_null($this->location()),
            "Contact_Message" => !is_null($this->contact()),
            "Forward" => !is_null($this->forwarded()),
            default => false,
        };
    }
}
