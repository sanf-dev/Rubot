<?php

namespace Rubot\Utils;

class Markdown
{
    private const MARKDOWN_TYPES = [
        "```" => ["Pre", 1],
        "**" => ["Bold", 2],
        "`" => ["Mono", 3],
        "__" => ["Italic", 4],
        "--" => ["Underline", 5],
        "~~" => ["Strike", 6],
        "||" => ["Spoiler", 7],
        "[" => ["Link", 8],
    ];

    private const MENTION_PREFIX_TYPES = [
        "u" => "User",
        "g" => "Group",
        "c" => "Channel",
        "b" => "Bot",
    ];

    private const MARKDOWN_RE =
        '/(?:^(?:> ?[^\n]*\n?)+)|' .   // multiline quote
        '```([\s\S]*?)```|' .         // code block
        '\*\*([^\n*]+?)\*\*|' .       // bold
        '`([^\n`]+?)`|' .             // mono
        '__([^\n_]+?)__|' .           // italic
        '--([^\n-]+?)--|' .           // underline
        '~~([^\n~]+?)~~|' .           // strike
        '\|\|([^\n|]+?)\|\||' .       // spoiler
        '\[([^\]]+?)\]\((\S+)\)' .    // [text](url)
        '/m';


    public function toMetadata(string $text): array
    {
        $parts = [];
        $resultText = "";
        $utfPos = 0;

        $this->parseText($text, $parts, $resultText, $utfPos);

        return array_filter([
            "text" => trim($resultText),
            "metadata" => $parts ? ["meta_data_parts" => $parts] : null
        ]);
    }

    /** ---------------------------------------------------
     * CORE PARSER
     * ---------------------------------------------------- */
    private function parseText(string $text, array &$meta, string &$out, int &$utfPos): void
    {
        $pos = 0;
        $length = strlen($text);

        while ($pos < $length && preg_match(self::MARKDOWN_RE, $text, $m, PREG_OFFSET_CAPTURE, $pos)) {

            $raw = $m[0][0];
            $startByte = $m[0][1];
            $endByte = $startByte + strlen($raw);
            $before = substr($text, $pos, $startByte - $pos);
            $out .= $before;
            $utfPos += $this->utf16Length($before);

            if ($this->isQuoteBlock($raw)) {
                $this->handleQuote($raw, $meta, $out, $utfPos);
                $pos = $endByte;
                continue;
            }

            foreach (self::MARKDOWN_TYPES as $prefix => [$type, $groupIndex]) {
                if ($this->matchPrefix($raw, $prefix)) {

                    if ($type === "Link") {
                        $this->handleLink($m, $meta, $out, $utfPos);
                    } elseif ($type === "Pre") {
                        $this->handlePre($m, $meta, $out, $utfPos);
                    } else {
                        $content = $m[$groupIndex][0] ?? "";
                        $this->handleInline($type, $content, $meta, $out, $utfPos);
                    }

                    break;
                }
            }

            $pos = $endByte;
        }

        if ($pos < $length) {
            $tail = substr($text, $pos);
            $out .= $tail;
            $utfPos += $this->utf16Length($tail);
        }
    }

    private function handleQuote(string $raw, array &$meta, string &$out, int &$utfPos): void
    {
        $content = $this->extractQuoteContent($raw);
        $inner = $this->toMetadata($content);
        $clean = $inner["text"];
        $len = $this->utf16Length($clean);

        if (!empty($inner["metadata"]["meta_data_parts"])) {
            foreach ($inner["metadata"]["meta_data_parts"] as $part) {
                $part["from_index"] += $utfPos;
                $meta[] = $part;
            }
        }

        $meta[] = [
            "type" => "Quote",
            "from_index" => $utfPos,
            "length" => $len
        ];

        $out .= $clean;
        $utfPos += $len;
    }


    private function handleLink(array $m, array &$meta, string &$out, int &$utfPos): void
    {
        $label = $m[8][0] ?? "";
        $url = $m[9][0] ?? "";

        $len = $this->utf16Length($label);

        $first = $url[0] ?? "";
        $isMention = self::MENTION_PREFIX_TYPES[$first] ?? null;

        if ($isMention) {
            $meta[] = [
                "type" => "MentionText",
                "from_index" => $utfPos,
                "length" => $len,
                "mention_text_object_guid" => $url,
                "mention_text_user_id" => $url,
                "mention_text_object_type" => $isMention,
            ];
        } else {
            $meta[] = [
                "type" => "Link",
                "from_index" => $utfPos,
                "length" => $len,
                "link_url" => $url,
                "link" => [
                    "type" => "hyperlink",
                    "hyperlink_data" => ["url" => $url]
                ]
            ];
        }

        $out .= $label;
        $utfPos += $len;
    }


    private function handlePre(array $m, array &$meta, string &$out, int &$utfPos): void
    {
        $content = $m[1][0] ?? "";
        $len = $this->utf16Length($content);

        $lines = explode("\n", $content, 2);
        $language = trim($lines[0]);
        $meta[] = [
            "type" => "Pre",
            "from_index" => $utfPos,
            "length" => $len,
            "language" => $language
        ];

        $out .= $content;
        $utfPos += $len;
    }


    private function handleInline(string $type, string $content, array &$meta, string &$out, int &$utfPos): void
    {
        $inner = $this->toMetadata($content);
        $clean = $inner["text"];
        $len = $this->utf16Length($clean);
        if (!empty($inner["metadata"]["meta_data_parts"])) {
            foreach ($inner["metadata"]["meta_data_parts"] as $p) {
                $p["from_index"] += $utfPos;
                $meta[] = $p;
            }
        }

        $meta[] = [
            "type" => $type,
            "from_index" => $utfPos,
            "length" => $len,
        ];

        $out .= $clean;
        $utfPos += $len;
    }

    private function isQuoteBlock(string $text): bool
    {
        return preg_match("/^(?:> ?[^\n]*\n?)+$/m", $text) === 1;
    }

    private function matchPrefix(string $text, string $prefix): bool
    {
        return str_starts_with($text, $prefix);
    }

    private function utf16Length(?string $s): int
    {
        if (is_null($s))
            return 0;
        $len = mb_strlen($s, "UTF-8");
        $total = 0;

        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($s, $i, 1, "UTF-8");
            $code = mb_ord($ch);
            $total += ($code > 0xFFFF) ? 2 : 1;
        }

        return $total;
    }

    private function extractQuoteContent(string $quote): string
    {
        $lines = preg_split("/\r?\n/", $quote);
        $clean = [];

        foreach ($lines as $line) {
            $clean[] = preg_replace('/^> ?/', "", $line);
        }

        return implode("\n", $clean);
    }
}