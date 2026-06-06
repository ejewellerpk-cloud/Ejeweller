<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Order;
use App\Enums\Source;
use App\Exports\OrderExport;
use App\Services\OrderService;
use App\Services\CustomerService;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\OrderResource;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\WhatsappOrderRequest;
use App\Http\Requests\OrderStatusRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Requests\PaymentStatusRequest;
use App\Http\Resources\OrderDetailsResource;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class WhatsappOrderController extends AdminController implements HasMiddleware
{
    private OrderService $orderService;
    private CustomerService $customerService;

    public function __construct(OrderService $order, CustomerService $customerService)
    {
        parent::__construct();
        $this->orderService = $order;
        $this->customerService = $customerService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:whatsapp-order', only: ['store', 'storeCustomer']),
            new Middleware('permission:whatsapp-orders|whatsapp-order', only: ['index', 'show']),
            new Middleware('permission:whatsapp-orders', only: ['destroy', 'export', 'changeStatus', 'changePaymentStatus']),
        ];
    }

    public function store(WhatsappOrderRequest $request): \Illuminate\Http\Response | OrderDetailsResource | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new OrderDetailsResource($this->orderService->whatsappOrderStore($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function storeCustomer(
        CustomerRequest $request
    ): \Illuminate\Http\Response|CustomerResource|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory {
        try {
            $customer = $this->customerService->store($request);
            return new CustomerResource($customer);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function index(PaginateRequest $request): \Illuminate\Http\Response | \Illuminate\Http\Resources\Json\AnonymousResourceCollection | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            if (!$request->filled('source')) {
                $request->merge(['source' => Source::WHATSAPP]);
            }
            return OrderResource::collection($this->orderService->list($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function show(Order $order): \Illuminate\Http\Response | OrderDetailsResource | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            if ((int) $order->source !== Source::WHATSAPP) {
                return response(['status' => false, 'message' => trans('all.message.not_found')], 404);
            }
            return new OrderDetailsResource($this->orderService->show($order, false));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function destroy(Order $order): \Illuminate\Http\Response | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            if ((int) $order->source !== Source::WHATSAPP) {
                return response(['status' => false, 'message' => trans('all.message.not_found')], 404);
            }
            $this->orderService->destroy($order);
            return response('', 202);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function export(PaginateRequest $request): \Illuminate\Http\Response | \Symfony\Component\HttpFoundation\BinaryFileResponse | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            if (!$request->filled('source')) {
                $request->merge(['source' => Source::WHATSAPP]);
            }
            return Excel::download(new OrderExport($this->orderService, $request), 'Whatsapp-Orders.xlsx');
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function changeStatus(Order $order, OrderStatusRequest $request): \Illuminate\Http\Response | OrderDetailsResource | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            if ((int) $order->source !== Source::WHATSAPP) {
                return response(['status' => false, 'message' => trans('all.message.not_found')], 404);
            }
            return new OrderDetailsResource($this->orderService->changeStatus($order, $request, false));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function changePaymentStatus(Order $order, PaymentStatusRequest $request): \Illuminate\Http\Response | OrderDetailsResource | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            if ((int) $order->source !== Source::WHATSAPP) {
                return response(['status' => false, 'message' => trans('all.message.not_found')], 404);
            }
            return new OrderDetailsResource($this->orderService->changePaymentStatus($order, $request, false));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
