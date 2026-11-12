<?php

namespace Kareemaladawy\StripeConnectToolkit\Support;

class NameSplitter
{
    /**
     * @return array{0: string, 1: string} [firstName, lastName]
     */
    public static function split(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }
}
