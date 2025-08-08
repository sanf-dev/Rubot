<?php

namespace RuBot\Tools;

use RuBot\Enums\ButtonType;

class InlineKeypadBuilder
{
    private array $rows = [];

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
        $this->rows[] = [
            "buttons" => $buttons
        ];
        return $this;
    }

    public function inLineKeyPad(): string
    {
        return "inline_keypad";
    }

    public function build(): array
    {
        return [
            "inline_keypad" => [
                "rows" => $this->rows

            ]
        ];
    }
}
