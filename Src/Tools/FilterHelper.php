<?php

namespace RuBot\Tools;

class FilterHelper
{
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke($update): bool
    {
        return ($this->callback)($update);
    }

    public static function Button_Id(string $expectedId): self
    {
        return new self(fn($update) => $update->button_id() === $expectedId);
    }

    public static function Command(string $expectedCommand): self
    {
        return new self(fn($update) => $update->text() === '/' . ltrim($expectedCommand, '/'));
    }

    public static function is_file(): self
    {
        return new self(function ($update) {
            $file = $update->getFile();
            return is_array($file) && isset($file["file_id"]);
        });
    }

    public static function is_forwarded(): self
    {
        return new self(function ($update) {
            $for = $update->forwarded();
            return is_array($for) && isset($for["forwarded_from"]);
        });
    }

    public static function is_contact(): self
    {
        return new self(function ($update) {
            $contact = $update->contact();
            return is_array($contact) && isset($contact["phone_number"]);
        });
    }

    public static function is_location(): self
    {
        return new self(function ($update) {
            $location = $update->location();
            return is_array($location) && isset($location["longitude"]);
        });
    }

    public function or(FilterHelper $other): FilterHelper
    {
        return new FilterHelper(fn($u) => $this($u) || $other($u));
    }

    public function and(FilterHelper $other): FilterHelper
    {
        return new FilterHelper(fn($u) => $this($u) && $other($u));
    }
}
