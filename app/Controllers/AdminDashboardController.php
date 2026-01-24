<?php

namespace App\Controllers;

use Tuto\Http\Responses\ViewResponse;

class AdminDashboardController
{
    public function dashboard(): ViewResponse
    {
        return view('admin/dashboard.twig');
    }
}