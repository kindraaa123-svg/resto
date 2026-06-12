<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class RemoteScannerController extends Controller
{
    public function index()
    {
        return Inertia::render('Staff/MobileScanner');
    }

    public function broadcast(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string',
            'pairing_code' => 'required|string',
        ]);

        \App\Events\RemoteStaffScanned::dispatch(
            $request->pairing_code,
            $request->barcode
        );
        
        return response()->json(['status' => 'success']);
    }
}
