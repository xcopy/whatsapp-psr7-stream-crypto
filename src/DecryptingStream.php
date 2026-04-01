<?php

namespace Wanted\WhatsappMedia;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

class DecryptingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private StreamInterface $stream;

    public function __construct(StreamInterface $source, MediaType $mediaType, string $mediaKey)
    {
        $keys = KeyFactory::fromMediaKey($mediaKey, $mediaType);
        $payload = self::readAll($source);

        if (strlen($payload) < 11) {
            throw new InvalidMacException('Encrypted payload is too short');
        }

        $ciphertext = substr($payload, 0, -10);
        $mac = substr($payload, -10);

        $expected = substr(hash_hmac('sha256', $keys->iv . $ciphertext, $keys->macKey, true), 0, 10);
        if (!hash_equals($expected, $mac)) {
            throw new InvalidMacException('MAC validation failed');
        }

        $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $keys->cipherKey, OPENSSL_RAW_DATA, $keys->iv);
        if ($plaintext === false) {
            throw new CryptoException('Decryption failed');
        }

        $this->stream = Utils::streamFor($plaintext);
    }

    private static function readAll(StreamInterface $stream): string
    {
        $data = '';
        while (!$stream->eof()) {
            $data .= $stream->read(8192);
        }

        return $data;
    }
}
