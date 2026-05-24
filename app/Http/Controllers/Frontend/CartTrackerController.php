<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CartTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartTrackerController extends Controller
{
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'session_id' => 'required|string',
        ]);

        CartTracker::firstOrCreate([
            'product_id' => $request->product_id,
            'session_id' => $request->session_id,
            'user_id'    => Auth::check() ? Auth::id() : null,
        ]);

        return response()->json(['status' => true, 'message' => 'Cart tracked successfully']);
    }

    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'session_id' => 'required|string',
        ]);

        $query = CartTracker::where('product_id', $request->product_id)
            ->where('session_id', $request->session_id);

        if (Auth::check()) {
            $query->orWhere(function($q) use ($request) {
                $q->where('product_id', $request->product_id)
                  ->where('user_id', Auth::id());
            });
        }

        $query->delete();

        return response()->json(['status' => true, 'message' => 'Cart track removed']);
    }

    public function clear(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $query = CartTracker::where('session_id', $request->session_id);

        if (Auth::check()) {
            $query->orWhere('user_id', Auth::id());
        }

        $query->delete();

        return response()->json(['status' => true, 'message' => 'Cart tracks cleared']);
    }
}
