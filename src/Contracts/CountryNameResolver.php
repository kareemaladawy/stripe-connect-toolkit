<?php

namespace Kareemaladawy\StripeConnectToolkit\Contracts;

/**
 * Resolves a human-readable country name from an ISO 3166-1 alpha-2 code,
 * for building a friendly "country not supported" error message. The
 * toolkit has no opinion on where country data lives in the host app.
 */
interface CountryNameResolver
{
    public function resolveName(string $iso2Code): ?string;
}
