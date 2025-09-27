<?php

namespace Rubot\Enums;
enum LockType
{
    case Image;
    case Music;
    case Voice;
    case Video;
    case Gif;
    case Document;
    case File;
    case Font;
    case Code;
    case Database;
    case Unk;
}