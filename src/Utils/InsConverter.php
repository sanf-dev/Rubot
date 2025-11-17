<?php

namespace Rubot\Utils;

use League\HTMLToMarkdown\Converter\ConverterInterface;
use League\HTMLToMarkdown\ElementInterface;

class InsConverter implements ConverterInterface
{
    public function convert(ElementInterface $element): string
    {
        $markdown = '';
        $quoteContent = \trim($element->getValue());
        $lines = \preg_split('/\r\n|\r|\n/', $quoteContent);
        \assert(\is_array($lines));
        foreach ($lines as $i => $line) {
            $markdown .= "--" . $line . "--";
        }
        return $markdown;
    }

    public function getSupportedTags(): array
    {
        return ["ins"];
    }
}