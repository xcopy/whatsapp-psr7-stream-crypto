# xcopy/whatsapp-psr7-stream-crypto

PSR-7 stream decorators for WhatsApp-compatible media encryption and decryption.

## Features

- Encrypt stream data with AES-256-CBC and append 10-byte truncated HMAC-SHA256
- Decrypt and validate MAC before returning plaintext
- HKDF-SHA256 key expansion with the `MediaType` enum providing type-specific info strings
- Sidecar generation for streamable media (`MediaType::VIDEO`, `MediaType::AUDIO`)

## Installation

As a library dependency:

```bash
git clone git@github.com:xcopy/whatsapp-psr7-stream-crypto
```

For local development after cloning:

```bash
composer install
```

## Usage

```php
<?php

use GuzzleHttp\Psr7\Utils;
use Xcopy\WhatsappMedia\DecryptingStream;
use Xcopy\WhatsappMedia\EncryptingStream;
use Xcopy\WhatsappMedia\MediaType;

// Encrypt — passing null generates a random 32-byte mediaKey
$encryptingStream = new EncryptingStream(
    source: Utils::streamFor(fopen('input.bin', 'rb')),
    mediaType: MediaType::VIDEO,
    mediaKey: null,           // retrieve the generated key via getMediaKey()
    generateSidecar: true,    // only effective for VIDEO and AUDIO
);

file_put_contents('output.encrypted', (string) $encryptingStream);
file_put_contents('output.sidecar', $encryptingStream->getSidecar() ?? '');

// Decrypt
$decryptingStream = new DecryptingStream(
    source: Utils::streamFor(fopen('output.encrypted', 'rb')),
    mediaType: MediaType::VIDEO,
    mediaKey: $encryptingStream->getMediaKey(),
);

file_put_contents('output.original', (string) $decryptingStream);
```

## CLI helper

```bash
php bin/whatsapp-media encrypt VIDEO ./samples/VIDEO.original ./tmp/VIDEO.encrypted @./samples/VIDEO.key
php bin/whatsapp-media decrypt VIDEO ./samples/VIDEO.encrypted ./tmp/VIDEO.original @./samples/VIDEO.key
```

`*.key` fixtures in `samples` are raw 32-byte binary `mediaKey` values accepted via the `@path/to/key` form. Plain base64 keys are also supported.

## Fixture compatibility

The test suite verifies compatibility with the provided WhatsApp fixtures in `samples`:

- `DecryptingStream` reproduces each `*.original` from `*.encrypted` + `*.key`
- `EncryptingStream` reproduces each `*.encrypted` from `*.original` + `*.key`
- `VIDEO.sidecar` is reproduced exactly for the video fixture

## Tests

```bash
composer test
```
