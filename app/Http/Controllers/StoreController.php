<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(): Response
    {
        $stores = Store::query()
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Stores/Index', [
            'stores' => $stores,
        ]);
    }
}
