<?php

namespace RuBot\Button;

use RuBot\Enums\LinkType;


class Link
{
    private array $rows = [];

    public function type(LinkType $type = LinkType::url): self
    {
        $this->rows["type"] = $type->value;
        if ($type === LinkType::url)
            trigger_error(
                "use Link(url)",
                E_USER_NOTICE
            );
        else
            trigger_error(
                "use joinChannel(username,ask_join:true)",
                E_USER_NOTICE
            );
        return $this;
    }

    public function joinChannel(string $username, bool $ask_join = true)
    {
        $this->rows["joinchannel_data"] = [
            "username" => $username,
            "ask_join" => $ask_join
        ];
        return $this;
    }

    public function Link(string $link)
    {
        $this->rows["link_url"] = $link;
        return $this;
    }

    public function build(): array
    {
        return [
            "button_link" => $this->rows
        ];
    }
}