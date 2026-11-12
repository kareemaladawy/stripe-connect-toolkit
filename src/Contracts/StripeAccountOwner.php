<?php

namespace Kareemaladawy\StripeConnectToolkit\Contracts;

/**
 * Marker interface for whatever host-app entity persists a Stripe Connect
 * account id and should receive webhook-driven field updates (e.g. Agent,
 * Vendor, Company).
 *
 * Deliberately separate from ConnectAccountHoldable: a "holder" supplies
 * the personal details Stripe needs to create an account (name, email,
 * phone, dob), while an "owner" is whatever row actually persists the
 * account id and payout/compliance state. In many apps these are the same
 * model; in others — e.g. an agency where an employee completes
 * onboarding but the agency itself owns the Connect account — they are
 * not, so the toolkit never assumes they're one type.
 */
interface StripeAccountOwner
{
}
