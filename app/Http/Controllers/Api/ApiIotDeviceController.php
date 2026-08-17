<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\IotDeviceController;
use App\Models\IotDevice;

class ApiIotDeviceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    
}
