<?php

namespace App\Services;

use Exception;
use App\Enums\Activity;
use App\Enums\GatewayMode;
use App\Enums\InputType;
use App\Models\GatewayOption;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Log;
use Dipokhalder\EnvEditor\EnvEditor;
use App\Http\Requests\PaginateRequest;
use App\Libraries\QueryExceptionLibrary;

class PaymentGatewayService
{
    public EnvEditor $envService;

    public function __construct(EnvEditor $envEditor)
    {
        $this->envService = $envEditor;
    }

    public ?object $gateway = null;
    protected array $paymentGatewayFilter = [
        'name',
        'slug',
    ];

    protected array $exceptFilter = [
        'excepts'
    ];

    /**
     * @throws Exception
     */
    public function list(PaginateRequest $request)
    {
        try {
            $requests    = $request->all();
            $method      = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue = $request->get('paginate', 0) == 1 ? $request->get('per_page', 10) : '*';
            $orderColumn = $request->get('order_column') ?? 'id';
            $orderType   = $request->get('order_type') ?? 'asc';

            return PaymentGateway::with('gatewayOptions', 'media')->where(function ($query) use ($requests) {
                foreach ($requests as $key => $request) {
                    if ($key === 'status' && $request !== '' && $request !== null) {
                        $query->where('status', (int) $request);
                        continue;
                    }

                    if (in_array($key, $this->paymentGatewayFilter)) {
                        $query->where($key, 'like', '%' . $request . '%');
                    }

                    if (in_array($key, $this->exceptFilter)) {
                        $explodes = explode('|', $request);
                        if (is_array($explodes)) {
                            foreach ($explodes as $explode) {
                                $query->where('id', '!=', $explode);
                            }
                        }
                    }
                }
            })->orderBy($orderColumn, $orderType)->$method(
                $methodValue
            );
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function update(array $validationRequests, ?string $slug = null): object
    {
        try {
            $this->gateway = PaymentGateway::where('slug', $slug)->first();
            if (blank($this->gateway)) {
                throw new Exception('Payment gateway not found.');
            }

            $morphType = $this->gateway->getMorphClass();

            foreach ($validationRequests as $key => $value) {
                if ($key === 'payment_type' || !is_string($key) || $key === '') {
                    continue;
                }

                if ($value === null) {
                    continue;
                }

                $value = is_scalar($value) ? (string) $value : json_encode($value);

                $options = GatewayOption::where('option', $key)
                    ->where('model_id', $this->gateway->id)
                    ->get();

                if ($options->isEmpty()) {
                    $options = GatewayOption::where('option', $key)->get()->filter(function ($row) {
                        return (int) $row->model_id === (int) $this->gateway->id;
                    });
                }

                if ($options->isEmpty()) {
                    GatewayOption::create([
                        'model_id' => $this->gateway->id,
                        'model_type' => $morphType,
                        'option' => $key,
                        'value' => $value,
                        'type' => $this->guessType($key),
                        'activities' => $this->defaultActivities($key),
                    ]);
                    continue;
                }

                foreach ($options as $option) {
                    $option->model_id = $this->gateway->id;
                    $option->model_type = $morphType;
                    $option->value = $value;
                    $option->type = $this->guessType($key, $option->type);
                    if (blank($option->activities) || $option->activities === '""' || $option->activities === '[]') {
                        $option->activities = $this->defaultActivities($key);
                    }
                    $option->save();
                }
            }

            $statusKey = $this->gateway->slug . '_status';
            if (array_key_exists($statusKey, $validationRequests) && $validationRequests[$statusKey] !== null && $validationRequests[$statusKey] !== '') {
                $this->gateway->status = (int) $validationRequests[$statusKey];
                $this->gateway->save();
            }

            if ($this->gateway->slug === 'swich' && (int) $this->gateway->status === Activity::ENABLE) {
                $this->ensureSwichMethodsEnabled();
                $this->enableOnlinePaymentsSetting();
            } elseif ((int) $this->gateway->status === Activity::ENABLE && !in_array($this->gateway->slug, ['cashondelivery', 'credit'], true)) {
                $this->enableOnlinePaymentsSetting();
            }

            return $this->gateway->fresh(['gatewayOptions', 'media']);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    protected function guessType(string $key, mixed $current = null): int
    {
        if (str_ends_with($key, '_status') || str_ends_with($key, '_mode')) {
            return InputType::SELECT;
        }

        return $current ? (int) $current : InputType::TEXT;
    }

    protected function defaultActivities(string $key): string
    {
        if (str_ends_with($key, '_mode')) {
            return json_encode([
                GatewayMode::SANDBOX => 'sandbox',
                GatewayMode::LIVE => 'live',
            ]);
        }

        if (str_ends_with($key, '_status')) {
            return json_encode([
                Activity::ENABLE => 'enable',
                Activity::DISABLE => 'disable',
            ]);
        }

        return '';
    }

    protected function ensureSwichMethodsEnabled(): void
    {
        $ewallet = $this->gateway->gatewayOptions()->where('option', 'swich_ewallet_status')->first();
        $biller = $this->gateway->gatewayOptions()->where('option', 'swich_biller_status')->first();
        $ewalletOn = (int) ($ewallet?->value ?? Activity::DISABLE) === Activity::ENABLE;
        $billerOn = (int) ($biller?->value ?? Activity::DISABLE) === Activity::ENABLE;

        if ($ewalletOn || $billerOn) {
            return;
        }

        if ($ewallet) {
            $ewallet->value = (string) Activity::ENABLE;
            $ewallet->save();
        }
    }

    protected function enableOnlinePaymentsSetting(): void
    {
        try {
            \Dipokhalder\Settings\Facades\Settings::group('site')->set([
                'site_online_payment_gateway' => Activity::ENABLE,
            ]);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
        }
    }
}
