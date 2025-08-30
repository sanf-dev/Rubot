<?php

namespace Rubot\Tools;

use Rubot\Enums\ButtonType;
use Rubot\Enums\KeypadType;

class ChatKeypadBuilder
{
    private array $rows = [];
    public bool $resize_keyboard = true;
    public bool $on_time_keyboard = false;

    public static function button(string $id, string $text, ButtonType $type = ButtonType::Simple, array $extra = []): array
    {
        return array_merge([
            "id" => $id,
            "type" => $type->value,
            "button_text" => $text
        ], $extra);
    }

    public function row(array ...$buttons): self
    {
        $this->rows[] = ["buttons" => $buttons];
        return $this;
    }

    public function resizeKeyboard(bool $bool = true): self
    {
        $this->resize_keyboard = $bool;
        return $this;
    }

    public function onTimeKeyboard(bool $bool = false): self
    {
        $this->on_time_keyboard = $bool;
        return $this;
    }

    public function build(KeypadType $type = KeypadType::New): array
    {
        return [
            "chat_keypad_type" => $type->value,
            "chat_keypad" => [
                "rows" => $this->rows,
                "resize_keyboard" => $this->resize_keyboard,
                "on_time_keyboard" => $this->on_time_keyboard
            ]
        ];
    }
}
