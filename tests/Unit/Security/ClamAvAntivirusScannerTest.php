<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Domain\Security\AntivirusScanFailedException;
use App\Domain\Security\Scanners\ClamAvAntivirusScanner;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class ClamAvAntivirusScannerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
    }

    public function test_a_clean_file_returns_a_clean_result(): void
    {
        Process::fake(['*' => Process::result(output: '', exitCode: 0)]);

        $result = (new ClamAvAntivirusScanner('/usr/bin/clamdscan'))->scan('/tmp/fichier.pdf');

        $this->assertTrue($result->clean);
    }

    public function test_an_infected_file_returns_the_threat_name(): void
    {
        Process::fake([
            '*' => Process::result(output: "/tmp/fichier.pdf: Eicar-Test-Signature FOUND\n", exitCode: 1),
        ]);

        $result = (new ClamAvAntivirusScanner('/usr/bin/clamdscan'))->scan('/tmp/fichier.pdf');

        $this->assertFalse($result->clean);
        $this->assertSame('Eicar-Test-Signature', $result->menace);
    }

    public function test_an_unreachable_daemon_throws_a_scan_failed_exception(): void
    {
        Process::fake(['*' => Process::result(errorOutput: 'ERROR: Could not connect to clamd', exitCode: 2)]);

        $this->expectException(AntivirusScanFailedException::class);

        (new ClamAvAntivirusScanner('/usr/bin/clamdscan'))->scan('/tmp/fichier.pdf');
    }
}
