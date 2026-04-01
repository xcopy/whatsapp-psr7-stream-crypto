<?php

namespace Xcopy\WhatsappMedia;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 stream decorator that encrypts plaintext into WhatsApp media payload.
 */
class EncryptingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    /**
     * Encrypted payload stream.
     */
    /** @phpstan-ignore-next-line property.onlyWritten */
    private StreamInterface $stream;

    /**
     * Raw 32-byte media key used for encryption.
     *
     * @var string
     */
    private readonly string $mediaKey;

    /**
     * WhatsApp media category.
     *
     * @var MediaType
     */
    private readonly MediaType $mediaType;

    /**
     * Generated sidecar bytes for streamable media, or null.
     *
     * @var string|null
     */
    private readonly ?string $sidecar;

    /**
     * Create an encrypting stream that produces WhatsApp media payload.
     *
     * @param StreamInterface $source Plaintext source stream.
     * @param MediaType $mediaType WhatsApp media category.
     * @param string|null $mediaKey Raw 32-byte media key, or null to generate one.
     * @param bool $generateSidecar Whether to generate sidecar for streamable media.
     *
     * @throws CryptoException If encryption operation fails.
     */
    public function __construct(StreamInterface $source, MediaType $mediaType, ?string $mediaKey = null, bool $generateSidecar = false)
    {
        $this->mediaKey = $mediaKey ?? random_bytes(32);
        $this->mediaType = $mediaType;

        $keys = KeyFactory::fromMediaKey($this->mediaKey, $this->mediaType);
        $plaintext = CryptoUtils::readAll($source);

        $ciphertext = openssl_encrypt(
            data: $plaintext,
            cipher_algo: 'aes-256-cbc',
            passphrase: $keys->cipherKey,
            options: OPENSSL_RAW_DATA,
            iv: $keys->iv
        );

        if ($ciphertext === false) {
            throw new CryptoException('Encryption failed');
        }

        $mac = CryptoUtils::truncatedHmacSha256($keys->iv . $ciphertext, $keys->macKey);
        $encryptedMedia = $ciphertext . $mac;

        $this->sidecar = $generateSidecar && $this->mediaType->isStreamable()
            ? Sidecar::fromEncryptedMedia($encryptedMedia, $keys->macKey, $keys->iv)
            : null;

        $this->stream = Utils::streamFor($encryptedMedia);
    }

    /**
     * Raw 32-byte media key used for current payload.
     */
    public function getMediaKey(): string
    {
        return $this->mediaKey;
    }

    /**
     * Media type bound to current payload.
     */
    public function getMediaType(): MediaType
    {
        return $this->mediaType;
    }

    /**
     * Generated sidecar bytes, or null if sidecar generation was disabled.
     */
    public function getSidecar(): ?string
    {
        return $this->sidecar;
    }
}
