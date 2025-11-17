<?php

declare(strict_types=1);

namespace Rubot\Enums;

enum ParseMode
{
    case Markdown;
    case HTML;
    case Auto;
}