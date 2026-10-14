<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Order;
use App\Exports\OrderExport;
use App\Services\OrderService;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\OrderStatusRequest;
use App\Http\Requests\BulkOrderStatusRequest;
use App\Http\Requests\PaymentStatusRequest;
use App\Http\Resources\OrderDetailsResource;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class OnlineOrderController extends AdminController implements HasMiddleware
{
    private OrderService $orderService;

    public function __construct(OrderService $order)
    {
        parent::__construct();
        $this->orderService = $order;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:online-orders', only: ['index']),
            new Middleware('permission:online-orders', only: ['show']),
            new Middleware('permission:online-orders', only: ['export']),
            new Middleware('permission:online-orders', only: ['changeStatus']),
            new Middleware('permission:online-orders', only: ['bulkChangeStatus']),
            new Middleware('permission:online-orders', only: ['changePaymentStatus']),
            new Middleware('permission:online-orders', only: ['destroy']),
        ];
    }

    public function index(PaginateRequest $request): \Illuminate\Http\Response | \Illuminate\Http\Resources\Json\AnonymousResourceCollection | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return OrderDetailsResource::collection($this->orderService->list($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function show(Order $order): \Illuminate\Http\Response | OrderDetailsResource | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new OrderDetailsResource($this->orderService->show($order, false));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function destroy(Order $order): \Illuminate\Http\Response | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            $this->orderService->destroy($order);
            return response('', 204);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function export(PaginateRequest $request): \Illuminate\Http\Response | \Symfony\Component\HttpFoundation\BinaryFileResponse | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return Excel::download(new OrderExport($this->orderService, $request), 'Online-Order.xlsx');
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function changeStatus(Order $order, OrderStatusRequest $request): \Illuminate\Http\Response | OrderDetailsResource | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new OrderDetailsResource($this->orderService->changeStatus($order, $request, false));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function bulkChangeStatus(BulkOrderStatusRequest $request): \Illuminate\Http\Response | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        $success = 0;
        $failed  = 0;
        $results = [];

        $orders = Order::query()
            ->whereIn('id', $request->validated('ids'))
            ->get()
            ->keyBy('id');

        foreach ($request->validated('ids') as $orderId) {
            $order = $orders->get($orderId);
            if (!$order) {
                $failed++;
                $results[] = [
                    'id'      => (int) $orderId,
                    'ok'      => false,
                    'message' => 'Order not found.',
                ];
                continue;
            }

            try {
                $statusRequest = OrderStatusRequest::create('/', 'POST', [
                    'status' => $request->validated('status'),
                    'reason' => $request->validated('reason'),
                ]);
                $statusRequest->setContainer(app())->setRedirector(app('redirect'));

                $this->orderService->changeStatus($order, $statusRequest, false);
                $success++;
                $results[] = [
                    'id'              => (int) $orderId,
                    'ok'              => true,
                    'order_serial_no' => $order->order_serial_no,
                    'status'          => (int) $request->validated('status'),
                ];
            } catch (Exception $exception) {
                $failed++;
                $results[] = [
                    'id'              => (int) $orderId,
                    'ok'              => false,
                    'order_serial_no' => $order->order_serial_no,
                    'message'         => $exception->getMessage(),
                ];
            }
        }

        return response([
            'status'  => true,
            'message' => "Updated {$success} order(s). Failed: {$failed}.",
            'data'    => [
                'success' => $success,
                'failed'  => $failed,
                'results' => $results,
            ],
        ]);
    }

    public function changePaymentStatus(Order $order, PaymentStatusRequest $request): \Illuminate\Http\Response | OrderDetailsResource | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new OrderDetailsResource($this->orderService->changePaymentStatus($order, $request, false));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
