<?php

namespace App\Support\Security;

final class ServerSideUrl
{
    private const DEFAULT_ALLOWED_HOSTS = ['*'];

    private const DEFAULT_ALLOWED_SCHEMES = ['http', 'https'];

    private const DEFAULT_TIMEOUT_SECONDS = 5;

    private const DEFAULT_MAX_REDIRECTS = 0;

    private const DEFAULT_MAX_RESPONSE_BYTES = 1048576;

    private const DEFAULT_USER_AGENT = 'SocioproLinkPreview/1.0';

    /**
     * Return a URL safe enough for low-risk server-side previews.
     *
     * @param  list<string>|null  $allowedHosts
     * @param  list<string>|null  $allowedSchemes
     */
    public static function forHttpFetch(
        string $url,
        ?array $allowedHosts = null,
        ?array $allowedSchemes = null
    ): ?string {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (! in_array($scheme, self::normalizeAllowedSchemes($allowedSchemes ?? self::DEFAULT_ALLOWED_SCHEMES), true)) {
            return null;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $host = self::normalizeHost((string) ($parts['host'] ?? ''));
        if (
            $host === ''
            || ! self::hostMatchesAllowlist($host, $allowedHosts ?? self::DEFAULT_ALLOWED_HOSTS)
            || ! self::resolvesOnlyToPublicIps($host)
        ) {
            return null;
        }

        return $url;
    }

    public static function forConfiguredHttpFetch(string $url): ?string
    {
        return self::forHttpFetch(
            $url,
            self::configuredStringList('allowed_hosts', self::DEFAULT_ALLOWED_HOSTS),
            self::configuredStringList('allowed_schemes', self::DEFAULT_ALLOWED_SCHEMES)
        );
    }

    /**
     * @return array<string, array<string, int|string|bool>>
     */
    public static function configuredStreamContextOptions(): array
    {
        return self::streamContextOptions(
            self::configuredInt('timeout_seconds', self::DEFAULT_TIMEOUT_SECONDS),
            self::configuredInt('max_redirects', self::DEFAULT_MAX_REDIRECTS, min: 0),
            self::configuredString('user_agent', self::DEFAULT_USER_AGENT)
        );
    }

    public static function configuredResponseByteLimit(): int
    {
        return self::configuredInt('max_response_bytes', self::DEFAULT_MAX_RESPONSE_BYTES, min: 1);
    }

    /**
     * @return array<string, array<string, int|string|bool>>
     */
    public static function streamContextOptions(
        int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
        int $maxRedirects = self::DEFAULT_MAX_REDIRECTS,
        string $userAgent = self::DEFAULT_USER_AGENT
    ): array {
        $timeoutSeconds = max(1, $timeoutSeconds);
        $maxRedirects = max(0, $maxRedirects);

        $options = [
            'follow_location' => 0,
            'ignore_errors' => true,
            'max_redirects' => $maxRedirects,
            'timeout' => $timeoutSeconds,
            'user_agent' => $userAgent,
            'header' => "Accept: text/html,application/xhtml+xml\r\n",
        ];

        return [
            'http' => $options,
            'https' => $options,
        ];
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
        $host = rtrim(strtolower(trim($host, "[] \t\n\r\0\x0B")), '.');

        if ($host === '' || preg_match('/[\s\/\\\\\0]/', $host) === 1) {
            return '';
        }

        return $host;
    }

    private static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * @param  list<string>  $allowedHosts
     */
    private static function hostMatchesAllowlist(string $host, array $allowedHosts): bool
    {
        foreach ($allowedHosts as $allowedHost) {
            $allowedHost = self::normalizeAllowedHost($allowedHost);

            if ($allowedHost === '') {
                continue;
            }

            if ($allowedHost === '*') {
                return true;
            }

            if (str_starts_with($allowedHost, '*.')) {
                $suffix = substr($allowedHost, 2);

                if ($host !== $suffix && str_ends_with($host, '.'.$suffix)) {
                    return true;
                }

                continue;
            }

            if ($host === $allowedHost) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeAllowedHost(string $host): string
    {
        $host = trim($host);

        if ($host === '*') {
            return '*';
        }

        if (str_contains($host, '://')) {
            $parts = parse_url($host);
            $host = is_array($parts) ? (string) ($parts['host'] ?? '') : '';
        }

        if (str_starts_with($host, '*.')) {
            $suffix = self::normalizeHost(substr($host, 2));

            return $suffix === '' ? '' : '*.'.$suffix;
        }

        return self::normalizeHost($host);
    }

    /**
     * @param  list<string>  $schemes
     * @return list<string>
     */
    private static function normalizeAllowedSchemes(array $schemes): array
    {
        return array_values(array_filter(
            array_map(
                fn (string $scheme): string => strtolower(trim($scheme, " \t\n\r\0\x0B:/")),
                $schemes
            ),
            fn (string $scheme): bool => $scheme !== ''
        ));
    }

    /**
     * @param  list<string>  $default
     * @return list<string>
     */
    private static function configuredStringList(string $key, array $default): array
    {
        $value = config("security.server_side_url.{$key}", $default);

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return $default;
        }

        $values = array_values(array_filter(
            array_map(fn (mixed $item): string => trim((string) $item), $value),
            fn (string $item): bool => $item !== ''
        ));

        return $values === [] ? $default : $values;
    }

    private static function configuredString(string $key, string $default): string
    {
        $value = config("security.server_side_url.{$key}", $default);

        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }

    private static function configuredInt(string $key, int $default, int $min = 1): int
    {
        $value = config("security.server_side_url.{$key}", $default);

        if (! is_numeric($value)) {
            return $default;
        }

        return max($min, (int) $value);
    }
}
