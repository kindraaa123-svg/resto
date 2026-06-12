<?php

namespace App\Http\Controllers;

use App\Events\RemoteCameraRequest;
use App\Events\RemoteCameraScanned;
use App\Events\RemoteGuestDiscovery;
use App\Events\RemoteGuestRequest;
use App\Events\RemoteGuestScanned;
use Illuminate\Http\Request;

class RemoteCameraController extends Controller
{
    public function getNetworkInfo(Request $request)
    {
        return response()->json([
            'networkId' => 'GLOBAL_POS_NETWORK', // Global network ID for discovery
            'ip' => $request->ip(),
        ]);
    }

    public function ping(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Ping received', $request->all());
        
        try {
            $request->validate([
                'networkId' => 'required',
                'deviceId' => 'required',
                'deviceName' => 'required',
            ]);

            broadcast(new RemoteGuestDiscovery(
                $request->networkId,
                $request->deviceId,
                $request->deviceName
            ));
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Broadcast failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function guestRequest(Request $request)
    {
        $request->validate([
            'networkId' => 'required',
            'deviceId' => 'required',
            'requesterId' => 'required',
            'requesterName' => 'required',
        ]);

        broadcast(new RemoteGuestRequest(
            $request->networkId,
            $request->deviceId,
            $request->requesterId,
            $request->requesterName
        ));

        return response()->json(['success' => true]);
    }

    public function guestScanned(Request $request)
    {
        try {
            $request->validate([
                'requesterId' => 'required',
                'barcode' => 'required',
            ]);

            broadcast(new RemoteGuestScanned(
                $request->requesterId,
                $request->barcode
            ));

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Guest Scanned failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Authenticated methods
    public function request(Request $request)
    {
        $request->validate([
            'toUserId' => 'required',
        ]);

        broadcast(new RemoteCameraRequest(
            auth()->id(),
            auth()->user()->name,
            $request->toUserId
        ))->toOthers();

        return response()->json(['success' => true]);
    }

    public function scanned(Request $request)
    {
        $request->validate([
            'toUserId' => 'required',
            'barcode' => 'required',
        ]);

        broadcast(new RemoteCameraScanned(
            auth()->id(),
            $request->toUserId,
            $request->barcode
        ))->toOthers();

        return response()->json(['success' => true]);
    }
}
