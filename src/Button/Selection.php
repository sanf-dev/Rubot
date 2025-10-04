<?php

namespace Rubot\Button;

use Rubot\Enums\ButtonSelectionSearch;

class Selection implements \JsonSerializable
{
    private array $rows = [];

    public function title(string $title): self
    {
        $this->rows["title"] = $title;
        return $this;
    }

    public function columns_count(string $count): self
    {
        $this->rows["columns_count"] = $count;
        return $this;
    }

    public function is_multi_selection(bool $is_multi): self
    {
        $this->rows["is_multi_selection"] = $is_multi;
        return $this;
    }

    public function items(array $value): self
    {
        $this->rows["items"] = $value;
        return $this;
    }

    public function get_type(ButtonSelectionSearch $type = ButtonSelectionSearch::Local): self
    {
        $this->rows["get_type"] = $type->value;
        return $this;
    }

    public function search_type(ButtonSelectionSearch $type = ButtonSelectionSearch::Local): self
    {
        $this->rows["search_type"] = $type->value;
        return $this;
    }

    public function selection_id(string $id): self
    {
        $this->rows["selection_id"] = $id;
        return $this;
    }


    public function build(): array
    {
        return [
            "button_selection" => $this->rows
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->build();
    }
}
