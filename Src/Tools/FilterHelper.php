<?php

namespace RuBot\Tools;

class FilterHelper
{
    public static function ButtonID(string $expectedId): \Closure
    {
        return function ($update) use ($expectedId) {
            return $update->button_id() === $expectedId;
        };
    }

    public static function Command(string $expectedCommand): \Closure
    {
        return function ($update) use ($expectedCommand) {
            return $update->text() === '/' . ltrim($expectedCommand, '/');
        };
    }
}
