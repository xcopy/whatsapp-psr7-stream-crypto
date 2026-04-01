<?php

namespace Xcopy\WhatsappMedia;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 stream decorator that validates MAC and decrypts WhatsApp payloads.
 */
class DecryptingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    /**
     * Decrypted plaintext stream.
     */
    /** @phpstan-ignore-next-line property.onlyWritten */
    private StreamInterface $stream;

    /**
     * Create a decrypting stream that validates MAC and decrypts the payload.
     *
     * @param StreamInterface $source Encrypted payload stream (ciphertext + MAC).
     * @param MediaType $mediaType WhatsApp media category.
     * @param string $mediaKey Raw 32-byte media key.
     *
     * @throws InvalidMacException If payload is too short or MAC validation fails.
     * @throws CryptoException If decryption operation fails.
     */
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
