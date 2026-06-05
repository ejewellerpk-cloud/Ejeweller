<?php

namespace App\Http\Controllers\Auth;


use App\Models\User;
use App\Enums\Status;
use Illuminate\Http\Request;
use App\Libraries\AppLibrary;
use App\Services\MenuService;
use Illuminate\Http\JsonResponse;
use App\Services\PermissionService;
use App\Http\Controllers\Controller;
use App\Http\Resources\MenuResource;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Services\DefaultAccessService;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\PermissionResource;
use App\Services\OtpManagerService;
use App\Services\AuthTokenService;

class LoginController extends Controller
{
    public string $token;
    public DefaultAccessService $defaultAccessService;
    public PermissionService $permissionService;
    public MenuService $menuService;
    public OtpManagerService $otpManagerService;
    public AuthTokenService $authTokenService;

    public function __construct(
        MenuService          $menuService,
        PermissionService    $permissionService,
        DefaultAccessService $defaultAccessService,
        OtpManagerService    $otpManagerService,
        AuthTokenService     $authTokenService
    ) {
        $this->menuService          = $menuService;
        $this->permissionService    = $permissionService;
        $this->defaultAccessService = $defaultAccessService;
        $this->otpManagerService    = $otpManagerService;
        $this->authTokenService     = $authTokenService;
    }

    /**
     * @throws \Exception
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email'        => $request['phone'] ? ['nullable', 'string', 'email', 'max:255'] : ['required', 'string', 'email', 'max:255'],
                'phone'        => $request['email'] ? ['nullable', 'string', 'max:20'] : ['required', 'string', 'max:20'],
                'country_code' => $request['email'] ? ['nullable', 'string', 'max:20'] : ['required', 'string', 'max:20'],
                'password'     => ['required', 'string', 'min:6'],
            ],
        );

        if ($validator->fails()) {
            if (!$request['email'] && !$request['phone']) {
                return new JsonResponse([
                    'errors' => [
                        'email_or_phone' => trans('all.message.email_or_phone_required'),
                    ] + $validator->errors()->toArray()
                ], 422);
            } else {
                return new JsonResponse([
                    'errors' => $validator->errors()
                ], 422);
            }
        }

        $request->merge(['status' => Status::ACTIVE]);

        if ($request['email']) {
            if (!Auth::guard('web')->attempt($request->only('email', 'password', 'status'))) {
                return new JsonResponse([
                    'errors' => ['validation' => trans('all.message.credentials_invalid')]
                ], 400);
            }
            $user = User::where('email', $request['email'])->first();
        } else {
            if (!Auth::guard('web')->attempt($request->only('country_code', 'phone', 'password', 'status'))) {
                return new JsonResponse([
                    'errors' => ['validation' => trans('all.message.credentials_invalid')]
                ], 400);
            }
            $user = User::where(['phone' => $request['phone'], 'country_code' => $request->country_code])->first();
        }

        $this->token = $this->authTokenService->issueToken($user, $request, $request->input('device_name'));

        if (!isset($user->roles[0])) {
            return new JsonResponse([
                'errors' => ['validation' => trans('all.message.role_exist')]
            ], 400);
        }

        $permission        = PermissionResource::collection($this->permissionService->permission($user->roles[0]));
        $defaultPermission = AppLibrary::defaultPermission($permission);
        $defaultMenu       = (object)AppLibrary::defaultMenu($this->menuService->menu($user->roles[0]), $defaultPermission);

        return new JsonResponse([
            'message'           => trans('all.message.login_success'),
            'token'             => $this->token,
            'user'              => new UserResource($user),
            'menu'              => MenuResource::collection(collect($this->menuService->menu($user->roles[0]))),
            'permission'        => $permission,
            'defaultPermission' => $defaultPermission,
            'defaultMenu'       => $defaultMenu,
        ], 201);
    }

    public function otpSend(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email'        => $request['phone'] ? ['nullable', 'string', 'email', 'max:255'] : ['required', 'string', 'email', 'max:255'],
                'phone'        => $request['email'] ? ['nullable', 'string', 'max:20'] : ['required', 'string', 'max:20'],
                'country_code' => $request['email'] ? ['nullable', 'string', 'max:20'] : ['required', 'string', 'max:20'],
            ],
        );

        if ($validator->fails()) {
            if (!$request['email'] && !$request['phone']) {
                return new JsonResponse([
                    'errors' => [
                        'email_or_phone' => trans('all.message.email_or_phone_required'),
                    ] + $validator->errors()->toArray()
                ], 422);
            } else {
                return new JsonResponse([
                    'errors' => $validator->errors()
                ], 422);
            }
        }

        // Verify if user exists
        if ($request['email']) {
            $user = User::where('email', $request['email'])->first();
        } else {
            $user = User::where(['phone' => $request['phone'], 'country_code' => $request['country_code']])->first();
        }

        if (!$user) {
            return new JsonResponse([
                'errors' => ['email_or_phone' => trans('all.message.user_does_not_exist')]
            ], 422);
        }

        if ($user->status !== Status::ACTIVE) {
            return new JsonResponse([
                'errors' => ['validation' => trans('all.message.user_not_active')]
            ], 400);
        }

        try {
            if ($request['email']) {
                $this->otpManagerService->otpEmail($request);
                return new JsonResponse([
                    'message' => trans('all.message.check_your_email_for_code')
                ], 200);
            } else {
                $this->otpManagerService->otpPhone($request);
                return new JsonResponse([
                    'message' => trans('all.message.check_your_phone_for_code')
                ], 200);
            }
        } catch (\Exception $exception) {
            return new JsonResponse([
                'errors' => ['validation' => $exception->getMessage()]
            ], 422);
        }
    }

    public function otpVerify(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email'        => $request['phone'] ? ['nullable', 'string', 'email', 'max:255'] : ['required', 'string', 'email', 'max:255'],
                'phone'        => $request['email'] ? ['nullable', 'string', 'max:20'] : ['required', 'string', 'max:20'],
                'country_code' => $request['email'] ? ['nullable', 'string', 'max:20'] : ['required', 'string', 'max:20'],
                'token'        => ['required', 'string'],
            ],
        );

        if ($validator->fails()) {
            return new JsonResponse(['errors' => $validator->errors()], 422);
        }

        try {
            // Verify OTP using OtpManagerService
            if ($request['email']) {
                $verifyRequest = app(\App\Http\Requests\VerifyEmailRequest::class);
                $this->otpManagerService->verifyEmail($verifyRequest);

                $user = User::where('email', $request['email'])->first();
            } else {
                $verifyRequest = app(\App\Http\Requests\VerifyPhoneRequest::class);
                $this->otpManagerService->verifyPhone($verifyRequest);

                $user = User::where(['phone' => $request['phone'], 'country_code' => $request['country_code']])->first();
            }

            if (!$user) {
                return new JsonResponse([
                    'errors' => ['email_or_phone' => trans('all.message.user_does_not_exist')]
                ], 422);
            }

            if ($user->status !== Status::ACTIVE) {
                return new JsonResponse([
                    'errors' => ['validation' => trans('all.message.user_not_active')]
                ], 400);
            }

            // Authentication and token creation
            Auth::guard('web')->loginUsingId($user->id);
            $this->token = $this->authTokenService->issueToken($user, $request, $request->input('device_name'));

            if (!isset($user->roles[0])) {
                return new JsonResponse([
                    'errors' => ['validation' => trans('all.message.role_exist')]
                ], 400);
            }

            $permission        = PermissionResource::collection($this->permissionService->permission($user->roles[0]));
            $defaultPermission = AppLibrary::defaultPermission($permission);
            $defaultMenu       = (object)AppLibrary::defaultMenu($this->menuService->menu($user->roles[0]), $defaultPermission);

            return new JsonResponse([
                'message'           => trans('all.message.login_success'),
                'token'             => $this->token,
                'user'              => new UserResource($user),
                'menu'              => MenuResource::collection(collect($this->menuService->menu($user->roles[0]))),
                'permission'        => $permission,
                'defaultPermission' => $defaultPermission,
                'defaultMenu'       => $defaultMenu,
            ], 201);

        } catch (\Exception $exception) {
            return new JsonResponse([
                'errors' => ['token' => [$exception->getMessage()]]
            ], 422);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()?->id;

        if ($request->boolean('all_devices')) {
            app(\App\Services\UserSessionService::class)->revokeAllExcept($user);
        } elseif ($currentTokenId) {
            $user->tokens()->where('id', $currentTokenId)->delete();
        }

        return new JsonResponse([
            'message' => trans('all.message.logout_success')
        ], 200);
    }
}
