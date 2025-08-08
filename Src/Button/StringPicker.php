<?php

namespace RuBot\Button;


class StringPicker
{
    private array $rows = [];

    public function items(array $items): self
    {
        $this->rows["items"] = $items;
        return $this;
    }

    public function default_value(string $value)
    {
        $this->rows["default_value"] = $value;
        return $this;
    }

    public function title(string $title): self
    {
        $this->rows["title"] = $title;
        return $this;
    }

    public function build(): array
    {
        return [
            "button_string_picker" => $this->rows
        ];
    }
}
