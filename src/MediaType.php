<?php

namespace Xcopy\WhatsappMedia;

/**
 * Supported WhatsApp media categories.
 */
enum MediaType: string
{
    case IMAGE = 'IMAGE';
    case VIDEO = 'VIDEO';
    case AUDIO = 'AUDIO';
    case DOCUMENT = 'DOCUMENT';

    /**
     * HKDF application info string for this media type.
     *
     * Returns the WhatsApp-specific context string used during key derivation
     * to bind the expanded keys to a particular media category.
     *
     * @return string HKDF info parameter (e.g., "WhatsApp Image Keys").
     */
    public function hkdfInfo(): string
    {
        return match ($this) {
            self::IMAGE => 'WhatsApp Image Keys',
            self::VIDEO => 'WhatsApp Video Keys',
            self::AUDIO => 'WhatsApp Audio Keys',
            self::DOCUMENT => 'WhatsApp Document Keys',
        };
    }

    /**
     * Whether sidecar generation is supported for this media type.
     *
     * Only VIDEO and AUDIO media types support sidecar generation, which enables
     * partial decryption and streaming playback without downloading the full file.
     *
     * @return bool True for VIDEO and AUDIO, false otherwise.
     */
    public function isStreamable(): bool
    {
        return $this === self::VIDEO || $this === self::AUDIO;
    }
}
