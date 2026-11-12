<?php

namespace Kareemaladawy\StripeConnectToolkit\DTOs;

/**
 * Normalized shape of the subset of a Stripe Account object the toolkit
 * considers worth persisting on the owner after an `account.updated`
 * webhook. Mirrors the original app's `extractAccountFields()` mapping.
 */
final class AccountSyncFields
{
    public function __construct(
        public readonly bool $detailsSubmitted,
        public readonly bool $payoutsEnabled,
        public readonly ?string $disabledReason,
        public readonly string $requirementsJson,
        public readonly ?string $defaultCurrency,
    ) {
    }

    public static function fromStripeAccount(object $stripeAccount): self
    {
        return new self(
            detailsSubmitted: (bool) $stripeAccount->details_submitted,
            payoutsEnabled: (bool) $stripeAccount->payouts_enabled,
            disabledReason: $stripeAccount->requirements->disabled_reason ?? null,
            requirementsJson: json_encode($stripeAccount->requirements),
            defaultCurrency: $stripeAccount->default_currency ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'stripe_details_submitted' => $this->detailsSubmitted,
            'stripe_payouts_enabled' => $this->payoutsEnabled,
            'stripe_disabled_reason' => $this->disabledReason,
            'stripe_requirements' => $this->requirementsJson,
            'default_currency' => $this->defaultCurrency,
        ];
    }
}
