<?php

namespace App\Controllers\Front\Api;

use App\Core\Controller;
use App\Core\Session;
use App\Models\HomeDashboard;

class HomeApiController extends Controller
{
    public function summary(): void
    {
        if (!Session::isLoggedIn()) {
            $this->jsonResponse(['success' => false, 'error' => 'unauthorized'], 401);
        }

        $this->jsonResponse([
            'success' => true,
            'summary' => HomeDashboard::liveSummary(),
        ]);
    }
}
