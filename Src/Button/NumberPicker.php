<?php

namespace Rubot\Button;


class NumberPicker
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

    public function min_value(int $value): self
    {
        $this->rows["min_value"] = (string) $value;
        return $this;
    }

    public function max_value(int $value): self
    {
        $this->rows["max_value"] = (string) $value;
        return $this;
    }

    public function build(): array
    {
        return [
            "button_number_picker" => $this->rows
        ];
    }
}
