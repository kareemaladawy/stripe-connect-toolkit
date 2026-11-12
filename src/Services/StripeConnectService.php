<?php

namespace Kareemaladawy\StripeConnectToolkit\Services;

use Kareemaladawy\StripeConnectToolkit\Contracts\ConnectAccountHoldable;
use Kareemaladawy\StripeConnectToolkit\Contracts\CountryNameResolver;
use Kareemaladawy\StripeConnectToolkit\Exceptions\StripeToolkitException;
use Kareemaladawy\StripeConnectToolkit\Support\NameSplitter;
use Kareemaladawy\StripeConnectToolkit\Support\PhoneFormatter;
use Kareemaladawy\StripeConnectToolkit\Support\StripeConnectCountries;
use Illuminate\Support\Facades\Cache;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\CountrySpec;

/**
 * Stripe Connect (Express) account creation and onboarding. Depends only
 * on the ConnectAccountHoldable contract - never on a host app's model
 * classes.
 */
class StripeConnectService
{
    public function __construct(
        private readonly ?CountryNameResolver $countryNameResolver = null,
    ) {
    }

    public function createConnectedAccount(ConnectAccountHoldable $holder, ?string $country = null): Account
    {
        $payload = $this->buildConnectedAccountPayload($holder, $country);

        return Account::create($payload);
    }

    private function buildConnectedAccountPayload(ConnectAccountHoldable $holder, ?string $country): array
    {
        [$firstName, $lastName] = NameSplitter::split($holder->getStripeAccountHolderName());

        $countryCode = $country ?? $holder->getStripeAccountHolderCountryCode();

        if (empty($countryCode)) {
            throw new StripeToolkitException(__('stripe-connect-toolkit::messages.payment_account_missing_country'));
        }

        if (!StripeConnectCountries::isSupported($countryCode)) {
            $countryName = $this->countryNameResolver?->resolveName($countryCode) ?? $countryCode;

            throw new StripeToolkitException(
                __('stripe-connect-toolkit::messages.payment_account_country_not_supported', ['country' => $countryName])
            );
        }

        $serviceAgreement = $countryCode === 'US' ? 'full' : 'recipient';

        $capabilities = [
            'transfers' => ['requested' => true],
        ];

        if ($serviceAgreement === 'full') {
            $capabilities['card_payments'] = ['requested' => true];
        }

        $individual = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $holder->getStripeAccountHolderEmail(),
            'phone' => PhoneFormatter::toE164($holder->getStripeAccountHolderPhone(), $countryCode),
        ];

        if ($dob = $holder->getStripeAccountHolderDob()) {
            $individual['dob'] = [
                'day' => (int) date('d', strtotime($dob)),
                'month' => (int) date('m', strtotime($dob)),
                'year' => (int) date('Y', strtotime($dob)),
            ];
        }

        return [
            'type' => 'express',
            'business_type' => 'individual',
            'email' => $holder->getStripeAccountHolderEmail(),
            'country' => $countryCode,
            'default_currency' => $this->resolveDefaultCurrency($countryCode),
            'individual' => $individual,
            'settings' => [
                'payouts' => [
                    'schedule' => ['interval' => 'daily'],
                ],
            ],
            'capabilities' => $capabilities,
            'tos_acceptance' => ['service_agreement' => $serviceAgreement],
        ];
    }

    private function resolveDefaultCurrency(string $countryCode): string
    {
        try {
            $ttl = (int) config('stripe-connect-toolkit.country_spec_cache_ttl', 86400);
            $store = config('stripe-connect-toolkit.cache_store');

            $spec = Cache::store($store)->remember(
                "stripe_connect_toolkit_country_spec_{$countryCode}",
                $ttl,
                fn () => CountrySpec::retrieve($countryCode)
            );

            $supportedCurrencies = array_keys((array) $spec->supported_bank_account_currencies);

            $preferredCurrency = config('stripe-connect-toolkit.preferred_connect_currency', 'usd');

            if (in_array($preferredCurrency, $supportedCurrencies, true)) {
                return $preferredCurrency;
            }

            return $spec->default_currency;
        } catch (\Exception) {
            return config('stripe-connect-toolkit.preferred_connect_currency', 'usd');
        }
    }

    public function createAccountLink(string $accountId, string $refreshUrl, string $returnUrl): AccountLink
    {
        return AccountLink::create([
            'account' => $accountId,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
            'collect' => 'eventually_due',
        ]);
    }

    public function createLoginLink(string $accountId)
    {
        return Account::createLoginLink($accountId);
    }

    public function retrieveAccount(string $accountId): Account
    {
        return Account::retrieve($accountId);
    }
}
