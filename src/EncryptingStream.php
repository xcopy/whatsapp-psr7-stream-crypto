<?php

namespace Wanted\WhatsappMedia;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

class EncryptingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private StreamInterface $stream;
    private readonly string $mediaKey;
    private readonly MediaType $mediaType;
    private readonly ?string $sidecar;

    public function __construct(StreamInterface $source, MediaType $mediaType, ?string $mediaKey = null, bool $generateSidecar = false)
    {
        $this->mediaKey = $mediaKey ?? random_bytes(32);
        $this->mediaType = $mediaType;

        $keys = KeyFactory::fromMediaKey($this->mediaKey, $this->mediaType);
        $plaintext = self::readAll($source);

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

        $mac = substr(hash_hmac('sha256', $keys->iv . $ciphertext, $keys->macKey, true), 0, 10);
        $encryptedMedia = $ciphertext . $mac;
        $this->sidecar = $generateSidecar && $this->mediaType->isStreamable()
            ? Sidecar::fromEncryptedMedia($encryptedMedia, $keys->macKey, $keys->iv)
            : null;
        $this->stream = Utils::streamFor($encryptedMedia);
    }

    public function getMediaKey(): string
    {
        return $this->mediaKey;
    }

    public function getMediaType(): MediaType
    {
        return $this->mediaType;
    }

    public function getSidecar(): ?string
    {
        return $this->sidecar;
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
