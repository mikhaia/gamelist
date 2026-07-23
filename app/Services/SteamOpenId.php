<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class SteamOpenId
{
    private const OPENID_NAMESPACE = 'http://specs.openid.net/auth/2.0';

    private const IDENTIFIER_SELECT = 'http://specs.openid.net/auth/2.0/identifier_select';

    /** @var array<int, string> */
    private const REQUIRED_SIGNED_FIELDS = [
        'op_endpoint',
        'claimed_id',
        'identity',
        'return_to',
        'response_nonce',
    ];

    public function authenticationUrl(string $returnTo, string $realm): string
    {
        return $this->endpoint().'?'.http_build_query([
            'openid.ns' => self::OPENID_NAMESPACE,
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $returnTo,
            'openid.realm' => rtrim($realm, '/').'/',
            'openid.identity' => self::IDENTIFIER_SELECT,
            'openid.claimed_id' => self::IDENTIFIER_SELECT,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function verify(Request $request, string $expectedReturnTo): ?string
    {
        $parameters = $this->openidParameters($request);

        if (($parameters['openid.ns'] ?? null) !== self::OPENID_NAMESPACE
            || ($parameters['openid.mode'] ?? null) !== 'id_res'
            || ! $this->matches($expectedReturnTo, $parameters['openid.return_to'] ?? null)
            || ! $this->matches($this->endpoint(), $parameters['openid.op_endpoint'] ?? null, normalizeUrl: true)
            || ! $this->signedFieldsArePresent($parameters)
            || ! $this->nonceIsFresh($parameters['openid.response_nonce'] ?? null)) {
            return null;
        }

        $claimedId = $parameters['openid.claimed_id'] ?? null;
        if (! is_string($claimedId)
            || ! $this->matches($claimedId, $parameters['openid.identity'] ?? null)
            || ! preg_match('#^https?://steamcommunity\.com/openid/id/([0-9]{15,20})$#', $claimedId, $matches)) {
            return null;
        }

        try {
            $verification = $parameters;
            $verification['openid.mode'] = 'check_authentication';
            $response = Http::asForm()
                ->accept('text/plain')
                ->timeout(10)
                ->post($this->endpoint(), $verification);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful() || ! preg_match('/^is_valid\s*:\s*true\s*$/mi', $response->body())) {
            return null;
        }

        $nonce = $parameters['openid.response_nonce'];
        if (! Cache::add('steam-openid-nonce:'.hash('sha256', $nonce), true, now()->addMinutes(15))) {
            return null;
        }

        return $matches[1];
    }

    private function endpoint(): string
    {
        return rtrim((string) config('services.steam.openid_url'), '/');
    }

    /** @return array<string, string> */
    private function openidParameters(Request $request): array
    {
        $parameters = [];

        foreach ($request->query() as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            if (str_starts_with($key, 'openid.')) {
                $parameters[$key] = $value;
            } elseif (str_starts_with($key, 'openid_')) {
                $parameters['openid.'.substr($key, 7)] = $value;
            }
        }

        return $parameters;
    }

    /** @param array<string, string> $parameters */
    private function signedFieldsArePresent(array $parameters): bool
    {
        $signed = array_filter(explode(',', $parameters['openid.signed'] ?? ''));

        foreach (self::REQUIRED_SIGNED_FIELDS as $field) {
            if (! in_array($field, $signed, true)) {
                return false;
            }
        }

        return true;
    }

    private function nonceIsFresh(?string $nonce): bool
    {
        if (! is_string($nonce)
            || ! preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z)/', $nonce, $matches)) {
            return false;
        }

        try {
            $issuedAt = CarbonImmutable::parse($matches[1]);
        } catch (Throwable) {
            return false;
        }

        return $issuedAt->between(now()->subMinutes(15), now()->addMinutes(5));
    }

    private function matches(string $expected, mixed $actual, bool $normalizeUrl = false): bool
    {
        if (! is_string($actual)) {
            return false;
        }

        if ($normalizeUrl) {
            $expected = rtrim($expected, '/');
            $actual = rtrim($actual, '/');
        }

        return hash_equals($expected, $actual);
    }
}
