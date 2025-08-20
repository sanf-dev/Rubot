<?php

namespace RuBot\Enums;

enum Filter: string
{
    case is_command = "is_command";
    case is_button_id = "is_buttonId";
    case is_edited = "is_edited";
    case is_file = "is_file";
    case is_location = "is_location";
    case is_contact = "is_contact";
    case is_forward = "is_forward";
    case is_user = "is_user";
    case is_group = "is_group";
    case is_channel = "is_channel";
    case has_reply_to = "has_reply_to";
}
