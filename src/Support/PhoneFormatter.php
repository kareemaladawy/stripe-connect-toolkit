<?php

namespace Kareemaladawy\StripeConnectToolkit\Support;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneFormatter
{
    /**
     * Best-effort conversion of a phone number to E.164. Falls back to the
     * original string whenever parsing/formatting isn't possible — Stripe
     * rejects a malformed phone outright, so passing through lets Stripe's
     * own validation surface the real error rather than this method
     * silently dropping the field.
     */
    public static function toE164(?string $phone, ?string $countryIso2): ?string
    {
        if (!$phone) {
            return null;
        }

        if (preg_match('/^\+[1-9]\d{6,14}$/', $phone)) {
            return $phone;
        }

        if (!$countryIso2) {
            return $phone;
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $numberProto = $phoneUtil->parse($phone, strtoupper($countryIso2));

            if ($phoneUtil->isValidNumber($numberProto)) {
                return $phoneUtil->format($numberProto, PhoneNumberFormat::E164);
            }
        } catch (\Exception) {
            // Fall through and return original
        }

        return $phone;
    }
}
