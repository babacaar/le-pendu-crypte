<?php
namespace App\Tests\Service;

use App\Service\PointSystemService;
use PHPUnit\Framework\TestCase;

class PointSystemServiceTest extends TestCase
{
    private PointSystemService $svc;

    protected function setUp(): void
    {
        $this->svc = new PointSystemService($this->createMock(\Doctrine\ORM\EntityManagerInterface::class));
    }

    /** @dataProvider boundaryProvider */
    public function testCalculatePointsBoundary(string $difficulty, int $errors, bool $usedHint, int $expected)
    {
        $actual = $this->svc->calculatePoints($difficulty, $errors, $usedHint);
        $this->assertSame($expected, $actual, "Boundary test failed for $difficulty, errors=$errors, hint=" . ($usedHint? 'yes':'no'));
    }

    public function boundaryProvider(): array
    {
        return [
            // perfect rounds (0 errors, no hint)
            ['easy',   0, false,  50],  // only one easy‐perfect case
            ['medium', 0, false, 110],
            ['hard',   0, false, 160],
    
            // max errors (e.g. length-of-word errors)
            ['easy',   100, false, 0],
            ['medium', 100, true,  0],
            ['hard',   100, true,  0],
        ];
    }
    
    public function testInvalidDifficultyThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->svc->calculatePoints('impossible', 0, false);
    }
}