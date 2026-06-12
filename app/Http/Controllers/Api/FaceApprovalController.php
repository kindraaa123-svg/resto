<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class FaceApprovalController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate([
            'face_descriptor' => 'required|array',
            'face_descriptor.*' => 'numeric',
        ]);

        $liveDescriptor = $request->face_descriptor;

        // Find all users who are Superadmin, Admin, or Manager
        $authorizedUsers = User::whereNotNull('face_descriptor')->get();

        foreach ($authorizedUsers as $user) {
            $savedDescriptor = $user->face_descriptor;

            // Handle if database returns JSON string instead of array
            if (is_string($savedDescriptor)) {
                $savedDescriptor = json_decode($savedDescriptor, true);
            }

            if (!is_array($savedDescriptor)) continue;

            if ($this->euclideanDistance($liveDescriptor, $savedDescriptor) <= 0.6) {
                // Double check role for security
                if (in_array((int)$user->roleid, [1, 2, 8])) {
                    return response()->json([
                        'approved' => true,
                        'manager_id' => $user->id,
                        'manager_name' => $user->name,
                    ]);
                }
            }
        }

        return response()->json([
            'approved' => false,
            'message' => 'Unauthorized: Face not recognized or insufficient permissions.'
        ]);
    }

    public function checkFace(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        
        if (!$user) return response()->json(['needs_face' => false]);
        
        // All except superadmin (assuming roleid 1 is superadmin)
        // AND face_descriptor is not null
        $needsFace = ($user->roleid != 1) && !empty($user->face_descriptor);
        
        return response()->json([
            'needs_face' => $needsFace,
            'user_name' => $user->name
        ]);
    }

    public function verifyLoginFace(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'face_descriptor' => 'required|array'
        ]);
        
        $user = User::where('email', $request->email)->first();
        if (!$user || empty($user->face_descriptor)) {
            return response()->json(['approved' => false]);
        }

        $savedDescriptor = $user->face_descriptor;
        if (is_string($savedDescriptor)) {
            $savedDescriptor = json_decode($savedDescriptor, true);
        }
        
        if ($this->euclideanDistance($request->face_descriptor, $savedDescriptor) <= 0.6) {
            return response()->json(['approved' => true]);
        }
        
        return response()->json(['approved' => false]);
    }

    private function euclideanDistance($arr1, $arr2)
    {
        if (empty($arr1) || empty($arr2)) return 1.0;
        if (!is_array($arr1) || !is_array($arr2)) return 1.0;
        if (count($arr1) !== count($arr2)) return 1.0;
        
        $sum = 0;
        for ($i = 0; $i < count($arr1); $i++) {
            $sum += pow($arr1[$i] - $arr2[$i], 2);
        }
        
        return sqrt($sum);
    }
}
