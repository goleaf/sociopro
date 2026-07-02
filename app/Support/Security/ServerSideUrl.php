<?php

namespace App\Support\Security;

final class ServerSideUrl
{
    /**
     * Return a URL safe enough for low-risk server-side previews.
     */
    public static function forHttpFetch(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $host = self::normalizeHost((string) ($parts['host'] ?? ''));
        if ($host === '' || ! self::resolvesOnlyToPublicIps($host)) {
            return null;
        }

        return $url;
    }

    public static function resolvesOnlyToPublicIps(string $host): bool
    {
        $host = self::normalizeHost($host);

        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return self::isPublicIp($host);
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        if (! is_array($records) || $records === []) {
            return false;
        }

        $resolvedIps = [];

        foreach ($records as $record) {
            if (isset($record['ip']) && is_string($record['ip'])) {
                $resolvedIps[] = $record['ip'];
            }

            if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                $resolvedIps[] = $record['ipv6'];
            }
        }

        if ($resolvedIps === []) {
            return false;
        }

        foreach ($resolvedIps as $ip) {
            if (! self::isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    private static function normalizeHost(string $host): string
    {
        return rtrim(strtolower(trim($host, "[] \t\n\r\0\x0B")), '.');
    }

    private static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
