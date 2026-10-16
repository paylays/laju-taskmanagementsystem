<?php

namespace App\Services;

/**
 * Simulated antivirus: flags files containing any of the given signatures.
 * Replace with a ClamAV client in production; callers only use isInfected().
 */
class VirusScanner
{
    public const EICAR = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';

    private const CHUNK = 1024 * 1024;

    /** @var list<string> */
    private array $signatures;

    /**
     * @param list<string> $signatures
     */
    public function __construct(array $signatures = [self::EICAR])
    {
        $this->signatures = array_values(array_filter($signatures, fn ($s) => $s !== ''));
    }

    /**
     * @param resource $stream closed by this method
     */
    public function isInfected($stream): bool
    {
        $overlap = max(array_map('strlen', $this->signatures)) - 1;
        $tail = '';

        try {
            while (! feof($stream)) {
                $chunk = fread($stream, self::CHUNK);
                if ($chunk === false) {
                    break;
                }

                // Keep the end of the previous chunk so a signature split across reads is still found.
                $window = $tail.$chunk;
                foreach ($this->signatures as $signature) {
                    if (str_contains($window, $signature)) {
                        return true;
                    }
                }
                $tail = substr($window, -$overlap);
            }
        } finally {
            fclose($stream);
        }

        return false;
    }
}
