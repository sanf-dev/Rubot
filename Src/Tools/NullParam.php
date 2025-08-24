<?php

declare(strict_types=1);

namespace RuBot\Tools;

use RuBot\Enums\Field;

class NullParam extends Param
{
    public function __construct()
    {
        parent::__construct([]);
    }

    public function ParamField(Field $field)
    {
        return null;
    }

    public function __call(string $name, array $args)
    {
        return null;
    }

    public function __get(string $name)
    {
        return null;
    }
    public function is_empty(): bool
    {
        return true;
    }
}