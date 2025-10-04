<?php

require "../vendor/autoload.php";

use Rubot\Bot;
use Rubot\Tools\{
    Security,
    Message,
};
use Rubot\Enums\{
    LockType
};


Security::create("Ali", ["log" => false, "key" => "app"])->set();


$bot = new Bot("Token");


$commends = function (Message $update) {

    print_r($update->rawData());
    if ($update->has_emoji()) {
        $update->reply("emoji " . json_encode($update->has_emoji(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
    }

    if ($update->has_link()) {
        $update->reply("link " . json_encode($update->has_link(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
    }

    if ($update->has_mention()) {
        $update->reply("mention " . json_encode($update->has_mention(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
    }

    if ($update->has_hashtag()) {
        $update->reply("hashtag " . json_encode($update->has_hashtag(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
    }

    if ($update->contains_number()) {
        $update->reply("number " . json_encode($update->contains_number(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
    }

    if ($update->contains_email()) {
        $update->reply("email " . json_encode($update->contains_email(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
    }

    if ($update->contains_phone()) {
        $update->reply("phone " . json_encode($update->contains_phone(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
    }

    if ($update->contains_words(["iran", "work"])) {
        $update->reply("word " . json_encode($update->contains_words(["iran", "work"]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
    }

    if ($update->contains_words_all(["iran", "work"])) {
        $update->reply("word all " . json_encode($update->contains_words_all(["iran", "work"]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
    }

    if ($update->contains_date()) {
        $update->reply("date " . json_encode($update->contains_date(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
    }

    if ($update->contains_time()) {
        $update->reply("time " . json_encode($update->contains_time(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
    }

    if ($update->contains_code()) {
        $update->reply("code " . json_encode($update->contains_code(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
    }

    if ($update->contains_repeated_chars()) {
        $update->reply("flood " . json_encode($update->contains_repeated_chars(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_UNICODE));
    }


};

$bot->onUpdate([], $commends);