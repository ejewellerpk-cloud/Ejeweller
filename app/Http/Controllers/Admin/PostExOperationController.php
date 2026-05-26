<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PostExApiException;
use App\Http\Requests\PostExShipperAdviceRequest;
use App\Services\PostEx\PostExApiClient;
use App\Services\PostEx\PostExSettingsService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PostExOperationController extends AdminController implements HasMiddleware
{
    public function __construct(
        protected PostExApiClient $postExApiClient,
        protected PostExSettingsService $postExSettingsService
    ) {
        parent::__construct();
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings', only: [
                'operationalCities',
                'merchantAddresses',
                'orderTypes',
                'orderStatuses',
                'unbookedOrders',
                'listOrders',
            ]),
            new Middleware('permission:online-orders', only: [
                'saveShipperAdvice',
                'getShipperAdvice',
                'generateLoadSheet',
            ]),
        ];
    }

    public function operationalCities(Request $request): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        return $this->respond(fn () => $this->postExApiClient->getOperationalCities($request->query('operationalCityType')));
    }

    public function merchantAddresses(Request $request): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        return $this->respond(fn () => $this->postExApiClient->getMerchantAddresses($request->query('cityName')));
    }

    public function orderTypes(): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        return $this->respond(fn () => $this->postExApiClient->getOrderTypes());
    }

    public function orderStatuses(): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        return $this->respond(fn () => $this->postExApiClient->getOrderStatuses());
    }

    public function unbookedOrders(Request $request): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        $request->validate([
            'startDate' => ['required', 'date'],
            'endDate'   => ['required', 'date', 'after_or_equal:startDate'],
            'cityName'  => ['nullable', 'string', 'max:100'],
        ]);

        return $this->respond(fn () => $this->postExApiClient->getUnbookedOrders(
            $request->query('startDate'),
            $request->query('endDate'),
            $request->query('cityName')
        ));
    }

    public function listOrders(Request $request): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        $request->validate([
            'orderStatusID' => ['required', 'integer', 'min:0'],
            'fromDate'      => ['required', 'date'],
            'toDate'        => ['required', 'date', 'after_or_equal:fromDate'],
        ]);

        return $this->respond(fn () => $this->postExApiClient->listOrders(
            (int) $request->query('orderStatusID'),
            $request->query('fromDate'),
            $request->query('toDate')
        ));
    }

    public function saveShipperAdvice(PostExShipperAdviceRequest $request, string $trackingNumber): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        return $this->respond(fn () => $this->postExApiClient->saveShipperAdvice(
            $trackingNumber,
            (int) $request->validated('status_id'),
            (string) $request->validated('remarks')
        ));
    }

    public function getShipperAdvice(string $trackingNumber): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        return $this->respond(fn () => $this->postExApiClient->getShipperAdvice($trackingNumber));
    }

    public function generateLoadSheet(Request $request): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        $request->validate([
            'trackingNumbers'   => ['required', 'array', 'min:1'],
            'trackingNumbers.*' => ['required', 'string', 'max:64'],
            'pickupAddress'     => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->assertEnabled();
            $response = $this->postExApiClient->generateLoadSheet(
                $request->input('trackingNumbers'),
                $request->input('pickupAddress')
            );

            return response($response->body(), 200, [
                'Content-Type'        => $response->header('Content-Type') ?? 'application/pdf',
                'Content-Disposition' => 'inline; filename="postex-load-sheet.pdf"',
            ]);
        } catch (PostExApiException $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    protected function respond(callable $callback): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            $this->assertEnabled();
            $data = $callback();

            return response([
                'status' => true,
                'data'   => $data['dist'] ?? $data,
            ]);
        } catch (PostExApiException $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    /**
     * @throws Exception
     */
    protected function assertEnabled(): void
    {
        if (!$this->postExSettingsService->isEnabled()) {
            throw new Exception('PostEx integration is disabled or not configured.', 422);
        }
    }
}
