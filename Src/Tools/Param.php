<?php

namespace RuBot\Tools;

use RuBot\Enums\Field;
class Param
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function __call(string $name, array $args)
    {
        return $this->data[$name] ?? null;
    }

    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function __debugInfo()
    {
        return $this->data ?? [];
    }

    public function __toString()
    {
        return json_encode($this->data);
    }

    public function ParamField(Field $field)
    {
        return $this->{$field->value} ?? null;
    }

    public function is_empty(): bool
    {
        return empty($this->data);
    }
}