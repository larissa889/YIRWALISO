<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Afficher le tableau de bord de l'administration
     */
    public function index()
    {
        return view('admin.dashboard', [
            'title' => 'Tableau de bord',
            'header' => 'Tableau de bord'
        ]);
    }
}
