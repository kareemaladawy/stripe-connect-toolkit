<?php

namespace Kareemaladawy\StripeConnectToolkit\DTOs;

/**
 * Normalized shape of a Stripe external account (bank account) payload,
 * independent of the host app's persistence columns.
 */
final class ExternalAccountData
{
    public function __construct(
        public readonly string $stripeExternalAccountId,
        public readonly string $stripeAccountId,
        public readonly ?string $bankName,
        public readonly ?string $currency,
        public readonly bool $defaultForCurrency,
        public readonly ?string $accountHolderName,
        public readonly ?string $accountHolderType,
        public readonly ?string $last4,
        public readonly ?string $status,
        public readonly ?string $countryCode,
    ) {
    }

    public static function fromStripeExternalAccount(object $externalAccount): self
    {
        return new self(
            stripeExternalAccountId: $externalAccount->id,
            stripeAccountId: $externalAccount->account,
            bankName: $externalAccount->bank_name ?? null,
            currency: $externalAccount->currency ?? null,
            defaultForCurrency: (bool) ($externalAccount->default_for_currency ?? false),
            accountHolderName: $externalAccount->account_holder_name ?? null,
            accountHolderType: $externalAccount->account_holder_type ?? null,
            last4: $externalAccount->last4 ?? null,
            status: $externalAccount->status ?? null,
            countryCode: $externalAccount->country ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'stripe_external_account_id' => $this->stripeExternalAccountId,
            'bank_name' => $this->bankName,
            'currency' => $this->currency,
            'default_for_currency' => $this->defaultForCurrency,
            'account_holder_name' => $this->accountHolderName,
            'account_holder_type' => $this->accountHolderType,
            'last4' => $this->last4,
            'status' => $this->status,
            'country_code' => $this->countryCode,
        ];
    }
}
