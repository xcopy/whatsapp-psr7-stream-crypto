<?php

namespace Xcopy\WhatsappMedia;

/**
 * Generates stream sidecar metadata used for partial media playback.
 */
class Sidecar
{
    /**
     * Sidecar chunk size in bytes (64 KiB).
     */
    private const int BLOCK = 64 * 1024;

    /**
     * CBC block overlap for chunk windows (16 bytes).
     */
    private const int OVERLAP = 16;

    /**
     * Build sidecar bytes from encrypted media payload.
     *
     * Generates authentication fragments for 64K-aligned chunks over the concatenated
     * stream (iv + encrypted media), allowing partial validation during streaming playback.
     *
     * @param string $encryptedMedia Encrypted payload (ciphertext + MAC).
     * @param string $macKey 32-byte HMAC key.
     * @param string $iv 16-byte initialization vector.
     *
     * @return string Concatenated 10-byte HMAC fragments for each chunk.
     */
    public static function fromEncryptedMedia(string $encryptedMedia, string $macKey, string $iv): string
    {
        $stream = $iv . $encryptedMedia;
        $length = strlen($stream);
        $offset = 0;
        $sidecar = '';

        // Each fragment signs 64K bytes plus one AES block of look-ahead.
        while ($offset < $length) {
            $chunk = substr($stream, $offset, self::BLOCK + self::OVERLAP);
            $sidecar .= CryptoUtils::truncatedHmacSha256($chunk, $macKey);
            // Next chunk starts exactly 64K later.
            $offset += self::BLOCK;
        }

        return $sidecar;
    }
}
