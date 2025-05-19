<?php

namespace Tests\TestModels;

class SessionTestModelDataTable extends TestModelDataTable
{
    public function getSessionKeyPublic(string $suffix = ''): string
    {
        return $this->getSessionKey($suffix);
    }

    public function storeInSessionPublic(string $key, $value): void
    {
        $this->storeInSession($key, $value);
    }

    public function getFromSessionPublic(string $key, $default = null)
    {
        return $this->getFromSession($key, $default);
    }
}