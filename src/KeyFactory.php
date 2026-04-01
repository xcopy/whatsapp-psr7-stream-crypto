<?php

namespace Xcopy\WhatsappMedia;

use InvalidArgumentException;
use RuntimeException;

class KeyFactory
{
    public static function fromMediaKey(string $mediaKey, MediaType $mediaType): KeyMaterial
    {
        if (strlen($mediaKey) !== 32) {
            throw new InvalidArgumentException('mediaKey must be exactly 32 bytes');
        }

        $expanded = hash_hkdf(
            algo: 'sha256',
            key: $mediaKey,
            length: 112,
            info: $mediaType->hkdfInfo(),
        );

        if ($expanded === '' || strlen($expanded) !== 112) {
            throw new RuntimeException('Unable to derive key material via HKDF');
        }

        return new KeyMaterial(
            iv: substr($expanded, 0, 16),
            cipherKey: substr($expanded, 16, 32),
            macKey: substr($expanded, 48, 32),
            refKey: substr($expanded, 80, 32),
        );
    }
}
