<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FbCapiService;

class FbCapiController extends Controller
{
    protected FbCapiService $fbCapiService;

    public function __construct(FbCapiService $fbCapiService)
    {
        $this->fbCapiService = $fbCapiService;
    }

    public function track(Request $request)
    {
        $eventName = $request->input('event_name');
        $customData = $request->input('custom_data', []);
        
        $userData = [];
        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            $userData['email'] = $user->email;
            $userData['phone'] = $user->phone;
            $nameParts = explode(' ', $user->name);
            $userData['first_name'] = $nameParts[0];
            if (count($nameParts) > 1) {
                $userData['last_name'] = end($nameParts);
            }
        } else if ($request->input('user_data')) {
            $userData = $request->input('user_data');
        }

        $eventId = $request->input('event_id');

        $this->fbCapiService->dispatchEvent($eventName, $userData, $customData, $eventId);

        return response()->json(['status' => 'success']);
    }
}
