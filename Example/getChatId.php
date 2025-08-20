<?php

require "vendor/autoload.php";


use RuBot\Bot;

use RuBot\Tools\{
    Message,
    InlineKeypadBuilder,
    FilterHelper

};
use RuBot\Enums\{
    Filter,
    Field
};


$bot = new Bot("BOT-TOKEN");

$btn = (new InlineKeypadBuilder())
    ->row(
        InlineKeypadBuilder::button("get_id", "get my info")
    )->build()
;

$run = function (Message $update) use ($btn) {
    if ($update->filter([Filter::is_button_id, Filter::is_user, Filter::is_forward])) {
        if ($update->filter(Filter::is_command))
            $update->reply("hello User", $btn);

        $sender_id = $update->sender_id();

        if ($update->filter(Filter::is_forward)) {
            $chat_id = $update->forwarded()->ParamField(Field::FORWARD_FROM_CHAT_ID) ?? $update->forwarded()->ParamField(Field::FORWARD_FROM_SENDER_ID);
            echo $update->forwarded();
        } else if ($update->filter(FilterHelper::Button_Id("get_id")))
            $chat_id = $update->chat_id();
        else
            $chat_id = $update->chat_id();

        $data = "chat id : $chat_id\nsender id : $sender_id";
        $update->reply($data);
    }
};

// $bot->onUpdate([], $run); // use Long-Polling
// $bot->onMessage($run); // use endpoint (Web Hook)