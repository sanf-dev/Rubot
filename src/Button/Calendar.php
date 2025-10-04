<?php

namespace Rubot\Button;

use Rubot\Enums\Date;

class Calendar implements \JsonSerializable
{
    private array $rows = [];

    public function title(string $title): self
    {
        $this->rows["title"] = $title;
        return $this;
    }

    public function type(Date $type = Date::DatePersian): self
    {
        $this->rows["type"] = $type->value;
        return $this;
    }

    public function default_value(string $value)
    {
        $this->rows["default_value"] = $value;
        return $this;
    }

    public function min_year(string $value)
    {
        $this->rows["min_year"] = $value;
        return $this;
    }

    public function max_year(string $value)
    {
        $this->rows["max_year"] = $value;
        return $this;
    }

    public function build(): array
    {
        return [
            "button_calendar" => $this->rows
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->build();
    }
}
