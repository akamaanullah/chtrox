<?php

namespace App\Controllers\Front;

use App\Models\People;

class PeopleController extends FrontController
{
    public function index(): void
    {
        $this->renderApp('people', [
            'people_contacts' => People::displayGrid(),
            'people_count' => People::count(),
        ]);
    }
}
