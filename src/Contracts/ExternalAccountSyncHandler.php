<?php

namespace Kareemaladawy\StripeConnectToolkit\Contracts;

use Kareemaladawy\StripeConnectToolkit\DTOs\ExternalAccountData;

/**
 * Persists the bank/external-account details Stripe reports via its
 * `account.external_account.*` webhooks. The toolkit extracts and
 * normalizes the Stripe payload into ExternalAccountData; the host app
 * decides where/how to store it (its own BankDetail-style table, with
 * whatever polymorphic/owner columns it uses).
 */
interface ExternalAccountSyncHandler
{
    public function created(ConnectAccountHoldable $owner, ExternalAccountData $data): void;

    public function updated(ExternalAccountData $data): void;

    public function deleted(ExternalAccountData $data): void;
}
