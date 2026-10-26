<?php
namespace Admin\Controllers;

use Admin\Core\View;
use App\Core\Request;

class LandingController
{
    public function index(Request $request, array $params = []): void
    {
        View::render('landing.index', [
            'title' => 'PasaBuy Mo, Pasaya Mo',
        ], null);
    }
}