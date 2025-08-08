<?php


ini_set("display_errors", 0);
ini_set("log_errors", 1);
ini_set("error_log", "/php_errors.log");
error_reporting(E_ALL);

require "vendor/autoload.php";


use RuBot\Bot;
use RuBot\Tools\{
    Message,
    InlineKeypadBuilder,
    ChatKeypadBuilder,
    FilterHelper as When
};
use RuBot\Enums\{
    Filter,
    ButtonType,
    Type_keypad,
    Type_line,
    LocationType,
    SelectionItemType
};
use RuBot\Button\{
    Location,
    NumberPicker,
    Selection,
    SelectionItem,
    StringPicker,
    TextBox
};

$bot = new Bot("BOT_TOKEN");

/*
$bot->SecretKey = "SANFAPIBOT";
if ($bot->checkSecretKey()) {
    $bot->sendMessage("user", "b0IVjtd0Epv08ca731765049151bc171");
    $secret = $bot->setSecretKey(true);
}
*/

$locBTN = (new Location())
    ->title("Select Location")
    ->type(LocationType::Picker)
;

$NumPikBTN = (new NumberPicker())
    ->title("your age")
    ->min_value(1380)
    ->max_value(1405)
    ->default_value(1385)
;

$selectITEM = (new SelectionItem())
    ->setItem("1", "", SelectionItemType::TextOnly)
    ->setItem("2", "", SelectionItemType::TextOnly)
    ->setItem("3", "", SelectionItemType::TextOnly)
    ->setItem("4", "", SelectionItemType::TextOnly)
    ->setItem("5", "", SelectionItemType::TextOnly)
    ->setItem("6", "", SelectionItemType::TextOnly)
;
$selctBTN = (new Selection())
    ->title("select")
    ->columns_count(2)
    ->is_multi_selection(true)
    ->items($selectITEM->build())
    ->search_type()
    ->get_type()
    ->selection_id("2")
;

$strBTN = (new StringPicker())
    ->title("select item")
    ->items(["s1", "s2", "s3", "s4", "s5", "s6"])
    ->default_value("s3")
;

$textBoxBTN = (new TextBox())
    ->title("whats your name?")
    ->place_holder("Sanf Api Bot")
    ->type_keypad(Type_keypad::String)
    ->type_line(Type_line::SingleLine)
;

$inlineBTN = (new InlineKeypadBuilder())
    ->row(
        InlineKeypadBuilder::button("loc", "btn location", ButtonType::Location, $locBTN->build()),
        InlineKeypadBuilder::button("num", "btn number picker", ButtonType::NumberPicker, $NumPikBTN->build())

    )
    ->row(
        InlineKeypadBuilder::button("selection", "btn selection", ButtonType::Selection, $selctBTN->build()),
        InlineKeypadBuilder::button("str", "btn string picker", ButtonType::StringPicker, $strBTN->build()),
        InlineKeypadBuilder::button("textbox", "btn textBox", ButtonType::Textbox, $textBoxBTN->build())

    )
;

$ChatKeyPadBTN = (new ChatKeypadBuilder())
    ->row(
        ChatKeypadBuilder::button("loc", "btn location", ButtonType::Location, $locBTN->build()),
    )
    ->row(
        ChatKeypadBuilder::button("num", "btn number picker", ButtonType::NumberPicker, $NumPikBTN->build()),
        ChatKeypadBuilder::button("selection", "btn selection", ButtonType::Selection, $selctBTN->build()),
    )->row(
        ChatKeypadBuilder::button("str", "btn string picker", ButtonType::StringPicker, $strBTN->build()),
        ChatKeypadBuilder::button("textbox", "btn textBox", ButtonType::Textbox, $textBoxBTN->build()),
    )
    ->row(
        ChatKeypadBuilder::button("remove", "remove Chat keyPad", ButtonType::Simple),
    )
    ->build()

;

$bot->setCommands([
    [
        "command" => "inline",
        "description" => "inline keypad"
    ],
    [
        "command" => "ChatKey",
        "description" => "Chat keypad"
    ]
]);

shuffle($ChatKeyPadBTN["chat_keypad"]["rows"]);
shuffle($inlineBTN["inline_keypad"]["rows"]);


$run = function (Message $update) use ($bot, $inlineBTN, $ChatKeyPadBTN) {
    $text = $update->text();
    file_put_contents("data.json", json_encode($update->rawData()));

    if ($update->filter(When::Command("start"))) {
        $update->reply("hello , welcome to bot\ngithub : https://github.com/sanf-dev/\nchannel : @sanfapi");
    }
    if ($update->filter(When::Command("inline"))) {
        $update->reply("github : https://github.com/sanf-dev/", $inlineBTN->build());
    }
    if ($update->filter(When::Command("ChatKey"))) {
        $update->reply("github : https://github.com/sanf-dev/", $ChatKeyPadBTN);
    }

    if ($update->filter(When::ButtonID("loc"))) {
        $update->reply(json_encode($update->location()));
    }
    if ($update->filter(When::ButtonID("num"))) {
        $update->reply($text ?? "number picker");
    }
    if ($update->filter(When::ButtonID("selection"))) {
        $update->reply($text ?? "selection");
    }
    if ($update->filter(When::ButtonID("str"))) {
        $update->reply($text ?? "string picker");
    }
    if ($update->filter(When::ButtonID("textbox"))) {
        $update->reply($text ?? "textBox");
    }
    if ($update->filter(When::ButtonID("remove"))) {
        $update->removeChatKeyPad();
        $update->reply("ok");
    }
};

$bot->onMessage($run);