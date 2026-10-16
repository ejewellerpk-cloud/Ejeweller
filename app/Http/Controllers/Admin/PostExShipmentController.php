<?php

namespace App\Http\Controllers\Admin;

use App\Services\PostEx\PostExShipmentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PostExShipmentController extends AdminController implements HasMiddleware
{
    public function __construct(protected PostExShipmentService $postExShipmentService)
    {
        parent::__construct();
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:online-orders', only: [
                'summary',
                'index',
                'liveStatuses',
                'liveIndex',
            ]),
        ];
    }

    public function summary(Request $request): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return response([
                'status' => true,
                'data'   => $this->postExShipmentService->summary($request),
            ]);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function index(Request $request): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            $paginator = $this->postExShipmentService->list($request);
            $rows = collect($paginator->items())->map(
                fn ($order) => $this->postExShipmentService->transformOrder($order)
            )->values();

            return response([
                'status' => true,
                'data'   => $rows,
                'meta'   => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ]);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function liveStatuses(): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return response([
                'status' => true,
                'data'   => $this->postExShipmentService->liveStatuses(),
            ]);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function liveIndex(Request $request): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return response([
                'status' => true,
                'data'   => $this->postExShipmentService->liveList($request),
            ]);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
