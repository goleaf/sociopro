<?php

namespace App\Support\Addons;

use JsonException;
use RuntimeException;

final class AddonPackageManifestParser
{
    public function parse(string $json): AddonPackageManifest
    {
        try {
            $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Addon manifest is not valid JSON.', previous: $exception);
        }

        if (! is_array($payload)) {
            throw new RuntimeException('Addon manifest must be a JSON object.');
        }

        $isAddon = (string) ($payload['is_addon'] ?? '0') === '1';
        $productVersion = $this->arrayValue($payload, 'product_version');
        $minimumProductVersion = $this->stringValue($productVersion, 'minimum_required_version');

        if ($isAddon) {
            $addonVersion = $this->arrayValue($payload, 'addon_version');

            return new AddonPackageManifest(
                true,
                $minimumProductVersion,
                $this->stringValue($addonVersion, 'minimum_required_version'),
                $this->stringValue($addonVersion, 'update_version'),
                $this->addonEntries($payload)
            );
        }

        return new AddonPackageManifest(
            false,
            $minimumProductVersion,
            null,
            $this->stringValue($productVersion, 'update_version'),
            []
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function arrayValue(array $payload, string $key): array
    {
        $value = $payload[$key] ?? null;

        if (! is_array($value)) {
            throw new RuntimeException("Addon manifest is missing {$key} metadata.");
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function stringValue(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        if (! is_scalar($value) || trim((string) $value) === '') {
            throw new RuntimeException("Addon manifest is missing {$key}.");
        }

        return trim((string) $value);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{unique_identifier: string, title: string, features: string}>
     */
    private function addonEntries(array $payload): array
    {
        $addons = $payload['addons'] ?? null;

        if (! is_array($addons) || $addons === []) {
            throw new RuntimeException('Addon manifest must contain at least one addon entry.');
        }

        $entries = [];

        foreach ($addons as $addon) {
            if (! is_array($addon)) {
                throw new RuntimeException('Addon manifest entry must be an object.');
            }

            $identifier = $addon['unique_identifier'] ?? null;

            if (! is_scalar($identifier) || trim((string) $identifier) === '') {
                throw new RuntimeException('Addon manifest entry is missing a unique identifier.');
            }

            $entries[] = [
                'unique_identifier' => trim((string) $identifier),
                'title' => is_scalar($addon['title'] ?? null) ? trim((string) $addon['title']) : '',
                'features' => is_scalar($addon['features'] ?? null) ? trim((string) $addon['features']) : '',
            ];
        }

        return $entries;
    }
}
