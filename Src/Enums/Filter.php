<?php

namespace RuBot\Enums;

enum Filter: string
{
    case Command = "Command";
    case ButtonID = "Button_Id";
    case Edit = "Edit_Message";
    case User = "User";
    case File = "File";
    case location = "location";
    case Contact = "Contact_Message";
    case forward = "Forward";
}
