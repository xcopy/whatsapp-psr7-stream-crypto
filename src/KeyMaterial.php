<?php

namespace Xcopy\WhatsappMedia;

readonly class KeyMaterial
{
    public function __construct(
        public string $iv,
        public string $cipherKey,
        public string $macKey,
        public string $refKey,
    ) {}
}
