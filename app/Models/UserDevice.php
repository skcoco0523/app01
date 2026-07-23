<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    use HasFactory;

    protected $table = 'user_devices';

    // マスアサインメント可能な属性
    protected $fillable = [
        'user_id',
        'device_id',
        'os',
        'browser',
        'endpoint',
        'public_key',
        'auth_token'
    ];
    
    public static function updateOrCreateDevice($data)
    {
        return self::updateOrCreate(
            [
                'user_id' => $data['user_id']
            ],
            [
                'device_id' => $data['device_id'],
                'os' => $data['os'],
                'browser' => $data['browser'],
                'endpoint' => $data['endpoint'],
                'public_key' => $data['public_key'],
                'auth_token' => $data['auth_token']
            ]
        );
    }
    public static function getUserDevices($user_id)
    {
        return UserDevice::where('user_id', $user_id)->first();
    }

    public static function delUserDevices($user_id)
    {
        return UserDevice::where('user_id', $user_id)->delete();
    }

    // ユーザーとのリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
