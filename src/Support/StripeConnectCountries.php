<?php

namespace Kareemaladawy\StripeConnectToolkit\Support;

class StripeConnectCountries
{
    /**
     * ISO 3166-1 alpha-2 codes of countries where Stripe Connect (Express)
     * accounts can currently be onboarded.
     *
     * Source: https://stripe.com/global
     * Review and update this list if Stripe changes its supported-country
     * coverage.
     */
    public const CODES = [
        'AE', 'AT', 'AU', 'BE', 'BG', 'BR', 'CA', 'CH', 'CY', 'CZ',
        'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GI', 'GR', 'HK',
        'HR', 'HU', 'ID', 'IE', 'IT', 'JP', 'LI', 'LT', 'LU', 'LV',
        'MT', 'MX', 'MY', 'NL', 'NO', 'NZ', 'PH', 'PL', 'PT', 'RO',
        'SE', 'SG', 'SI', 'SK', 'TH', 'US',
    ];

    public static function isSupported(string $countryCode): bool
    {
        return in_array(strtoupper($countryCode), self::CODES, true);
    }
}
