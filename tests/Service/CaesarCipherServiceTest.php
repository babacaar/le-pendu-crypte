<?php
namespace App\Tests\Service;

use App\Service\CaesarCipherService;
use PHPUnit\Framework\TestCase;

class CaesarCipherServiceTest extends TestCase
{
    private CaesarCipherService $service;

    protected function setUp(): void
    {
        $this->service = new CaesarCipherService();
    }

    public function testEncryptDecryptRoundTrip()
    {
        $word = 'CLEOPATRE';
        $shift = 17;
        $encrypted = $this->service->encrypt($word, $shift);
        $decrypted = $this->service->decrypt($encrypted, $shift);
        $this->assertEquals($word, $decrypted);
    }

    /** @dataProvider invalidInputsProvider */
    public function testEncryptInvalidInputs($input, $shift)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->encrypt($input, $shift);
    }

    public function invalidInputsProvider(): array
    {
        return [
            ['HELLO', -5],          // negative shift
            ['WORLD', 5.5],         // non-integer shift
            [12345, 3],             // non-string text
            ['TEXT', 9999999999],   // shift too large?
        ];
    }
}