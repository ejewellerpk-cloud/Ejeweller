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
            $nameParts = explode(' ', (string) $user->name);
            $userData['first_name'] = $nameParts[0] ?? null;
            if (count($nameParts) > 1) {
                $userData['last_name'] = end($nameParts);
            }
        }

        $incoming = $request->input('user_data', []);
        if (is_array($incoming)) {
            $userData = array_merge($userData, array_filter($incoming));
        }

        $eventId = $request->input('event_id');
        $eventSourceUrl = $request->input('event_source_url');

        $this->fbCapiService->dispatchEvent($eventName, $userData, $customData, $eventId, $eventSourceUrl);

        return response()->json(['status' => 'success']);
    }
}
