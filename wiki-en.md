# 🤖 PHP Bot Framework for Rubika

Hey there! 👋 Welcome to the Rubot framework – your friendly sidekick for building awesome bots on Rubika. We'll guide you step by step to get your bot up and running in no time. Rubot makes everything faster, safer, and way simpler. No more wrestling with complex setups – just pure bot-building fun! 🚀

---

## 🚀 Getting Started

Building a bot can feel overwhelming with all those challenges, right? Don't sweat it – we've smoothed out the tough parts for you. Let's dive into Rubot step by step, so you can skip the headaches and focus on the cool stuff.

### Step 1: Grab Your Bot Token

First things first: Head over to [@BotFather](https://rubika.ir/botfather) on Rubika and create your bot. It'll give you a token that looks something like this:

```token
CDDDJ0WLUIFLDETYHGDVOOBJJCYXSDPWXKIMFNKQYGVZHJJPRNLTWCGQNVNQQWGX
```

Keep this token safe – it's like your bot's secret password! 🔑

### Step 2: Download and Install Rubot

Time to bring Rubot home. You've got three easy options – pick what suits you best.

1. **Via Composer (Recommended – Super Quick!)**  
   Open your terminal and create a project folder:

   ```bash
   mkdir rubika_bot
   cd rubika_bot
   ```

   Then install the library:

   ```bash
   composer require sanf/rubot
   ```

   Boom! You're set. 🎉

2. **Via Git (For Version Control Lovers)**  
   Create your folder like before:

   ```bash
   mkdir rubika_bot
   cd rubika_bot
   ```

   Clone the repo:

   ```bash
   git clone https://github.com/sanf-dev/Rubot.git
   ```

   And generate the autoload file:

   ```bash
   composer dump-autoload
   ```

   Easy peasy!

3. **Direct Download from GitHub (No Tools Needed)**  
   Visit the [Rubot GitHub page](https://github.com/sanf-dev/Rubot).  
   Click the green **Code** button up top-right, then hit **Download ZIP**.  
   Unzip the file, navigate to it in your terminal, and run:
   ```bash
   composer dump-autoload
   ```
   All done – high five! ✋

**You've nailed the first big step!** Let's keep the momentum going.

### Step 3: Load the Framework

This part's a breeze. Create a new PHP file (say, `bot.php`) right next to your Rubot folder. Kick it off like this:

```php
<?php
require "vendor/autoload.php";

use Rubot\Bot;

$bot = new Bot("YOUR_BOT_TOKEN_HERE");
```

Swap in your token where it says `YOUR_BOT_TOKEN_HERE`. Now, call any method you need. For example, to send a message:

```php
$result = $bot->sendMessage(
    "CHAT_ID",              // Who to send it to
    "Hello, world! 🌍",     // Your message
    null,                   // Reply to a message ID (optional)
    false,                  // Disable notifications? (false = with sound)
    []                      // Extra options (like keyboards)
);

print_r($result);  // Outputs the message ID and more details
```

We'll unpack those arguments later – promise! 😉

---

## 📨 Handling Message Updates

Your bot needs to "listen" for incoming messages. Rubot gives you two ways: Webhooks (real-time magic) or Long Polling (reliable but with a tiny delay). Choose based on your setup!

1. **Webhooks (Instant Updates)**  
   Set up a server endpoint and use:

   ```php
   $bot->onMessage(...$handlers);
   ```

2. **Long Polling (Simple Polling Loop)**  
   Note: Updates might arrive with a slight lag, but it's super straightforward.

   ```php
   $bot->onUpdate("array:Config", ...$handlers);
   ```

   Customize the config like this:

   - `limit`: Max messages per fetch (default: 100)
   - `timeout`: Request delay in seconds (default: 1)
   - `offset_id`: Starting point for updates (default: empty)
   - `seconds`: Response timeout in seconds (default: 5)

   Example config:

   ```php
   $config = [
       "limit" => 50,
       "timeout" => 0,
       "offset_id" => "",
       "seconds" => 7
   ];
   $bot->onUpdate($config, ...$handlers);
   ```

To process updates, import the handy `Message` class:

```php
use Rubot\Tools\Message;
```

Then create an anonymous function (handler):

```php
$handle = function (Message $update) use ($bot) {
    print_r($update->rawData());  // See the raw update data
};

$bot->onUpdate([], $handle);  // Empty config for defaults
```

Now, explore some `Message` class gems:

```php
$handle = function (Message $update) use ($bot) {
    // Reply to the message
    $update->reply("Thanks for chatting! 😊");

    // Reply with a file
    $update->replyFile("Check this out!", "https://example.com/image.jpg");

    // Get basics
    $text = $update->text();
    $msgId = $update->message_id();

    // Check for files
    if ($update->is_file()) {
        echo "File incoming!";
    }
};
```

More details coming up – stay tuned! 📚

### Build a Simple Bot (Your First Win!)

Let's put it together with a fun example. This bot greets users and responds to "Rubot":

```php
<?php
require_once "vendor/autoload.php";

use Rubot\Bot;
use Rubot\Tools\Message;

$bot = new Bot("YOUR_BOT_TOKEN_HERE");

$handle = function (Message $update) {
    $text = $update->text();

    if ($text == "Rubot") {
        $update->reply("I'm here! 🙋‍♂️ What's up?");
    } elseif (in_array($text, ["hi", "hello", "Hello", "Hi"])) {
        $update->reply("Hello and welcome! I'm Rubot, your bot buddy. 😄");
    }
};

$bot->onMessage($handle);  // Or use onUpdate for polling
```

Run it and chat with your bot – magic! ✨

---

## 🎛️ Inline Messages and Keyboards

Want interactive buttons? Rubot makes glass-like inline messages and reply keyboards a snap. Here's a quick taste:

### Creating Inline Messages (Glass Buttons)

```php
require_once "vendor/autoload.php";

use Rubot\Enums\ButtonType;
use Rubot\Tools\InlineKeypadBuilder;

// Build a simple inline keyboard
$inline = new InlineKeypadBuilder();
$inline
    ->row(
        InlineKeypadBuilder::button(
            "btn_simple",           // Button ID
            "Click Me!",            // Button text
            ButtonType::Simple      // Type (default: Simple)
        )
    )
    ->row(
        InlineKeypadBuilder::button("btn_add", "Add New Item"),
        InlineKeypadBuilder::button("btn_remove", "Remove Item")
    );

$bot->sendMessage(
    "CHAT_ID",
    "Pick an option below:",
    null,           // Reply ID
    false,          // Notifications
    $inline         // The keyboard (auto-converts to JSON)
);
```

### Creating Reply Keyboards

```php
use Rubot\Enums\ButtonType;
use Rubot\Tools\ChatKeypadBuilder;

$keypad = new ChatKeypadBuilder();
$keypad
    ->row(
        ChatKeypadBuilder::button(
            "btn_start",
            "Start Here",
            ButtonType::Simple
        )
    )
    ->row(
        ChatKeypadBuilder::button("btn_add", "Add New Item"),
        ChatKeypadBuilder::button("btn_remove", "Remove Item")
    );

$bot->sendMessage(
    "CHAT_ID",
    "Choose wisely:",
    null,
    false,
    $keypad
);
```

### Level Up: Number Picker Example

For something fancier, like an age selector:

```php
use Rubot\Enums\ButtonType;
use Rubot\Button\NumberPicker;
use Rubot\Tools\InlineKeypadBuilder;

$numberPicker = new NumberPicker();
$numberPicker
    ->title("Select your age:")
    ->max_value(55)
    ->min_value(18)
    ->default_value(22);

$inline = new InlineKeypadBuilder();
$inline->row(
    InlineKeypadBuilder::button(
        "select_age",
        "Click to Select",
        ButtonType::NumberPicker,
        $numberPicker->build()  // Embed the picker
    )
);
```

**Pro Tip:** Inline messages won't show up in polling mode – use webhooks for the full sparkle! 🌟

---

## 🛠️ Classes and Methods Deep Dive

Ready to geek out? Here's the full scoop on Rubot's classes and methods. We've organized it neatly so you can jump right in.

### Bot Class Arguments

These are the building blocks for most methods (shared with the `Message` class too). Think of them as your bot's toolkit!

| Argument              | Description                       | Type                    | Default | Required? |
| --------------------- | --------------------------------- | ----------------------- | ------- | --------- |
| $chat_id              | Target chat ID                    | `string`                | -       | Yes       |
| $chat_ids             | Chat IDs for user info            | `string` or `array`     | -       | Yes       |
| $text                 | Message text to send              | `string`                | -       | Yes       |
| $path                 | File path/URL (max 50MB)          | `string`                | -       | Yes       |
| $options              | Poll or extra options             | `array`                 | -       | Yes       |
| $question             | Poll question                     | `string`                | -       | Yes       |
| $latitude             | Latitude for location             | `string`                | -       | Yes       |
| $longitude            | Longitude for location            | `string`                | -       | Yes       |
| $phone                | Phone number to send              | `string`                | -       | Yes       |
| $first_name           | First name for contact            | `string`                | -       | Yes       |
| $last_name            | Last name for contact             | `string`                | -       | Yes       |
| $file_id              | File ID for download/processing   | `string`                | -       | Yes       |
| $file_name            | Custom file name                  | `string`                | `null`  | No        |
| $reply                | Reply message ID                  | `string` ,`int` ,`null` | `null`  | No        |
| $disable_notification | Send silently?                    | `bool`                  | `false` | No        |
| $other                | Extra params                      | `array`                 | `[]`    | No        |
| $progress             | Upload/download progress callback | `callable`              | `null`  | No        |

### Bot Class Methods

The heart of your bot – everything from sending messages to managing updates.

| Method           | Description                     |
| ---------------- | ------------------------------- |
| `onMessage`      | Handle updates via webhook      |
| `onUpdate`       | Handle updates via long polling |
| `getToken`       | Get your bot's token            |
| `getMe`          | Fetch bot info                  |
| `sendMessage`    | Send a text message             |
| `editMessage`    | Edit a message                  |
| `deleteMessage`  | Delete a message                |
| `sendFile`       | Send a file                     |
| `sendPoll`       | Send a poll                     |
| `sendLocation`   | Send a location                 |
| `sendContact`    | Send a contact                  |
| `forwardMessage` | Forward a message               |
| `getChat`        | Get chat/user info              |
| `getUpdates`     | Fetch pending updates           |
| `setCommands`    | Set bot commands                |
| `setWebHook`     | Set webhook URL                 |
| `editChatKeypad` | Edit a reply keyboard           |
| `download`       | Download file by ID             |
| `getFile`        | Get file link                   |
| `streamUpload`   | Upload file with streaming      |

### Message Class Methods

Your go-to for processing incoming messages – filters, replies, and more. We've grouped them for clarity!

#### Get Update Info

| Method                | Description                       |
| --------------------- | --------------------------------- |
| `text`                | Get message text                  |
| `chat_id`             | Get sender's chat ID              |
| `message_id`          | Get message ID                    |
| `reply_to_message_id` | Get replied-to message ID         |
| `getTime`             | Get send time                     |
| `is_edited`           | Check if edited                   |
| `sender_type`         | Get sender type (user/group/etc.) |
| `sender_id`           | Get sender ID                     |
| `button_id`           | Get button ID                     |
| `File`                | Get file info                     |
| `location`            | Get location info                 |
| `contact`             | Get contact info                  |
| `forwarded`           | Get forwarded message info        |
| `sticker`             | Get sticker info                  |
| `start_id`            | Get start payload ID              |
| `poll`                | Get poll info                     |
| `rawData`             | Get raw update data               |

#### Action Methods (Send Stuff!)

| Method             | Description            |
| ------------------ | ---------------------- |
| `reply`            | Reply to the message   |
| `deleteMessage`    | Delete the message     |
| `replyFile`        | Reply with a file      |
| `sendPoll`         | Send a poll            |
| `sendLocation`     | Send a location        |
| `sendContact`      | Send a contact         |
| `forwardMessage`   | Forward the message    |
| `editChatKeypad`   | Edit reply keyboard    |
| `removeChatKeypad` | Remove reply keyboard  |
| `DownloadFile`     | Auto-download the file |

#### Filters (Smart Checks)

| Method                    | Description                  |
| ------------------------- | ---------------------------- |
| `is_command`              | Is it a command?             |
| `is_button_id`            | Has a button ID?             |
| `is_file`                 | Is it a file?                |
| `is_user`                 | From a private chat?         |
| `is_group`                | From a group?                |
| `is_channel`              | From a channel?              |
| `is_sticker`              | Is it a sticker?             |
| `is_poll`                 | Is it a poll?                |
| `is_long`                 | Is the message long?         |
| `is_short`                | Is the message short?        |
| `is_similar_to`           | Similar to a given text?     |
| `has_reply_to`            | Has a reply?                 |
| `has_link`                | Contains a link?             |
| `has_mention`             | Has a mention?               |
| `has_hashtag`             | Has a hashtag?               |
| `has_emoji`               | Has emojis?                  |
| `has_pattern`             | Matches a pattern?           |
| `contains_number`         | Contains numbers?            |
| `contains_email`          | Contains an email?           |
| `contains_phone`          | Contains a phone number?     |
| `contains_words`          | Contains specific words?     |
| `contains_words_all`      | Contains all specific words? |
| `contains_date`           | Contains a date?             |
| `contains_time`           | Contains a time?             |
| `contains_code`           | Contains code?               |
| `contains_repeated_chars` | Has repeated characters?     |
| `contains_language`       | Detects languages?           |
| `Filelocker`              | Lock specific files          |
| `Limiter`                 | Prevent spam and limit users |

### Security Class

Keep those pesky unauthorized webhook hits at bay! This class blocks invalid access automatically.

| Method     | Description                     |
| ---------- | ------------------------------- |
| `create`   | Set up auto-blocking with a key |
| `check`    | Verify incoming requests        |
| `getInput` | Get URL inputs                  |

**How It Works:** Add a secret key to your webhook URL, like `https://yourdomain.com/webhook?key=my_secret_key`. Pass it to Security:

```php
use Rubot\Tools\Security;

Security::create("my_secret_key")->set();
```

Optional config array for extras:

- `key`: Param name (default: "key")
- `log`: Enable logging (default: false)
- `log_file_name`: Log file name (default: ".log_file_name")

Secure and sound! 🛡️

---

## 🆘 Issues and Suggestions

Running into bumps or got killer ideas? We're all ears!

1. Drop a new Issue on our [GitHub repo](https://github.com/sanf-dev/Rubot) – we'll hop on it quick!
2. Ping us on Rubika: [@Coder98](https://rubika.ir/Coder98) or [@Coder95](https://rubika.ir/Coder95)
3. Or slide into Telegram: [@Coder95](https://t.me/Coder95)

Hope this guide sparked your bot dreams – happy coding! If it helped, give it a star on GitHub. ⭐

**Built with ❤️ by the Rubot Team**
