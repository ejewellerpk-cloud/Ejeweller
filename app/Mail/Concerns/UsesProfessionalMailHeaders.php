<?php

namespace App\Mail\Concerns;

use Dipokhalder\Settings\Facades\Settings;

trait UsesProfessionalMailHeaders
{
    protected function applyProfessionalHeaders(): static
    {
        $companyName = Settings::group('company')->get('company_name') ?: config('app.name');
        $companyEmail = Settings::group('company')->get('company_email');

        if (!blank($companyEmail)) {
            $this->replyTo($companyEmail, $companyName);
        }

        return $this;
    }
}
