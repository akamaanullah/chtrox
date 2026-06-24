<?php

namespace App\Helpers;

class GiphyUrl
{
    public static function isGifUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
            return false;
        }

        if (!preg_match('/giphy\.com/i', $url)) {
            return false;
        }

        if (preg_match('/^https?:\/\/[a-zA-Z0-9.-]+\.giphy\.com\/v1\/gifs\//i', $url)) {
            return true;
        }

        if (preg_match('/^https?:\/\/media[0-9]*\.giphy\.com\/media\//i', $url)) {
            return true;
        }

        if (preg_match('/^https?:\/\/i\.giphy\.com\//i', $url)) {
            return true;
        }

        return false;
    }

    public static function resolveMessageType(string $storedType, string $body): string
    {
        if ($storedType === 'gif') {
            return 'gif';
        }

        if ($storedType === 'text' && self::isGifUrl($body)) {
            return 'gif';
        }

        return $storedType;
    }
}
