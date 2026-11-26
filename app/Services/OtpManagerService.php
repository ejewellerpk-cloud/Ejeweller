<?php

namespace App\Services;

use App\Enums\Ask;
use Exception;
use Carbon\Carbon;
use App\Models\Otp;
use App\Enums\OtpType;
use App\Events\SendSmsCode;
use Illuminate\Http\Request;
use App\Events\SendEmailCode;
use Illuminate\Support\Facades\DB;
use App\Events\SendVerifyEmailCode;
use Illuminate\Support\Facades\Log;
use Dipokhalder\Settings\Facades\Settings;
use App\Http\Requests\VerifyEmailRequest;
use App\Http\Requests\VerifyPhoneRequest;
use App\Libraries\QueryExceptionLibrary;

class OtpManagerService
{

    /**
     * @throws Exception
     */
    public function otpPhone(Request $request): bool
    {
        try {
            if (env('DEMO') == "True" || env('DEMO') == "TRUE" || env('DEMO') == "true" || env('DEMO') == 1) {
                return true;
            }
            $otp = DB::table('otps')->where([
                ['phone', $request->post('phone')],
                ['code', $request->post('country_code')],
            ]);

            if ($otp->exists()) {
                $otp->delete();
            }

            $token = $this->generateOtpToken();

            $otp = Otp::create([
                'phone' => $request->phone,
                'code' => $request->country_code,
                'token' => $token,
                'created_at' => now(),
            ]);

            if (!blank($otp)) {
                SendSmsCode::dispatch(
                    ['phone' => $request->post('phone'), 'code' => $request->post('country_code'), 'token' => $token]
                );
            }

            return true;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function resetOtpEmail(Request $request): bool
    {
        try {
            if (env('DEMO') == "True" || env('DEMO') == "TRUE" || env('DEMO') == "true" || env('DEMO') == 1) {
                return true;
            }
            $otp = DB::table('password_reset_tokens')->where([
                ['email', $request->post('email')]
            ]);

            if ($otp->exists()) {
                $otp->delete();
            }

            $token = $this->generateOtpToken();

            $password_reset = DB::table('password_reset_tokens')->insert([
                'email' => $request->post('email'),
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            if (!blank($password_reset)) {
                SendEmailCode::dispatch(['email' => $request->post('email'), 'pin' => $token]);
            }

            return true;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function otpEmail(Request $request): bool
    {
        try {
            if (env('DEMO') == "True" || env('DEMO') == "TRUE" || env('DEMO') == "true" || env('DEMO') == 1) {
                return true;
            }
            $otp = DB::table('password_reset_tokens')->where([
                ['email', $request->post('email')]
            ]);

            if ($otp->exists()) {
                $otp->delete();
            }

            $token = $this->generateOtpToken();

            $password_reset = DB::table('password_reset_tokens')->insert([
                'email' => $request->post('email'),
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            if (!blank($password_reset)) {
                SendVerifyEmailCode::dispatch(['email' => $request->post('email'), 'pin' => $token]);
            }

            return true;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function verifyPhone(VerifyPhoneRequest $request): bool
    {
        try {
            if (env('DEMO') == "True" || env('DEMO') == "TRUE" || env('DEMO') == "true" || env('DEMO') == 1) {
                return true;
            }

            $otp = DB::table('otps')->where([
                ['phone', $request->post('phone')],
                ['token', $request->post('token')],
            ]);
            if ($otp->exists()) {
                $difference = Carbon::now()->diffInSeconds($otp->first()->created_at);
                if ($difference > (int) Settings::group('otp')->get('otp_expire_time') * 60) {
                    throw new Exception(trans('all.message.code_is_expired'), 422);
                } else {
                    DB::table('otps')->where([
                        ['phone', $request->post('phone')],
                        ['token', $request->post('token')],
                    ])->update(['is_verified' => Ask::YES]);

                    return true;
                }
            } else {
                throw new Exception(trans('all.message.code_is_invalid'), 422);
            }
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function verifyEmail(VerifyEmailRequest $request): bool
    {
        try {
            if (env('DEMO') == "True" || env('DEMO') == "TRUE" || env('DEMO') == "true" || env('DEMO') == 1) {
                return true;
            }

            $verify = DB::table('password_reset_tokens')->where([
                ['email', $request->post('email')],
                ['token', $request->post('token')],
            ]);
            if ($verify->exists()) {
                $difference = Carbon::now()->diffInSeconds($verify->first()->created_at);
                if ($difference > (int) Settings::group('otp')->get('otp_expire_time') * 60) {
                    throw new Exception(trans('all.message.code_is_expired'), 422);
                }

                DB::table('password_reset_tokens')->where([
                    ['email', $request->post('email')],
                    ['token', $request->post('token')],
                ])->update(['is_verified' => Ask::YES]);

                return true;
            } else {
                throw new Exception(trans('all.message.code_is_invalid'), 422);
            }
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * Password reset must only proceed after a verified, non-expired email OTP.
     *
     * @throws Exception
     */
    public function assertEmailResetVerified(string $email): void
    {
        if (env('DEMO') == "True" || env('DEMO') == "TRUE" || env('DEMO') == "true" || env('DEMO') == 1) {
            return;
        }

        $row = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (!$row || (int) $row->is_verified !== Ask::YES) {
            throw new Exception(trans('all.message.code_is_invalid'), 422);
        }

        $difference = Carbon::now()->diffInSeconds($row->created_at);
        if ($difference > (int) Settings::group('otp')->get('otp_expire_time') * 60) {
            throw new Exception(trans('all.message.code_is_expired'), 422);
        }
    }

    /**
     * Password reset must only proceed after a verified, non-expired phone OTP.
     *
     * @throws Exception
     */
    public function assertPhoneResetVerified(string $phone, string $countryCode): void
    {
        if (env('DEMO') == "True" || env('DEMO') == "TRUE" || env('DEMO') == "true" || env('DEMO') == 1) {
            return;
        }

        $row = DB::table('otps')->where([
            ['phone', $phone],
            ['code', $countryCode],
        ])->orderByDesc('id')->first();

        if (!$row || (int) $row->is_verified !== Ask::YES) {
            throw new Exception(trans('all.message.code_is_invalid'), 422);
        }

        $difference = Carbon::now()->diffInSeconds($row->created_at);
        if ($difference > (int) Settings::group('otp')->get('otp_expire_time') * 60) {
            throw new Exception(trans('all.message.code_is_expired'), 422);
        }
    }

    public function consumeEmailReset(string $email): void
    {
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }

    public function consumePhoneReset(string $phone, string $countryCode): void
    {
        DB::table('otps')->where([
            ['phone', $phone],
            ['code', $countryCode],
        ])->delete();
    }

    protected function generateOtpToken(): int
    {
        $digits = (int) Settings::group('otp')->get('otp_digit_limit');
        if ($digits < 4) {
            $digits = 4;
        }
        if ($digits > 8) {
            $digits = 8;
        }

        $min = 10 ** ($digits - 1);
        $max = (10 ** $digits) - 1;

        return random_int($min, $max);
    }
}
