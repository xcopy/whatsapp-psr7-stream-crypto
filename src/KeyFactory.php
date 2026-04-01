<?php

namespace Xcopy\WhatsappMedia;

use InvalidArgumentException;
use RuntimeException;

/**
 * Derives encryption, MAC, and IV material from a 32-byte media key.
 */
class KeyFactory
{
    /**
     * Expand media key into the 112-byte WhatsApp key schedule.
     *
     * Uses HKDF-SHA256 with media-type-specific application info to derive
     * IV, cipher key, MAC key, and reference key from the input media key.
     *
     * @param string $mediaKey Raw 32-byte media key.
     * @param MediaType $mediaType WhatsApp media category for HKDF info string.
     *
     * @return KeyMaterial Derived key material (IV, cipher key, MAC key, ref key).
     * @throws InvalidArgumentException If media key is not exactly 32 bytes.
     * @throws RuntimeException If HKDF expansion fails.
     */
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

        if (strlen($expanded) !== 112) {
            throw new RuntimeException('Unable to derive key material via HKDF');
        }

        // WhatsApp key schedule: 16-byte IV + 32-byte cipher key + 32-byte MAC key + 32-byte ref key.
        return new KeyMaterial(
            iv: substr($expanded, 0, 16),
            cipherKey: substr($expanded, 16, 32),
            macKey: substr($expanded, 48, 32),
            refKey: substr($expanded, 80, 32),
        );
    }
}
