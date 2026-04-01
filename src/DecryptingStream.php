<?php

namespace Xcopy\WhatsappMedia;

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
        $payload = CryptoUtils::readAll($source);

        if (strlen($payload) <= CryptoUtils::TRUNCATED_MAC_LENGTH) {
            throw new InvalidMacException('Encrypted payload is too short');
        }

        [$ciphertext, $mac] = CryptoUtils::splitEncryptedPayload($payload);

        $expected = CryptoUtils::truncatedHmacSha256($keys->iv . $ciphertext, $keys->macKey);
        if (!hash_equals($expected, $mac)) {
            throw new InvalidMacException('MAC validation failed');
        }

        $plaintext = openssl_decrypt(
            data: $ciphertext,
            cipher_algo: 'aes-256-cbc',
            passphrase: $keys->cipherKey,
            options: OPENSSL_RAW_DATA,
            iv: $keys->iv
        );

        if ($plaintext === false) {
            throw new CryptoException('Decryption failed');
        }

        $this->stream = Utils::streamFor($plaintext);
    }
}
