<?php

namespace App\Core;

abstract class Model
{
    public static function db(): \PDO
    {
        return Database::connection();
    }
}
