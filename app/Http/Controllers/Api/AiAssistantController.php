<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TableSeat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiAssistantController extends Controller
{
    public function ask(Request $request)
    {
        $query = strtolower($request->input('query', ''));

        if (Str::contains($query, ['a-7', 'a7'])) {
            $table = TableSeat::where('name', 'A-7')->first();
            if ($table) {
                return response()->json([
                    'answer' => 'A-7 adalah sebuah meja (Table Seat) yang tersedia.',
                ]);
            }

            return response()->json(['answer' => 'Saya tahu A-7 seharusnya ada, tapi tidak menemukannya di database.']);
        }

        if (Str::contains($query, ['abudi', 'budi'])) {
            $user = User::where('name', 'abudi')->first();
            if ($user) {
                return response()->json([
                    'answer' => "Abudi adalah staf kami (User ID: {$user->id}) yang bertugas di cabang ID: {$user->branchid}.",
                ]);
            }

            return response()->json(['answer' => 'Saya tidak menemukan staf bernama Abudi saat ini.']);
        }

        return response()->json([
            'answer' => "Maaf, saya belum mengerti pertanyaan Anda. Coba tanya tentang 'a-7' atau 'abudi'.",
        ]);
    }
}
