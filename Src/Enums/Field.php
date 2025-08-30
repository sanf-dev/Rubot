<?php

declare(strict_types=1);

namespace Rubot\Enums;

enum Field: string
{
    /**
     * get file paaram
     */
    case FILE_ID = 'file_id';
    case FILE_NAME = 'file_name';
    case SIZE = 'size';

    /**
     * get forward param 
     */
    case FORWARD_TYPE = "type_from";
    case FORWARD_MESSAGE_ID = "message_id";
    case FORWARD_FROM_SENDER_ID = "from_sender_id";
    case FORWARD_FROM_CHAT_ID = "from_chat_id";

    /**
     * get location param
     */
    case LOCATION_LONGITUDE = "longitude";
    case LOCATION_LATITUDE = "latitude";

    /**
     * get contact param
     */
    case CONTACT_PHONE = "phone_number";
    case CONTACT_FNAME = "first_name";
    case CONTACT_LNAME = "last_name";

    public static function fileFields(): array
    {
        return [
            self::FILE_ID,
            self::FILE_NAME,
            self::SIZE,
        ];
    }

    public static function forwardFields(): array
    {
        return [
            self::FORWARD_TYPE,
            self::FORWARD_MESSAGE_ID,
            self::FORWARD_FROM_SENDER_ID,
            self::FORWARD_FROM_CHAT_ID,
        ];
    }

    public static function locationFields(): array
    {
        return [
            self::LOCATION_LONGITUDE,
            self::LOCATION_LATITUDE,
        ];
    }

    public static function contactFields(): array
    {
        return [
            self::CONTACT_PHONE,
            self::CONTACT_FNAME,
            self::CONTACT_LNAME,
        ];
    }
}

