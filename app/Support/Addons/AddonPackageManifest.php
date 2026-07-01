<?php

namespace App\Support\Addons;

final class AddonPackageManifest
{
    /**
     * @param  list<array{unique_identifier: string, title: string, features: string}>  $addons
     */
    public function __construct(
        public readonly bool $isAddon,
        public readonly string $minimumProductVersion,
        public readonly ?string $minimumAddonVersion,
        public readonly string $updateVersion,
        public readonly array $addons
    ) {}
}
