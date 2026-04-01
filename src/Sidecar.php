<?php

namespace Xcopy\WhatsappMedia;

class Sidecar
{
    private const int BLOCK = 64 * 1024;
    private const int OVERLAP = 16;

    public static function fromEncryptedMedia(string $encryptedMedia, string $macKey, string $iv): string
    {
        $stream = $iv . $encryptedMedia;
        $length = strlen($stream);
        $offset = 0;
        $sidecar = '';

        while ($offset < $length) {
            $chunk = substr($stream, $offset, self::BLOCK + self::OVERLAP);
            if ($chunk === '') {
                break;
            }

            $sidecar .= CryptoUtils::truncatedHmacSha256($chunk, $macKey);
            $offset += self::BLOCK;
        }

        return $sidecar;
    }
}
