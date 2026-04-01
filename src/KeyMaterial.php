<?php

namespace Xcopy\WhatsappMedia;

/**
 * Derived key material used by WhatsApp media crypto.
 */
readonly class KeyMaterial
{
    /**
     * Create key material from HKDF-expanded bytes.
     *
     * @param string $iv 16-byte initialization vector.
     * @param string $cipherKey 32-byte AES-256-CBC key.
     * @param string $macKey 32-byte HMAC-SHA256 key.
     * @param string $refKey 32-byte reference key.
     */
    public function __construct(
        public string $iv,
        public string $cipherKey,
        public string $macKey,
        public string $refKey,
    ) {}
}
