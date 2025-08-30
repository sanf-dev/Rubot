<?php

declare(strict_types=1);

namespace Rubot\Enums;

enum LinkType: string
{
    case joinchannel = "joinchannel";
    case url = "url";
}
