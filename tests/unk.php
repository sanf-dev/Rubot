<?php

use Rubot\Bot;
use Rubot\Enums\{
    ParseMode,
    Field
};
use Rubot\Tools\Message;
require '../vendor/autoload.php';

$bot = new Bot("TOKEN", ParseMode::Markdown);

const ADMINS = ["u0IVjtd0d5550ccf5d7b03f15cc05eed"];

$run = function (Message $update) use ($bot) {
    $sender_id = $update->sender_id();
    if (!in_array($sender_id, ADMINS))
        return;

    // ------------ data
    $text = $update->text("");
    $reply = $update->reply_to_message_id("");
    $is_file = $update->is_file();
    $metadata = $update->metadata();

    echo $text . PHP_EOL;

    $ot = [];
    if (!empty($metadata))
        $ot["metadata"] = $metadata;
    if (!empty($reply))
        $ot["reply_to_message_id"] = $reply;

    if ($is_file) {
        $file_name = $update->File(Field::FILE_NAME);
        $file_path = $bot->download($update->File(Field::FILE_ID), $file_name);
        $update->replyFile($text, $file_path, $file_name, null, $ot);
        unlink($file_path);
    } elseif (!empty($text)) {
        $update->reply($text, $ot);
    }
    $update->deleteMessage();
};
$bot->onUpdate([], $run);
