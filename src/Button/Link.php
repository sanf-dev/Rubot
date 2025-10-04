<?php

namespace Rubot\Button;

use Rubot\Enums\LinkType;


class Link implements \JsonSerializable
{
    private array $rows = [];

    private function type(LinkType $type = LinkType::url): self
    {
        $this->rows["type"] = $type->value;
        return $this;
    }

    public function joinChannel(string $username, bool $ask_join = true)
    {
        $this->type(LinkType::joinchannel);
        $this->rows["joinchannel_data"] = [
            "username" => $username,
            "ask_join" => $ask_join
        ];
        return $this;
    }

    public function Link(string $link)
    {
        $this->type(LinkType::url);
        $this->rows["link_url"] = $link;
        return $this;
    }

    public function build(): array
    {
        return [
            "button_link" => $this->rows
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->build();
    }
}