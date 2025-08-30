<?php

declare(strict_types=1);

namespace Rubot\Enums;

enum ButtonType: string
{
    case Simple = "Simple";
    case Selection = "Selection";
    case Calendar = "Calendar";
    case NumberPicker = "NumberPicker";
    case StringPicker = "StringPicker";
    case Location = "Location";
    case Payment = "Payment";

    case CameraImage = "CameraImage";
    case CameraVideo = "CameraVideo";
    case GalleryImage = "GalleryImage";
    case GalleryVideo = "GalleryVideo";

    case File = "File";
    case Audio = "Audio";
    case RecordAudio = "RecordAudio";

    case MyPhoneNumber = "MyPhoneNumber";
    case MyLocation = "MyLocation";

    case Textbox = "Textbox";
    case Link = "Link";

    case AskMyPhoneNumber = "AskMyPhoneNumber";
    case AskLocation = "AskLocation";

    case Barcode = "Barcode";
}
