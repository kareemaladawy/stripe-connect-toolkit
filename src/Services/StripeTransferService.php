<?php

namespace Kareemaladawy\StripeConnectToolkit\Services;

use Stripe\Balance;
use Stripe\BalanceTransaction;
use Stripe\Charge;
use Stripe\Transfer;

/**
 * Transfers to connected accounts, platform balance, and the balance
 * transaction lookups needed to reconcile Stripe payout webhooks back to
 * the Transfer(s) that funded them.
 */
class StripeTransferService
{
    public function createTransfer(array $params, array $options = []): Transfer
    {
        return Transfer::create($params, $options);
    }

    public function getBalanceTransaction(string $balanceTransactionId): BalanceTransaction
    {
        return BalanceTransaction::retrieve($balanceTransactionId);
    }

    public function getChargeWithBalanceTransaction(string $chargeId): Charge
    {
        return Charge::retrieve([
            'id' => $chargeId,
            'expand' => ['balance_transaction'],
        ]);
    }

    /**
     * List the balance transactions bundled into a payout on a connected
     * account, so a `payout.paid`/`payout.failed` event can be matched
     * back to the Stripe Transfer(s) it paid out.
     *
     * @return \Stripe\BalanceTransaction[]
     */
    public function listBalanceTransactionsForPayout(string $payoutId, string $stripeAccountId): array
    {
        $transactions = [];

        $params = ['payout' => $payoutId, 'limit' => 100];
        $options = ['stripe_account' => $stripeAccountId];

        do {
            $page = BalanceTransaction::all($params, $options);
            $transactions = array_merge($transactions, $page->data);

            $params['starting_after'] = end($page->data)?->id;
        } while ($page->has_more);

        return $transactions;
    }

    public static function getAvailableBalance(string $currency = 'usd'): int
    {
        $balance = Balance::retrieve();

        return collect($balance->available)
            ->firstWhere('currency', $currency)->amount ?? 0;
    }
}
