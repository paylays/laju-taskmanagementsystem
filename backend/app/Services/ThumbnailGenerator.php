<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;

class ThumbnailGenerator
{
    public function __construct(private readonly int $size = 320) {}

    /**
     * Writes a JPEG that fits inside size x size next to the original and returns its path,
     * or null if GD cannot decode the image.
     */
    public function generate(Filesystem $disk, string $sourcePath): ?string
    {
        $source = @imagecreatefromstring((string) $disk->get($sourcePath));
        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $this->size / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        // JPEG has no alpha channel; paint transparent areas white instead of black.
        imagefill($thumb, 0, 0, imagecolorallocate($thumb, 255, 255, 255));
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Progressive JPEG renders a full low-detail preview first on slow connections.
        imageinterlace($thumb, true);
        ob_start();
        imagejpeg($thumb, null, 82);
        $jpeg = ob_get_clean();

        $thumbPath = preg_replace('#^attachments/#', 'thumbnails/', $sourcePath);
        $thumbPath = preg_replace('/\.[^.\/]+$/', '', $thumbPath).'.jpg';
        $disk->put($thumbPath, $jpeg);

        return $thumbPath;
    }
}
