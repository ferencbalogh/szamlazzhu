<?php

namespace FerencBalogh\Szamlazz;

use FerencBalogh\Szamlazz\Receipt;

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
        $receipt->handler();
    }

    /**
     * Check Username & Password
     */
    protected function checkConnection()
    {
        if (config('szamlazz.username') === null || $this->password === null) {
            throw new InvalidArgumentException('missing username and password');
        }
    }
}