<?php

namespace App\Models;

use App\Core\Model;

class MediaAssets extends Model
{
    public static function commonMedia(): array
    {
        return [
            'https://images.pexels.com/photos/1181675/pexels-photo-1181675.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/1181467/pexels-photo-1181467.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/1181354/pexels-photo-1181354.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/1181243/pexels-photo-1181243.jpeg?auto=compress&cs=tinysrgb&w=800',
            'https://images.pexels.com/photos/1181208/pexels-photo-1181208.jpeg?auto=compress&cs=tinysrgb&w=800',
        ];
    }
}
