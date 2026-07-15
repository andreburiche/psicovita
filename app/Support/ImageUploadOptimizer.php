<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class ImageUploadOptimizer
{
    /**
     * Redimensiona e regrava a imagem como JPEG (ou mantém SVG intacto).
     * Devolve um UploadedFile temporário pronto para store().
     */
    public function optimize(
        UploadedFile $file,
        int $maxEdge = 1600,
        int $quality = 85,
    ): UploadedFile {
        $mime = (string) $file->getMimeType();

        if ($mime === 'image/svg+xml' || str_ends_with(strtolower($file->getClientOriginalName()), '.svg')) {
            return $file;
        }

        if (! function_exists('imagecreatetruecolor')) {
            return $file;
        }

        $source = $this->createImageResource($file, $mime);
        if ($source === false) {
            return $file;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width < 1 || $height < 1) {
            imagedestroy($source);

            return $file;
        }

        $scale = min(1, $maxEdge / max($width, $height));
        $targetW = max(1, (int) round($width * $scale));
        $targetH = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetW, $targetH);
        if ($canvas === false) {
            imagedestroy($source);

            return $file;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $targetW, $targetH, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetW, $targetH, $width, $height);
        imagedestroy($source);

        $tempPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'psiconecta-'.Str::uuid().'.jpg';
        if (! imagejpeg($canvas, $tempPath, $quality)) {
            imagedestroy($canvas);

            throw new RuntimeException('Não foi possível optimizar a imagem enviada.');
        }

        imagedestroy($canvas);

        return new UploadedFile(
            $tempPath,
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.jpg',
            'image/jpeg',
            null,
            true,
        );
    }

    /**
     * @return \GdImage|resource|false
     */
    private function createImageResource(UploadedFile $file, string $mime): mixed
    {
        $path = $file->getRealPath();
        if ($path === false) {
            return false;
        }

        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    public function cleanupTemp(UploadedFile $file): void
    {
        $path = $file->getRealPath();
        if ($path && File::exists($path) && str_contains($path, 'psiconecta-')) {
            File::delete($path);
        }
    }
}
