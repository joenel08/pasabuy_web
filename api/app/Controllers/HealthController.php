<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class HealthController
{
    public function index(Request $request, array $params = []): void
    {
        Response::ok(['status' => 'up', 'time' => date('c')]);
    }
}