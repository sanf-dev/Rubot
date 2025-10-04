<?php

require_once "../vendor/autoload.php";

use Rubot\Bot;
use Rubot\Tools\Message;

$bot = new Bot("Token");

$handle = function (Message $update) {
    $text = $update->text();
    if ($text == "Rubot") {
        $update->reply("im hear🙋‍♂️");
    } elseif(in_array($text,["hi","hello","Hello","Hi"])){
        $update->reply("hello wllcome , im Rubot");
    }
};

$bot->onMessage($handle);