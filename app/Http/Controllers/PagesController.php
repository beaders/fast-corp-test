<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEventAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function pageA(Request $request): View
    {
        return $this->viewPage('page-a', ActivityEventAction::PageA, $request->user('web'));
    }

    public function pageB(Request $request): View
    {
        return $this->viewPage('page-b', ActivityEventAction::PageB, $request->user('web'));
    }
}
