<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function daySales(Request $request): View
    {
        return view('reports.day-sales', [
            'date' => $request->string('date', now()->toDateString())->toString(),
            'sales' => collect(),
        ]);
    }

    public function payables(): View
    {
        return view('reports.payables', [
            'vendors' => collect(),
        ]);
    }
}
