<?php

namespace FerencBalogh\Szamlazzhu;

use FerencBalogh\Szamlazzhu\Receipt;
use FerencBalogh\Szamlazzhu\Exceptions\InvalidUserException;

class Szamlazz
{
    /**
     * Szamlazz constructor.
     */
    public function __construct()
    {
        $this->checkConnection();
    }

    /**
     * Create receipt
     */
    public function createReceipt()
    {

        $receipt = new Receipt();
        echo $receipt->handler();
    }

    /**
     * Check Username & Password
     */
    protected function checkConnection()
    {
        if (config('szamlazz.username') === null || $this->password === null) {
            throw new InvalidUserException('Missing username and password. Setup .env variables please.');
        }
    }
}