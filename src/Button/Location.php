<?php

namespace Rubot\Button;

use Rubot\Enums\LocationType;

class Location implements \JsonSerializable
{
    private array $rows = [];

    public function title(string $title): self
    {
        $this->rows["title"] = $title;
        return $this;
    }

    public function type(LocationType $type = LocationType::Picker): self
    {
        $this->rows["type"] = $type->value;
        return $this;
    }

    public function location_image_url(string $url)
    {
        $this->rows["location_image_url"] = $url;
        return $this;
    }

    public function default_pointer_location(string $longitude, string $latitude)
    {
        $this->rows["default_pointer_location"] = [
            "longitude" => $longitude,
            "latitude" => $latitude
        ];
        return $this;
    }

    public function default_map_location(string $longitude, string $latitude)
    {
        $this->rows["default_map_location"] = [

            "longitude" => $longitude,
            "latitude" => $latitude

        ];
        return $this;
    }

    public function build(): array
    {
        return [
            "button_location" => $this->rows
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->build();
    }
}
