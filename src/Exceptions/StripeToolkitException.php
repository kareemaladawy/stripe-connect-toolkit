<?php

namespace Kareemaladawy\StripeConnectToolkit\Exceptions;

use Exception;

/**
 * An expected, business-rule failure (e.g. "country not supported by
 * Stripe Connect") whose message is safe to show to the end user as-is.
 * Deliberately does not extend RuntimeException so host apps that catch
 * RuntimeException generically won't accidentally swallow it.
 */
class StripeToolkitException extends Exception
{
}
