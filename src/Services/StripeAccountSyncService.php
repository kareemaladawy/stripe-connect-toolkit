<?php

namespace Kareemaladawy\StripeConnectToolkit\Services;

use Kareemaladawy\StripeConnectToolkit\Contracts\AccountOwnerResolver;
use Kareemaladawy\StripeConnectToolkit\Contracts\ExternalAccountSyncHandler;
use Kareemaladawy\StripeConnectToolkit\DTOs\AccountSyncFields;
use Kareemaladawy\StripeConnectToolkit\DTOs\ExternalAccountData;

/**
 * Syncs `account.updated` and `account.external_account.*` webhook
 * payloads back onto the host app's own owner entity, via the
 * AccountOwnerResolver / ExternalAccountSyncHandler contracts. The host
 * app owns all persistence; this service only normalizes Stripe's
 * payload and routes it.
 */
class StripeAccountSyncService
{
    public function __construct(
        private readonly AccountOwnerResolver $ownerResolver,
        private readonly ?ExternalAccountSyncHandler $externalAccountHandler = null,
    ) {
    }

    public function handleAccountUpdated(object $stripeAccount): void
    {
        $owner = $this->ownerResolver->findByStripeAccountId($stripeAccount->id);

        if (!$owner) {
            return;
        }

        $fields = AccountSyncFields::fromStripeAccount($stripeAccount);

        $this->ownerResolver->updateStripeFields($owner, $fields->toArray());
    }

    public function handleExternalAccountCreated(object $externalAccount): void
    {
        if (!$this->externalAccountHandler) {
            return;
        }

        $owner = $this->ownerResolver->findByStripeAccountId($externalAccount->account);

        if (!$owner) {
            return;
        }

        $this->externalAccountHandler->created($owner, ExternalAccountData::fromStripeExternalAccount($externalAccount));
    }

    public function handleExternalAccountUpdated(object $externalAccount): void
    {
        $this->externalAccountHandler?->updated(ExternalAccountData::fromStripeExternalAccount($externalAccount));
    }

    public function handleExternalAccountDeleted(object $externalAccount): void
    {
        $this->externalAccountHandler?->deleted(ExternalAccountData::fromStripeExternalAccount($externalAccount));
    }
}
