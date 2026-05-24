<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Libraries\AppLibrary;
use App\Models\CartTracker;
use App\Models\Product;
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

        $product = Product::find($request->product_id);
        $stats   = AppLibrary::productSocialProofStats($product);

        return response()->json([
            'status'  => true,
            'message' => 'Cart tracked successfully',
            ...$stats,
        ]);
    }

    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'session_id' => 'required|string',
        ]);

        CartTracker::where('product_id', $request->product_id)
            ->where('session_id', $request->session_id)
            ->delete();

        if (Auth::check()) {
            CartTracker::where('product_id', $request->product_id)
                ->where('user_id', Auth::id())
                ->delete();
        }

        $product = Product::find($request->product_id);
        $stats   = AppLibrary::productSocialProofStats($product);

        return response()->json([
            'status'  => true,
            'message' => 'Cart track removed',
            ...$stats,
        ]);
    }

    public function clear(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        CartTracker::where('session_id', $request->session_id)->delete();

        if (Auth::check()) {
            CartTracker::where('user_id', Auth::id())->delete();
        }

        return response()->json(['status' => true, 'message' => 'Cart tracks cleared']);
    }

    public function stats(Request $request)
    {
        $request->validate([
            'product_ids'   => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $data = [];
        foreach ($request->product_ids as $productId) {
            $product = Product::find($productId);
            $data[(string) $productId] = AppLibrary::productSocialProofStats($product);
        }

        return response()->json([
            'status' => true,
            'data'   => $data,
        ]);
    }
}
