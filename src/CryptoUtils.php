<?php

namespace Xcopy\WhatsappMedia;

use Psr\Http\Message\StreamInterface;

class CryptoUtils
{
    public const int TRUNCATED_MAC_LENGTH = 10;

    private const int READ_CHUNK_SIZE = 8192;

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
     * @return array{0: string, 1: string}
     */
    public static function splitEncryptedPayload(string $payload): array
    {
        return [
            substr($payload, 0, -self::TRUNCATED_MAC_LENGTH),
            substr($payload, -self::TRUNCATED_MAC_LENGTH),
        ];
    }

    public static function readAll(StreamInterface $stream): string
    {
        $data = '';

        while (!$stream->eof()) {
            $data .= $stream->read(self::READ_CHUNK_SIZE);
        }

        return $data;
    }
}
