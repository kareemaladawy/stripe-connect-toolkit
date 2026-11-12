# Stripe Connect Toolkit

Reusable Stripe Connect (Express) onboarding, platform payments, transfers,
and webhook-driven account sync for Laravel applications

By [Kareem Aladawy](https://github.com/kareemaladawy).

## What's in here

| Class | Responsibility |
|---|---|
| `Services\StripeConnectService` | Create Connect (Express) accounts, onboarding/login links, retrieve accounts |
| `Services\StripePaymentService` | Platform-direct PaymentIntents, Customers, CustomerSessions |
| `Services\StripeTransferService` | Transfers to connected accounts, balance, balance-transaction/payout reconciliation |
| `Services\StripeAccountSyncService` | Normalizes `account.updated` / `account.external_account.*` webhook payloads and routes them to your app via contracts |
| `Support\StripeConnectCountries` | Stripe Connect-supported country list |
| `Support\PhoneFormatter`, `Support\NameSplitter` | Small helpers used by account creation |

Nothing in `src/Services` references an app's Eloquent models or
repositories. The only way the toolkit touches your data is through the
contracts below, which you implement once per host app.

## Install

```bash
composer require kareemaladawy/stripe-connect-toolkit
php artisan vendor:publish --tag=stripe-connect-toolkit-config
```

Set in `.env`:

```
STRIPE_SECRET=sk_...
STRIPE_KEY=pk_...
STRIPE_DEFAULT_CURRENCY=usd
STRIPE_PREFERRED_CONNECT_CURRENCY=usd
```

## Wiring it up: the contracts you implement

There are two distinct roles, deliberately kept separate - in some apps
the same model plays both, in others (e.g. an agency where an employee
onboards but the agency itself owns the Connect account) they don't:

- **`ConnectAccountHoldable`** - supplies the personal details Stripe needs
  to *create* an account (name, email, phone, dob, country). Implement
  this on whichever model(s) a human fills out onboarding as.
- **`StripeAccountOwner`** - a marker interface for whichever model
  actually *persists* the `stripe_account_id` and receives webhook-driven
  field updates. Implement this on whichever model(s) store that column.

### 1. `ConnectAccountHoldable`

```php
class Agent extends Model implements \Kareemaladawy\StripeConnectToolkit\Contracts\ConnectAccountHoldable
{
    public function getStripeAccountHolderName(): string { return $this->name; }
    public function getStripeAccountHolderEmail(): string { return $this->email; }
    public function getStripeAccountHolderPhone(): ?string { return $this->phone; }
    public function getStripeAccountHolderDob(): ?string { return $this->dob; }
    public function getStripeAccountHolderCountryCode(): ?string { return $this->country?->iso2; }
    public function getStripeAccountId(): ?string { return $this->stripe_account_id; }
}
```

### 2. `StripeAccountOwner`

```php
class Agent extends Model implements \Kareemaladawy\StripeConnectToolkit\Contracts\StripeAccountOwner {}
class Agency extends Model implements \Kareemaladawy\StripeConnectToolkit\Contracts\StripeAccountOwner {}
```

### 3. `AccountOwnerResolver` - one implementation per app, fanning out to your repositories

```php
class AppAccountOwnerResolver implements \Kareemaladawy\StripeConnectToolkit\Contracts\AccountOwnerResolver
{
    public function __construct(
        private AgentRepository $agents,
        private AgencyRepository $agencies,
    ) {}

    public function findByStripeAccountId(string $stripeAccountId): ?StripeAccountOwner
    {
        return $this->agents->findFirst(['stripe_account_id' => $stripeAccountId])
            ?? $this->agencies->findFirst(['stripe_account_id' => $stripeAccountId]);
    }

    public function updateStripeFields(StripeAccountOwner $owner, array $fields): void
    {
        match (get_class($owner)) {
            Agent::class => $this->agents->update($owner, $fields),
            Agency::class => $this->agencies->update($owner, $fields),
        };
    }
}
```

### 4. `ExternalAccountSyncHandler` (optional) - if you sync bank details from `account.external_account.*`

```php
class AppExternalAccountSyncHandler implements \Kareemaladawy\StripeConnectToolkit\Contracts\ExternalAccountSyncHandler
{
    public function __construct(
        private BankDetailRepository $bankDetails,
        private CountryRepository $countries,
    ) {}

    public function created(StripeAccountOwner $owner, ExternalAccountData $data): void
    {
        $this->bankDetails->create([
            ...$data->toArray(),
            'country_id' => $this->countries->findFirst(['iso2' => $data->countryCode])?->id,
            'bankable_type' => get_class($owner),
            'bankable_id' => $owner->getKey(),
        ]);
    }

    public function updated(ExternalAccountData $data): void { /* find by stripe_external_account_id, update */ }
    public function deleted(ExternalAccountData $data): void { /* find by stripe_external_account_id, delete */ }
}
```

### Optional: `CountryNameResolver`

Only used to build a friendly message when a holder's country isn't
Connect-supported. Skip it and the toolkit just uses the raw ISO code in
the error message.

### Bind them

In `config/stripe-connect-toolkit.php` (published) or any service provider:

```php
'bindings' => [
    \Kareemaladawy\StripeConnectToolkit\Contracts\CountryNameResolver::class => AppCountryNameResolver::class,
    \Kareemaladawy\StripeConnectToolkit\Contracts\AccountOwnerResolver::class => AppAccountOwnerResolver::class,
    \Kareemaladawy\StripeConnectToolkit\Contracts\ExternalAccountSyncHandler::class => AppExternalAccountSyncHandler::class,
],
```

## Usage

```php
// Onboarding
$account = app(StripeConnectService::class)->createConnectedAccount($agent);
$link = app(StripeConnectService::class)->createAccountLink($account->id, $refreshUrl, $returnUrl);

// Platform payments
$intent = app(StripePaymentService::class)->createPaymentIntent($customerId, $amountInCents, ['invoice_id' => $invoice->id]);

// Transfers
app(StripeTransferService::class)->createTransfer($params, ['stripe_account' => $account->id]);

// Webhook sync (inside your webhook controller's existing switch/match on event type)
app(StripeAccountSyncService::class)->handleAccountUpdated($event->data->object);
app(StripeAccountSyncService::class)->handleExternalAccountCreated($event->data->object);
```

## Notes on what intentionally stayed out

- `createCustomer($hotelAdmin, $hotel)`-style app-specific metadata
  building - stays in the host app; call
  `StripePaymentService::createCustomer($name, $email, $metadata)` with
  your own metadata array instead.
- Caching uses the host app's default cache store (or
  `STRIPE_TOOLKIT_CACHE_STORE` to pick another) via the `Cache` facade -
  no new caching infrastructure introduced.
- No currency/money formatting helpers - amounts are always passed as
  integer minor units, matching standard Stripe + this app's own money
  column convention.
