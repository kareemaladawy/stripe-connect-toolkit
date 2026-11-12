<?php

namespace Kareemaladawy\StripeConnectToolkit\Services;

use Kareemaladawy\StripeConnectToolkit\Exceptions\StripeToolkitException;
use Stripe\Customer;
use Stripe\CustomerSession;
use Stripe\PaymentIntent;

/**
 * PaymentIntent / Customer / CustomerSession helpers for platform-direct
 * charges (as opposed to Connect transfers — see StripeTransferService).
 */
class StripePaymentService
{
    public function createPaymentIntent(?string $customerId, int $amountInCents, array $metadata = []): array
    {
        if (!$customerId) {
            throw new StripeToolkitException(__('stripe-connect-toolkit::messages.stripe_customer_not_found'));
        }

        $intent = PaymentIntent::create($this->paymentIntentParams($amountInCents, $customerId, $metadata));
        $customerSession = $this->createCustomerSession($customerId);

        return $this->formatIntentResponse($intent, $customerSession);
    }

    public function getExistingPaymentIntent(string $customerId, string $transactionId): array
    {
        $intent = PaymentIntent::retrieve($transactionId);
        $customerSession = $this->createCustomerSession($customerId);

        return $this->formatIntentResponse($intent, $customerSession);
    }

    public function createCustomer(string $name, string $email, array $metadata = []): Customer
    {
        return Customer::create([
            'name' => $name,
            'email' => $email,
            'metadata' => $metadata,
        ]);
    }

    private function createCustomerSession(string $customerId): CustomerSession
    {
        return CustomerSession::create([
            'customer' => $customerId,
            'components' => [
                'payment_element' => [
                    'enabled' => true,
                    'features' => [
                        'payment_method_redisplay' => 'enabled',
                        'payment_method_save' => 'enabled',
                        'payment_method_save_usage' => 'off_session',
                        'payment_method_remove' => 'enabled',
                    ],
                ],
            ],
        ]);
    }

    private function paymentIntentParams(int $amountInCents, string $customerId, array $metadata): array
    {
        return [
            'customer' => $customerId,
            'amount' => $amountInCents,
            'currency' => config('stripe-connect-toolkit.default_currency', 'usd'),
            'setup_future_usage' => 'off_session',
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
            'metadata' => $metadata,
        ];
    }

    private function formatIntentResponse(PaymentIntent $intent, CustomerSession $customerSession): array
    {
        return [
            'amount_in_cents' => $intent->amount,
            'amount' => $intent->amount / 100,
            'transaction_id' => $intent->id,
            'publishable_key' => config('stripe-connect-toolkit.key'),
            'client_secret' => $intent->client_secret,
            'customer_session_client_secret' => $customerSession->client_secret,
        ];
    }
}
