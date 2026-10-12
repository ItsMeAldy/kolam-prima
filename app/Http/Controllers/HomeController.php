<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Project;
use App\Models\Service;
use App\Models\SiteSetting;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Tampilkan landing page.
     */
    public function index(): View
    {
        return view('home', [
            'settings' => SiteSetting::current(),
            'services' => Service::query()->ordered()->get(),
            'packages' => Package::query()->ordered()->get(),
            'projects' => Project::query()->published()->ordered()->with('images')->get(),
        ]);
    }
}
