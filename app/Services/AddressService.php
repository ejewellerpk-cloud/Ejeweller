<?php

namespace App\Services;


use App\Http\Requests\PaginateRequest;
use Exception;
use App\Models\Address;
use App\Models\OrderAddress;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddressRequest;
use App\Libraries\QueryExceptionLibrary;

class AddressService
{

    public $addressFilter = ['full_name', 'email', 'country_code', 'phone', 'country', 'city', 'state', 'zip_code', 'address'];

    /**
     * @throws Exception
     */
    public function list(PaginateRequest $request)
    {
        try {
            $userId = (int) auth()->user()->id;

            // Older guest checkouts only wrote order_addresses — import once into address book.
            if (!Address::where('user_id', $userId)->exists()) {
                $this->backfillFromLastOrderAddress($userId);
            }

            $requests    = $request->all();
            $method      = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue = $request->get('paginate', 0) == 1 ? $request->get('per_page', 10) : '*';
            $orderColumn = $request->get('order_column') ?? 'id';
            $orderType   = $request->get('order_type') ?? 'desc';

            return Address::query()
                ->select([
                    'id',
                    'user_id',
                    'full_name',
                    'email',
                    'country_code',
                    'phone',
                    'address',
                    'country',
                    'state',
                    'city',
                    'zip_code',
                    'latitude',
                    'longitude',
                ])
                ->where('user_id', $userId)
                ->where(function ($query) use ($requests) {
                    foreach ($requests as $key => $request) {
                        if (in_array($key, $this->addressFilter)) {
                            $query->where($key, 'like', '%' . $request . '%');
                        }
                    }
                })
                ->orderBy($orderColumn, $orderType)
                ->$method($methodValue);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    private function backfillFromLastOrderAddress(int $userId): void
    {
        try {
            $last = OrderAddress::query()
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->first();

            if (!$last || trim((string) $last->address) === '' || trim((string) $last->phone) === '') {
                return;
            }

            Address::create([
                'user_id'      => $userId,
                'full_name'    => $last->full_name,
                'email'        => $last->email,
                'country_code' => $last->country_code,
                'phone'        => $last->phone,
                'country'      => $last->country,
                'state'        => $last->state,
                'city'         => $last->city,
                'zip_code'     => $last->zip_code,
                'address'      => $last->address,
                'latitude'     => $last->latitude,
                'longitude'    => $last->longitude,
            ]);
        } catch (Exception $exception) {
            Log::info('address backfill skipped: ' . $exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function store(AddressRequest $request)
    {
        try {
            return Address::create($request->validated() + ['user_id' => Auth::user()->id]);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function update(AddressRequest $request, Address $address)
    {
        try {
            return tap($address)->update($request->validated());
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function destroy(Address $address): void
    {
        try {
            $address->delete();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
