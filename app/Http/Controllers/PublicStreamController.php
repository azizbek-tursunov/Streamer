<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use Inertia\Inertia;

class PublicStreamController extends Controller
{
    public function index()
    {
        return Inertia::render('Public/Stream', [
            'cameras' => Camera::where('is_active', true)->where('is_public', true)->get(),
        ]);
    }
}
