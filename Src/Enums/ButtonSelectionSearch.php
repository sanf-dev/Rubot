<?php

declare(strict_types=1);

namespace Rubot\Enums;

enum ButtonSelectionSearch: string
{
    case None = "None";
    case Local = "Local";
    case Api = "Api";
}