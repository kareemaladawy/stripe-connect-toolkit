<?php

namespace Kareemaladawy\StripeConnectToolkit\Contracts;

/**
 * Looks up and updates whichever host-app entity owns a given Stripe
 * Connect account id. The host app is typically polymorphic here (an
 * account might belong to an Agent, a Vendor, a Company, ...); this
 * contract lets the toolkit stay agnostic of how many owner types exist
 * or how they're stored — the host app's single implementation fans out
 * to its own repositories internally.
 */
interface AccountOwnerResolver
{
    public function findByStripeAccountId(string $stripeAccountId): ?ConnectAccountHoldable;

    /**
     * @param array<string, mixed> $fields Column => value pairs to persist.
     */
    public function updateStripeFields(ConnectAccountHoldable $owner, array $fields): void;
}
