<?php

namespace App\Libraries;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;
use InvalidArgumentException;
use App\Enums\CurrencyPosition;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\File;

class AppLibrary
{
    public static function date($date, $pattern = null): string
    {
        if (!$pattern) {
            $pattern = env('DATE_FORMAT');
        }
        return Carbon::parse($date)->format($pattern);
    }

    public static function time($time, $pattern = null): string
    {
        if (!$pattern) {
            $pattern = env('TIME_FORMAT');
        }
        return Carbon::parse($time)->format($pattern);
    }

    public static function datetime($dateTime, $pattern = null): string
    {
        if (!$pattern) {
            $pattern = env('TIME_FORMAT') . ', ' . env('DATE_FORMAT');
        }
        return Carbon::parse($dateTime)->format($pattern);
    }

    public static function increaseDate($dateTime, $days, $pattern = null): string
    {
        if (!$pattern) {
            $pattern = env('DATE_FORMAT');
        }
        return Carbon::parse($dateTime)->addDays($days)->format($pattern);
    }

    public static function deliveryTime($dateTime, $pattern = null): string
    {
        if (!$pattern) {
            $pattern = env('TIME_FORMAT');
        }
        $explode = explode('-', $dateTime);
        if (count($explode) == 2) {
            return Carbon::parse(trim($explode[0]))->format($pattern) . ' - ' . Carbon::parse(
                trim($explode[1])
            )->format($pattern);
        }
        return '';
    }

    public static function associativeToNumericArrayBuilder($array): array
    {
        $i = 1;
        $buildArray = [];
        if (count($array)) {
            foreach ($array as $arr) {
                if (isset($arr['children'])) {
                    $children = $arr['children'];
                    unset($arr['children']);

                    $arr['parent'] = 0;
                    $buildArray[$i] = $arr;
                    $parentId = $i;
                    $i++;
                    foreach ($children as $child) {
                        $child['parent'] = $parentId;
                        $buildArray[$i] = $child;
                        $i++;
                    }
                } else {
                    $arr['parent'] = 0;
                    $buildArray[$i] = $arr;
                    $i++;
                }
            }
        }
        return $buildArray;
    }

    public static function numericToAssociativeArrayBuilder($array): array
    {
        $buildArray = [];
        $indexedParents = [];

        if (count($array)) {
            foreach ($array as $arr) {
                if (!(int) ($arr['parent'] ?? 0)) {
                    $arr['children'] = [];
                    $buildArray[$arr['id']] = $arr;
                    $indexedParents[$arr['id']] = &$buildArray[$arr['id']];
                }
            }

            foreach ($array as $arr) {
                $parentId = (int) ($arr['parent'] ?? 0);
                if ($parentId > 0 && isset($indexedParents[$parentId])) {
                    $indexedParents[$parentId]['children'][] = $arr;
                }
            }
        }

        $buildArray = array_values($buildArray);
        foreach ($buildArray as $key => $build) {
            if ($build['url'] == "#" && empty($build['children'])) {
                unset($buildArray[$key]);
            }
        }

        return array_values($buildArray);
    }

    public static function permissionWithAccess(&$permissions, $rolePermissions): object
    {
        if ($permissions) {
            foreach ($permissions as $permission) {
                if (isset($rolePermissions[$permission->id])) {
                    $permission->access = true;
                } else {
                    $permission->access = false;
                }
            }
        }
        return $permissions;
    }

    public static function menu(&$menus, $permissions): array
    {
        if ($menus && $permissions) {
            foreach ($menus as $key => $menu) {
                if (isset($permissions[$menu['url']]) && !$permissions[$menu['url']]['access']) {
                    if ($menu['url'] != '#') {
                        unset($menus[$key]);
                    }
                }
            }
        }
        return $menus;
    }

    public static function pluck($array, $value, $key = null, $type = 'object'): array
    {
        $returnArray = [];
        if ($array) {
            foreach ($array as $item) {
                if ($key != null) {
                    if ($type == 'array') {
                        $returnArray[$item[$key]] = strtolower($value) == 'obj' ? $item : $item[$value];
                    } else {
                        $returnArray[$item[$key]] = strtolower($value) == 'obj' ? $item : $item->$value;
                    }
                } elseif ($value == 'obj') {
                    $returnArray[] = $item;
                } elseif ($type == 'array') {
                    $returnArray[] = $item[$value];
                } else {
                    $returnArray[] = $item->$value;
                }
            }
        }
        return $returnArray;
    }

    public static function username($name)
    {
        if ($name) {
            $username = strtolower(str_replace(' ', '', $name)) . rand(1, 999999);
            if (User::where(['username' => $username])->first()) {
                self::username($name);
            }
            return $username;
        }
    }

    public static function name($firstName, $lastName): string
    {
        return $firstName . ' ' . $lastName;
    }

    public static function amountCheck($amount, $attr = 'price'): object
    {
        $response = [
            'status'  => true,
            'message' => ''
        ];

        if (!is_numeric($amount)) {
            $response['status'] = false;
            $response['message'] = "This {$attr} must be integer.";
        }

        if ($amount <= 0) {
            if (!$response['status']) {
                return (object)$response;
            } else {
                $response['status'] = false;
                $response['message'] = "This {$attr} negative amount not allow.";
            }
        }

        $replaceValue = str_replace('.', '', $amount);
        if (strlen($replaceValue) > 12) {
            if (!$response['status']) {
                return (object)$response;
            } else {
                $response['status'] = false;
                $response['message'] = "This {$attr} length can't be greater than 12 digit.";
            }
        }

        return (object)$response;
    }

    public static function currencyAmountFormat($amount): string
    {
        if (env('CURRENCY_POSITION') == CurrencyPosition::LEFT) {
            return env('CURRENCY_SYMBOL') . number_format($amount, (int)env('CURRENCY_DECIMAL_POINT'), '.', '');
        }
        return number_format($amount, (int)env('CURRENCY_DECIMAL_POINT'), '.', '') . env('CURRENCY_SYMBOL');
    }

    public static function flatAmountFormat($amount): string
    {
        return number_format($amount, (int)env('CURRENCY_DECIMAL_POINT'), '.', '');
    }

    public static function convertAmountFormat($amount): float
    {
        return (float)number_format($amount, (int)env('CURRENCY_DECIMAL_POINT'), '.', '');
    }

    public static function fcmDataBind($request): void
    {
        $cdn = public_path("firebase-cdn.txt");
        $textContent = public_path("firebase-content.txt");
        $file = public_path("firebase-messaging-sw.js");
        $content = 'let config = {
        apiKey: "' . $request->notification_fcm_api_key . '",
        authDomain: "' . $request->notification_fcm_auth_domain . '",
        projectId: "' . $request->notification_fcm_project_id . '",
        storageBucket: "' . $request->notification_fcm_storage_bucket . '",
        messagingSenderId: "' . $request->notification_fcm_messaging_sender_id . '",
        appId: "' . $request->notification_fcm_app_id . '",
        measurementId: "' . $request->notification_fcm_measurement_id . '",' . "\n" . ' };' . "\n";
        File::put($file, File::get($cdn) . $content . File::get($textContent));
    }

    public static function defaultPermission($permissions)
    {
        $defaultPermission = (object)[];
        if (count($permissions)) {
            foreach ($permissions as $permission) {
                if ($permission->access) {
                    $defaultPermission = $permission;
                    break;
                }
            }
        }
        return $defaultPermission;
    }

    public static function defaultMenu($menus, $defaulPermission): array
    {
        foreach ($menus as $menu) {
            if (isset($menu['url']) && $menu['url'] === $defaulPermission->url) {
                return $menu;
            }
            if (isset($menu['children']) && is_array($menu['children']) && count($menu['children']) > 0) {
                $found = self::defaultMenu($menu['children'], $defaulPermission);
                if (!empty($found)) {
                    return $found;
                }
            }
        }
        return [];
    }

    public static function domain($input): array|string|null
    {
        $input = trim($input, '/');
        if (!preg_match('#^http(s)?://#', $input)) {
            $input = 'http://' . $input;
        }
        $urlParts = parse_url($input);

        $link = '';
        if (isset($urlParts['port'])) {
            $link .= ':' . $urlParts['port'];
        }

        if (isset($urlParts['path'])) {
            $link .= $urlParts['path'];
        }

        return preg_replace('/^www\./', '', ($urlParts['host'] . $link));
    }

    public static function licenseApiResponse($response)
    {
        $header = explode(';', $response->getHeader('Content-Type')[0]);
        $contentType = $header[0];
        if ($contentType == 'application/json') {
            $contents = $response->getBody()->getContents();
            $data = json_decode($contents);
            if (json_last_error() == JSON_ERROR_NONE) {
                return $data;
            }
            return $contents;
        }

        return ['status' => false, 'message' => 'something wrong'];
    }


    public static function deleteDir($dirPath): void
    {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (!str_ends_with($dirPath, '/')) {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    public static function sku($sku)
    {
        $productVariation = ProductVariation::where(['sku' => $sku])->first();
        $product = Product::where(['sku' => $sku])->first();
        if ($productVariation || $product) {
            self::sku(rand(1000000, 9999999));
        }
        return $sku;
    }

    public static function recursive($elements, $parentId = 0): array
    {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = self::recursive($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }

        return $branch;
    }

    public static function tagString($arrays): string
    {
        $string = '';
        $i = 1;
        $count = count($arrays);
        if (count($arrays) > 0) {
            foreach ($arrays as $array) {
                if ($i == $count) {
                    $string .= $array->name;
                } else {
                    $string .= $array->name . ', ';
                }
                $i++;
            }
        }
        return $string;
    }

    public static function taxString($arrays): string
    {
        $string = '';
        $i = 1;
        $count = count($arrays);
        if (count($arrays) > 0) {
            foreach ($arrays as $array) {
                if ($i == $count) {
                    $string .= $array?->tax?->name;
                } else {
                    $string .= $array?->tax?->name . ', ';
                }
                $i++;
            }
        }
        return $string;
    }

    public static function lowerWithReplaceToSpace($string): string
    {
        return strtolower(str_replace($string, '', ' '));
    }

    public static function reportCurrencyAmountFormat($amount): string
    {
        return number_format($amount, (int)env('CURRENCY_DECIMAL_POINT'), '.', ',');
    }

    public static function textShortener($text, $number = 30)
    {
        if ($text && mb_strlen($text) > $number) {
            return mb_substr($text, 0, $number) . "..";
        }
        return $text;
    }

    public static function isBetweenDate($startDate, $endDate)
    {
        if (!$startDate || !$endDate) {
            return false;
        }
        $startDate = Carbon::parse($startDate);
        $endDate   = Carbon::parse($endDate);
        return Carbon::now()->between($startDate, $endDate);
    }

    public static function isProductOfferActive($discount, $startDate = null, $endDate = null): bool
    {
        if ((float) $discount <= 0) {
            return false;
        }

        if (empty($startDate) || empty($endDate)) {
            return true;
        }

        return self::isBetweenDate($startDate, $endDate);
    }

    public static function productOfferPrice(float $price, $discount, $startDate = null, $endDate = null): float
    {
        if (!self::isProductOfferActive($discount, $startDate, $endDate)) {
            return $price;
        }

        return $price - (($price / 100) * (float) $discount);
    }

    /**
     * Real social proof counts from cart_trackers and orders (last 24 hours).
     */
    public static function buildOrderTimeline($order): array
    {
        if (!$order instanceof \App\Models\Order) {
            return [];
        }

        $placedAt = self::datetime($order->order_datetime);
        $updatedAt = self::datetime($order->updated_at);
        $currentStatus = (int) $order->status;

        if ($currentStatus === \App\Enums\OrderStatus::CANCELED) {
            return [
                ['status' => \App\Enums\OrderStatus::PENDING, 'date' => $placedAt],
                ['status' => \App\Enums\OrderStatus::CANCELED, 'date' => $updatedAt],
            ];
        }

        if ($currentStatus === \App\Enums\OrderStatus::REJECTED) {
            return [
                ['status' => \App\Enums\OrderStatus::PENDING, 'date' => $placedAt],
                ['status' => \App\Enums\OrderStatus::REJECTED, 'date' => $updatedAt],
            ];
        }

        $steps = $order->order_type === \App\Enums\OrderType::PICK_UP
            ? [
                \App\Enums\OrderStatus::PENDING,
                \App\Enums\OrderStatus::CONFIRMED,
                \App\Enums\OrderStatus::DELIVERED,
            ]
            : [
                \App\Enums\OrderStatus::PENDING,
                \App\Enums\OrderStatus::CONFIRMED,
                \App\Enums\OrderStatus::ON_THE_WAY,
                \App\Enums\OrderStatus::DELIVERED,
            ];

        $timeline = [];
        foreach ($steps as $stepStatus) {
            if ($stepStatus <= $currentStatus) {
                $timeline[] = [
                    'status' => $stepStatus,
                    'date'   => $stepStatus === $currentStatus ? $updatedAt : $placedAt,
                ];
            }
        }

        return $timeline;
    }

    public static function productSocialProofStats($product): array
    {
        if (!$product) {
            return ['in_baskets' => 0, 'bought_last_24_hours' => 0];
        }

        if (!($product instanceof \App\Models\Product)) {
            $product = \App\Models\Product::query()->find((int) $product);
        }

        if (!$product) {
            return ['in_baskets' => 0, 'bought_last_24_hours' => 0];
        }

        return [
            'in_baskets'           => (int) $product->cartTrackers()->count(),
            'bought_last_24_hours' => (int) abs(
                $product->productOrders()->where('created_at', '>=', now()->subDay())->sum('quantity')
            ),
        ];
    }

    public static function appVersion(){
        return config('product.version');
    }
}
