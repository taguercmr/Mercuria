<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class GuideController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('App/Guide/Index', [
            'mercurialeYear' => config('mercuria.mercuriale_year'),
            'mercurialeSource' => config('mercuria.mercuriale_source'),
            'plans' => config('mercuria.plans'),
        ]);
    }
}
