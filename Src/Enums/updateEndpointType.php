<?php

declare(strict_types=1);

namespace Rubot\Enums;

enum updateEndpointType: string
{
    case ReceiveUpdate = "ReceiveUpdate";
    case ReceiveInlineMessage = "ReceiveInlineMessage";
    case ReceiveQuery = "ReceiveQuery";
    case GetSelectionItem = "GetSelectionItem";
    case SearchSelectionItems = "SearchSelectionItems";
}