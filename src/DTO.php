<?php

namespace Ramapriya\Telegram\User;

class DTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $firstName,
        public readonly ?string $lastName = null,
        public readonly ?string $username = null,
        public readonly ?string $languageCode = null
    )
    {
    }

    public function toArray(bool $toUpper = false): array
    {
        $array = [
            'id' => $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'username' => $this->username,
            'language_code' => $this->languageCode,
        ];

        return $toUpper ? array_change_key_case($array, CASE_UPPER) : $array;
    }
}