<?php

require "vendor/autoload.php";

// -----------------------------
// Import Namespaces & Classes
// -----------------------------
use RuBot\Bot;
use RuBot\Button\{
    Link,
};
use RuBot\Tools\{
    Message,
    ChatKeypadBuilder,
    FilterHelper as When
};
use RuBot\Enums\{
    ButtonType,
    Filter
};

// -----------------------------
// Bot Initialization
// -----------------------------
$bot = new Bot("BOT-TOKEN");

// -----------------------------
// Chat Keypad (Buttons) Setup
// -----------------------------

// Button links
$dev = (new link())->joinChannel("coder98")->build();
$channel = (new link())->joinChannel("sanfapi")->build();

// Main buttons
$btn = (new ChatKeypadBuilder())->row(
    ChatKeypadBuilder::button("dev", "Developer", ButtonType::Link, $dev)
)->row(
        ChatKeypadBuilder::button("channel", "channel", ButtonType::Link, $channel)
    )->build();

// Alternate button layout (for replies)
$btn_1 = (new ChatKeypadBuilder())->row(
    ChatKeypadBuilder::button("delChatHistory", "delete chat history")
)->row(
        ChatKeypadBuilder::button("dev", "Developer", ButtonType::Link, $dev),
        ChatKeypadBuilder::button("channel", "channel", ButtonType::Link, $channel)
    )->build();

// -----------------------------
// AI Bot Handler
// -----------------------------
$run_ai = function (Message $update) use ($bot, $btn_1) {
    $text = $update->text();
    // Ignore button presses or commands
    if (!$update->filter([Filter::is_button_id, Filter::is_command])) {

        // Call GPT API
        $gpt_res = GPT($text, $update->chat_id());

        // Reply differently for groups/channels vs private chat
        if ($update->filter(Filter::is_group) || $update->filter(Filter::is_channel)) {
            $update->reply($gpt_res);
        } else {
            $update->reply($gpt_res, $btn_1);
        }
    }
};

// -----------------------------
// Command & Button Handlers
// -----------------------------
$run_cnb = function (Message $update) use ($btn) {

    // Handle "delete chat history" button
    if ($update->filter(When::Button_Id("delChatHistory"))) {
        GPT(null, $update->chat_id(), true);
        $update->reply("History cleared");
    }

    // Handle /start command
    if ($update->filter(When::Command("start"))) {
        $update->reply("Hi there! I'm your AI assistant 🤖.\nHow can I make things easier for you today?", $btn);
    }
};


$bot->onUpdate([], $run_ai, $run_cnb);  // use Long-Polling
// $bot->onMessage($run_ai,$run_cnb); // use endpoint (Web Hook)


// -----------------------------
// GPT API Integration
// -----------------------------
function GPT(?string $prompt, string $chat_id, bool $clear = false)
{
    $postData = [
        'chat_id' => $chat_id,
        'clear' => $clear,
        'prompt' => $prompt
    ];

    $ch = curl_init("https://api.artusha.ir/gpt/");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if (curl_errno($ch)) {
        return "error: " . curl_error($ch);
    }

    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (is_array($response) && isset($response["status"]) && $response["status"] == "OK") {
        return $response["response"] ?? json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        return json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
