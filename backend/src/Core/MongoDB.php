<?php

namespace App\Core;

final class MongoDB
{
    public static function client(array $config): \MongoDB\Client
    {
        return new \MongoDB\Client($config['mongo']['uri']);
    }
}
