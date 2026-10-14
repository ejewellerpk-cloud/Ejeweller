<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BulkPostExBookRequest;
use App\Http\Resources\OrderDetailsResource;
use App\Models\Order;
use App\Services\PostEx\PostExOrderService;
use Exception;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PostExOrderController extends AdminController implements HasMiddleware
{
    public function __construct(protected PostExOrderService $postExOrderService)
    {
        parent::__construct();
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:online-orders', only: [
                'create',
                'bulkCreate',
                'track',
                'cancel',
                'paymentStatus',
                'airwayBill',
            ]),
        ];
    }

    public function create(Order $order): OrderDetailsResource|\Illuminate\Http\Response
    {
        try {
            $result = $this->postExOrderService->createShipment($order);

            return new OrderDetailsResource($result['order']);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function bulkCreate(BulkPostExBookRequest $request): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
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
                $result = $this->postExOrderService->createShipment($order);
                $success++;
                $results[] = [
                    'id'                      => (int) $orderId,
                    'ok'                      => true,
                    'order_serial_no'         => $result['order']->order_serial_no,
                    'postex_tracking_number'  => $result['order']->postex_tracking_number,
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
            'message' => "Booked {$success} order(s). Failed: {$failed}.",
            'data'    => [
                'success' => $success,
                'failed'  => $failed,
                'results' => $results,
            ],
        ]);
    }

    public function track(Order $order): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            $data = $this->postExOrderService->track($order);

            return response([
                'status' => true,
                'data'   => $data['dist'] ?? $data,
            ]);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function cancel(Order $order): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            $this->postExOrderService->cancel($order);

            return response(['status' => true, 'message' => 'PostEx order cancelled successfully.']);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function paymentStatus(Order $order): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            $data = $this->postExOrderService->paymentStatus($order);

            return response([
                'status' => true,
                'data'   => $data['dist'] ?? $data,
            ]);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function airwayBill(Order $order): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            $response = $this->postExOrderService->airwayBill($order);

            if (!$response->successful() || !str_starts_with($response->body(), '%PDF')) {
                $message = $response->json('statusMessage') ?? 'Unable to generate airway bill.';

                return response(['status' => false, 'message' => $message], 422);
            }

            return response($response->body(), 200, [
                'Content-Type'        => $response->header('Content-Type') ?? 'application/pdf',
                'Content-Disposition' => 'inline; filename="postex-airway-bill-' . $order->order_serial_no . '.pdf"',
            ]);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
