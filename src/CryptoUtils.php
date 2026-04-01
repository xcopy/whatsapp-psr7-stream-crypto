<?php

namespace Xcopy\WhatsappMedia;

use Psr\Http\Message\StreamInterface;

/**
 * Shared low-level helpers for stream crypto operations.
 */
class CryptoUtils
{
    /**
     * Standard MAC length used by WhatsApp media crypto (10 bytes).
     */
    public const int TRUNCATED_MAC_LENGTH = 10;

    /**
     * Stream read chunk size in bytes (8 KiB).
     */
    private const int READ_CHUNK_SIZE = 8192;

    /**
     * Compute binary HMAC-SHA256 and return its truncated prefix.
     *
     * @param string $data Data to authenticate.
     * @param string $key HMAC secret key.
     * @param int $length Number of bytes to return from the hash (default: 10).
     *
     * @return string Truncated HMAC binary string.
     */
    public static function truncatedHmacSha256(string $data, string $key, int $length = self::TRUNCATED_MAC_LENGTH): string
    {
        $hash = hash_hmac(
            algo: 'sha256',
            data: $data,
            key: $key,
            binary: true
        );

        return substr($hash, 0, $length);
    }

    /**
     * Split encrypted payload into ciphertext and 10-byte MAC tail.
     *
     * @param string $payload Encrypted media payload (ciphertext + MAC).
     *
     * @return array{0: string, 1: string} Tuple of [ciphertext, MAC].
     */
    public static function splitEncryptedPayload(string $payload): array
    {
        return [
            substr($payload, 0, -self::TRUNCATED_MAC_LENGTH),
            substr($payload, -self::TRUNCATED_MAC_LENGTH),
        ];
    }

    /**
     * Read the full binary contents of a PSR-7 stream.
     *
     * @param StreamInterface $stream Source stream to read from.
     *
     * @return string Complete stream contents as binary string.
     */
    public static function readAll(StreamInterface $stream): string
    {
        $data = '';

        while (!$stream->eof()) {
            $data .= $stream->read(self::READ_CHUNK_SIZE);
        }

        return $data;
    }
}
