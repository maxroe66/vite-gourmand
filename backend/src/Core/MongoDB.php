<?php

namespace App\Core;

final class MongoDB
{
    public static function client(array $config): \MongoDB\Client
    {
        $uri = $config['mongo']['uri'];
        
        // Options pour garantir UTF-8 et conversion correcte des types
        $options = [
            'typeMap' => [
                'root' => 'array',
                'document' => 'array', 
                'array' => 'array'
            ]
        ];
        
        return new \MongoDB\Client($uri, [], $options);
    }
}
