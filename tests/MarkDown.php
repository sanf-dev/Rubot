<?php

use Rubot\Bot;
use Rubot\Enums\ParseMode;
use Rubot\Tools\Message;
require '../vendor/autoload.php';

$bot = new Bot("TOKEN", ParseMode::Markdown);


$markdown = <<<'Markdown'
>this quote text and
**bold**
`Mono`
__Italic__
--Underline | insert--
~~Strike | delete~~
||Spoiler||
[Link|Sender_id](https://github.com/Rubot)
code ```php
for ($i = 0; $i <= 15; $i++) {
    echo $i . PHP_EOL;
}
```
mix ||**`hello from Rubot - php`**||
Markdown;



$run = function (Message $update) use ($bot, $markdown) {
    $text = $update->text();
    echo $update->sender_id();
    if (empty($update->metadata())) {
        print_r($update->reply($markdown));
    } else {
        print_r($update->reply($text, ["metadata" => $update->metadata()]));
    }

};
$bot->onUpdate([], $run);
