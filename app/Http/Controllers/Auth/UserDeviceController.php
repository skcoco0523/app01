<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserDevice;

class UserDeviceController extends Controller
{
    public function device_update(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"------------start-----------");
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'device_id' => 'required|string|max:255|unique:user_devices',
            'os' => 'required|string|max:255',
            'browser' => 'required|string|max:255',
            'endpoint' => 'required|string',
            'public_key' => 'required|string',
            'auth_token' => 'required|string',
        ]);
        make_error_log($error_log,"validated=".print_r($validated,1));

        $device = UserDevice::updateOrCreateDevice($validated);

        if($device) return true;
        else        return false;
    }




}