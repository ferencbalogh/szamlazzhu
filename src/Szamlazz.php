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
    public function createReceipt(
        $elotag,
        $fizmod,
        $rendelesszam,
        $brutto,
        $email,
        $targy,
        $uzenet
    ) {
        $receipt = new Receipt($elotag, $fizmod, $rendelesszam, $brutto, $email, $targy, $uzenet);
        return $receipt->createReceipt();
    }

    /**
     * Check Username & Password
     */
    protected function checkConnection()
    {
        if (config('szamlazz.username') === null || config('szamlazz.password') === null) {
            throw new InvalidUserException('Missing username and password. Setup .env variables please.');
        }
    }
}