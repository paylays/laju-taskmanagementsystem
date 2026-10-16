<?php

namespace Tests\Unit;

use App\Services\VirusScanner;
use PHPUnit\Framework\TestCase;

class VirusScannerTest extends TestCase
{
    private function stream(string $contents)
    {
        $stream = fopen('php://memory', 'w+');
        fwrite($stream, $contents);
        rewind($stream);

        return $stream;
    }

    public function test_clean_file_passes(): void
    {
        $this->assertFalse((new VirusScanner)->isInfected($this->stream('quarterly report, nothing to see')));
    }

    public function test_eicar_signature_is_detected(): void
    {
        $this->assertTrue((new VirusScanner)->isInfected($this->stream('header '.VirusScanner::EICAR.' trailer')));
    }

    public function test_signature_split_across_read_chunks_is_detected(): void
    {
        // Place the marker so it straddles the 1 MB read boundary.
        $padding = str_repeat('a', 1024 * 1024 - 5);

        $this->assertTrue((new VirusScanner(['DEMO-MARKER']))->isInfected($this->stream($padding.'DEMO-MARKER')));
    }

    public function test_custom_marker_is_detected_and_empty_markers_are_ignored(): void
    {
        $scanner = new VirusScanner([VirusScanner::EICAR, '', 'LAJUR-SIMULATED-VIRUS']);

        $this->assertTrue($scanner->isInfected($this->stream('x LAJUR-SIMULATED-VIRUS x')));
        $this->assertFalse($scanner->isInfected($this->stream('plain text')));
    }
}
