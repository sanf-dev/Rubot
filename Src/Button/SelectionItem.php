<?php

namespace Rubot\Button;

use Rubot\Enums\SelectionItemType;

class SelectionItem
{
    private array $rows = [];

    public function setItem(string $text, string $image_url, SelectionItemType $type): self
    {
        $this->rows[] = [
            "text" => $text,
            "image_url" => $image_url,
            "type" => $type->value
        ];
        return $this;
    }

    public function build(): array
    {
        return $this->rows;
    }
}
