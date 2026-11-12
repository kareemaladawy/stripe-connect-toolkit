<?php

namespace Kareemaladawy\StripeConnectToolkit\Contracts;

/**
 * Implemented by whatever model in the host app can own a Stripe Connect
 * (Express) account — e.g. an Agent, a Vendor, a Seller. The toolkit never
 * depends on the host app's model classes directly; it only asks for this
 * shape.
 */
interface ConnectAccountHoldable
{
    public function getStripeAccountHolderName(): string;

    public function getStripeAccountHolderEmail(): string;

    public function getStripeAccountHolderPhone(): ?string;

    /**
     * @return string|null Any parseable date string (e.g. 'Y-m-d'), or null.
     */
    public function getStripeAccountHolderDob(): ?string;

    /**
     * ISO 3166-1 alpha-2 country code, or null if the host app has none on
     * file for this holder (the caller must then pass one explicitly).
     */
    public function getStripeAccountHolderCountryCode(): ?string;

    /**
     * The existing Stripe Connect account id already stored on this holder,
     * if any (used by sync/update flows). Null if none yet.
     */
    public function getStripeAccountId(): ?string;
}
