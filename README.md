<div align="center">
  <a href="https://github.com/sanf-dev/Rubot">
    <img src="https://uploadkon.ir/uploads/80b230_25rubot-logo.png" width="128" alt="logo - Rubot">
  </a>
  <h1>Rubot</h1>
  <h3>PHP framework for building Rubika bots efficiently and intelligently.</h3>
  
  [![PHP Framework](https://img.shields.io/badge/PHP%20framework-8A2BE2)](https://github.com/sanf-dev)
  [![PHP Version](https://img.shields.io/badge/PHP-%3E=8.1-8892BF)](https://www.php.net/)
  [![Packagist](https://img.shields.io/badge/Packagist-sanf/rubot-212121)](https://packagist.org/packages/sanf/rubot)
</div>

# Rubot

**Rubot** is a **PHP framework** for building **Rubika bots**.
It uses the official **Rubika APIs** to create bots efficiently and reliably.
Rubot is optimized for performance and ease of use, offering all supported methods for messaging, media, and user interactions.

Whether you need a simple chatbot or a more advanced assistant, Rubot makes development faster, smarter, and more maintainable.

> V 2.1.0
> PHP 8.1+

### What's New?

> Update v2.1.0

> **Overall changes**

- Optimization of core classes
- Using Guzzle for sending requests, downloading, and uploading
- Updated filters in the Message class
- Optimized upload and download
- Added support for receiving updates via long polling
- Added button links for joining channels and opening URLs
- Simplified retrieval and usage of file IDs
- Added new filters
- Added progress tracking for downloads and uploads
- Added the Rubino class

> **Minor changes**

- Added ENV file support
- Added retry count for failed requests
- Added timeout setting
- Added debugger
- Added automatic token retrieval from ENV

# Initial Setup

1. Get your token from the [@BotFather](https://rubika.ir/botfather) bot.

2. Install the Library
   Download the library using one of the following methods:

Using Composer

```bash
composer require sanf/rubot
# Or Install the Beta Version
composer require sanf/rubot:dev-main
```

Clone from GitHub

```bash
git clone https://github.com/sanf-dev/rubot
composer dump-autoload
```

3. Create a File and Run a Simple Bot

```php
require "vendor/autoload.php";

use Rubot\Bot;
use Rubot\Tools\Message;

$bot = new Bot("YOUR_BOT_TOKEN");

$run = function (Message $update) use ($bot) {
    $update->reply("Hello, user");
};

$bot->onMessage($run); // run webhook
```

4. Set a Webhook for Your Bot

```php
$bot->setWebHook("WEBHOOK_URL");
```

5. Congratulations 🎉
   You have successfully set up your bot!

# (Bot) Methods

## Sending Message

Simple example of sending a message with the Bot class

```php
$bot->sendMessage(
    "CHAT_ID",
    "TEXT",
    "REPLY_MESSAGE_ID",
    "DISABLE_NOTIFICATION",
    ["OTHER_METHOD"]
);
```

- `CHAT_ID` – `string` → The target chat ID.
- `TEXT` – `string` → The text message you want to send.
- `REPLY_MESSAGE_ID` – `string` → The message ID to reply to (optional).
- `DISABLE_NOTIFICATION` – `bool` → Additional options:
- `OTHER_METHOD` – `array` → Additional options:
  - `inline_keypad`: `array`
  - `chat_keypad`: `array`
  - `chat_keypad_type`: `string`

## Other methods

Get familiar with the different methods

### Sending Messages

- `sendFile` : `Send a file to a chat`
- `sendPoll` : `Send a poll to the chat`
- `sendLocation` : `Send a temporary location `
- `sendContact` : `Send a contact (phone number & name)`
- `forwardMessage` : `Forward a message from one chat to another `

### Editing & Deleting

- `editMessage` : `Edit the content of a previously sent message`
- `deleteMessage` : `Delete a message from a chat`
- `editChatKeypad` : `Edit an existing chat keyboard`

### File Handling

- `getFile` : `Get file information using its file ID`
- `sendFile` : `Upload a media file to Rubika servers`
- `download` : `Download a file from its file ID to a specified path`

### Chat & Bot Info

- `getMe` : `Get bot account details`
- `getChat` : `Get chat information (user, group, or channel)`
- `getUpdates` : `Receive incoming updates (polling method)`
- `setCommands` : `Set bot commands  `

### Webhook & Security

- `setSecretKey` : `Set a security key to protect webhook access `
- `checkSecretKey` : `Check if the incoming webhook request contains the correct key`
- `setWebHook` : `Automatically set webhook to a given URL`
- `onMessage` : `Handle incoming messages from the webhook with a callback function`
- `onUpdate` : `Receive updates using long polling`

## (Message) Methods

- `text` : `get Message`
- `chat_id` : `get Chat Id`
- `message_id` : `get Message Id`

## License

MIT © [Sanf-Dev](https://github.com/sanf-dev/)
