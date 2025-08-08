<p align="center"><b>RoBot | Rubika Bot API Library</b></p>

## Rubika Bot Api

A simple library for creating Rubika bots,
officially and legally connected to the official Rubika Bot APIs:
[botApi](https://rubika.ir/botapi/)

To obtain a token, use the official Rubika bot:
[@BotFather](https://rubika.ir/botfather)

> V 1.0.0  
> PHP 8.1+

# Installation

```bash
composer require sanf/rubot
```

## 🛠 Basic Structure

```php
require "vendor/autoload.php";

use RuBot\Bot;
use RuBot\Tools\Message;

$bot = new Bot("BOT_TOKEN");

$run = function (Message $update) use ($bot) {
    $update->reply("Hello, user");
};
```

---

## 🎛 Sending Messages

```php
$bot->sendMessage("TEXT", "CHAT_ID", "REPLY_MESSAGE_ID", ["OPTIONS"]);
```

- `TEXT` – `string` → The text message you want to send.
- `CHAT_ID` – `string` → The target chat ID.
- `REPLY_MESSAGE_ID` – `string` → The message ID to reply to (optional).
- `OPTIONS` – `array` → Additional options:

  - `disable_notification`: `bool`
  - `inline_keypad`: `array`
  - `chat_keypad`: `array`
  - `chat_keypad_type`: `string`

_Example options:_

```json
{
  "disable_notification": false,
  "inline_keypad": {},
  "chat_keypad": {},
  "chat_keypad_type": "New"
}
```

### Inline Keyboard Message

```php
use RuBot\Tools\InlineKeypadBuilder;
use RuBot\Enums\ButtonType;

$keypad = (new InlineKeypadBuilder())
    ->row(
        InlineKeypadBuilder::button("BTN_ID", "TEXT", ButtonType::Simple)
        // ...
    )->build();

$bot->sendMessage("YOUR_TEXT", "CHAT_ID", "REPLY_MESSAGE_ID", $keypad);
```

### Chat Keyboard

```php
use RuBot\Tools\ChatKeypadBuilder;
use RuBot\Enums\ButtonType;

$keypad = (new ChatKeypadBuilder)
    ->row(
        ChatKeypadBuilder::button("BTN_ID", "TEXT", ButtonType::Simple)
        // ...
    )->build();

$bot->sendMessage("YOUR_TEXT", "CHAT_ID", "REPLY_MESSAGE_ID", $keypad);
```

### ارسال پیام

---

## 🔍 Message Filtering

```php
use RuBot\Enums\Filter;
use RuBot\Tools\FilterHelper as When;

if ($update->filter(When::ButtonID("BTN_ID"))) {
    $update->reply("Button clicked.");
}

if ($update->filter(When::Command("start"))) {
    $update->reply("Welcome.");
}
```

---

## ⚙ Security Key Setup

```php
$bot->SecretKey = "my_bot_110";

if ($bot->checkSecretKey()) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $bot->sendMessage("Attempting unauthorized access from $ip", "ADMIN_CHAT_ID");
    $secret = $bot->setSecretKey(true);
}
```

**Webhook URL**:
`https://example.com?key=my_bot_110`

---

# Bot Methods

### Sending Messages

| Method           | Description                                                 | Output |
| ---------------- | ----------------------------------------------------------- | ------ |
| `sendMessage`    | Send a text message with optional keyboard or extra options | Array  |
| `sendFile`       | Send a file to a chat                                       | Array  |
| `sendPoll`       | Send a poll to the chat                                     | Array  |
| `sendLocation`   | Send a temporary location                                   | Array  |
| `sendContact`    | Send a contact (phone number & name)                        | Array  |
| `forwardMessage` | Forward a message from one chat to another                  | Array  |

### Editing & Deleting

| Method           | Description                                   | Output |
| ---------------- | --------------------------------------------- | ------ |
| `editMessage`    | Edit the content of a previously sent message | Bool   |
| `deleteMessage`  | Delete a message from a chat                  | Bool   |
| `editChatKeypad` | Edit an existing chat keyboard                | Bool   |

### File Handling

| Method            | Description                            | Output |
| ----------------- | -------------------------------------- | ------ |
| `getFile`         | Get file information using its file ID | Array  |
| `uploadMediaFile` | Upload a media file to Rubika servers  | Array  |

### Chat & Bot Info

| Method        | Description                                    | Output |
| ------------- | ---------------------------------------------- | ------ |
| `getMe`       | Get bot account details                        | Array  |
| `getChat`     | Get chat information (user, group, or channel) | Array  |
| `getUpdates`  | Receive incoming updates (polling method)      | Array  |
| `setCommands` | Set bot commands                               | Array  |

### Webhook & Security

| Method           | Description                                                        | Output |
| ---------------- | ------------------------------------------------------------------ | ------ |
| `setSecretKey`   | Set a security key to protect webhook access                       | Bool   |
| `checkSecretKey` | Check if the incoming webhook request contains the correct key     | Bool   |
| `WebHook`        | Manually configure a webhook                                       | Array  |
| `setWebHook`     | Automatically set webhook to a given URL                           | Array  |
| `onMessage`      | Handle incoming messages from the webhook with a callback function | Void   |

---

# Message Methods

### Reading Message Data

| Method           | Description                                | Output |
| ---------------- | ------------------------------------------ | ------ |
| `text`           | Get the text content of the message        | string |
| `chat_id`        | Get the chat ID where the message was sent | string |
| `message_id`     | Get the unique ID of the message           | string |
| `getTime`        | Get the time when the message was sent     | string |
| `is_edit`        | Check if the message was edited            | bool   |
| `sender_type`    | Get the sender type (user, group, channel) | string |
| `sender_id`      | Get the sender's unique ID                 | string |
| `button_id`      | Get the ID of the clicked button (if any)  | string |
| `getFile`        | Get information about an attached file     | array  |
| `location`       | Get shared location details                | array  |
| `contact`        | Get shared contact details                 | array  |
| `forwarded`      | Get details of a forwarded message         | array  |
| `start_id`       | Get the bot start payload (start ID)       | string |
| `inline_message` | Get inline message content                 | array  |
| `rawData`        | Get all raw data received from the update  | array  |

### Reply & Action Methods

| Method             | Description                                                                | Output |
| ------------------ | -------------------------------------------------------------------------- | ------ |
| `reply`            | Send a text message as a reply to the current message                      | array  |
| `sendPoll`         | Send a poll as a reply                                                     | array  |
| `sendLocation`     | Send a temporary location as a reply                                       | array  |
| `sendContact`      | Send a contact as a reply                                                  | array  |
| `forwardMessage`   | Forward the current message to another chat                                | array  |
| `editChatKeypad`   | Edit the current chat keyboard                                             | Array  |
| `removeChatKeyPad` | Remove the current chat keyboard                                           | Array  |
| `filter`           | Filter incoming messages based on conditions (e.g., commands, button file) | bool   |

---

## 📜 License

MIT © [Sanf-Dev](https://github.com/sanf-dev/)
