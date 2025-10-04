<?php

namespace Rubot\Button;

use Rubot\Enums\Type_line;
use Rubot\Enums\Type_keypad;

class TextBox implements \JsonSerializable
{
    private array $rows = [];

    public function title(string $title): self
    {
        $this->rows["title"] = $title;
        return $this;
    }

    public function default_value(string $value)
    {
        $this->rows["default_value"] = $value;
        return $this;
    }

    public function place_holder(string $place_holder)
    {
        $this->rows["place_holder"] = $place_holder;
        return $this;
    }

    public function type_keypad(Type_keypad $type): self
    {
        $this->rows["type_keypad"] = $type->value;
        return $this;
    }

    public function type_line(Type_line $type): self
    {
        $this->rows["type_line"] = $type->value;
        return $this;
    }

    public function build(): array
    {
        return [
            "button_textbox" => $this->rows
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->build();
    }
}
