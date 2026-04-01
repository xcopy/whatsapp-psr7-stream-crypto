<?php

namespace Xcopy\WhatsappMedia\Tests;

use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Xcopy\WhatsappMedia\DecryptingStream;
use Xcopy\WhatsappMedia\EncryptingStream;
use Xcopy\WhatsappMedia\MediaType;

final class SampleCompatibilityTest extends TestCase
{
    #[DataProvider('mediaFixtureProvider')]
    public function testDecryptsProvidedFixture(MediaType $mediaType): void
    {
        $decrypted = new DecryptingStream(
            source: Utils::streamFor($this->fixture($mediaType, 'encrypted')),
            mediaType: $mediaType,
            mediaKey: $this->fixture($mediaType, 'key'),
        );

        self::assertSame($this->fixture($mediaType, 'original'), (string) $decrypted);
    }

    #[DataProvider('mediaFixtureProvider')]
    public function testEncryptsToProvidedFixture(MediaType $mediaType): void
    {
        $encrypted = new EncryptingStream(
            source: Utils::streamFor($this->fixture($mediaType, 'original')),
            mediaType: $mediaType,
            mediaKey: $this->fixture($mediaType, 'key'),
            generateSidecar: $mediaType === MediaType::VIDEO,
        );

        self::assertSame($this->fixture($mediaType, 'encrypted'), (string) $encrypted);
    }

    public function testGeneratesProvidedVideoSidecar(): void
    {
        $encrypted = new EncryptingStream(
            source: Utils::streamFor($this->fixture(MediaType::VIDEO, 'original')),
            mediaType: MediaType::VIDEO,
            mediaKey: $this->fixture(MediaType::VIDEO, 'key'),
            generateSidecar: true,
        );

        self::assertSame($this->fixture(MediaType::VIDEO, 'sidecar'), $encrypted->getSidecar());
    }

    /**
     * @return array<string, array{0: MediaType}>
     */
    public static function mediaFixtureProvider(): array
    {
        return [
            MediaType::IMAGE->value => [MediaType::IMAGE],
            MediaType::AUDIO->value => [MediaType::AUDIO],
            MediaType::VIDEO->value => [MediaType::VIDEO],
        ];
    }

    private function fixture(MediaType $mediaType, string $extension): string
    {
        $path = dirname(__DIR__) . '/samples/' . $mediaType->value . '.' . $extension;
        self::assertFileExists($path);

        $contents = file_get_contents($path);
        self::assertNotFalse($contents);

        return $contents;
    }
}
