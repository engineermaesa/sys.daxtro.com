<?php

namespace App\Services\Aftersales;

use Illuminate\Http\UploadedFile;

class ServiceFormImage
{
    private const HEIC_TYPES = ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'];

    public static function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'max:10240', 'mimetypes:'.implode(',', [
                'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp', 'image/x-ms-bmp', ...self::HEIC_TYPES,
            ])],
            'caption' => 'nullable|string|max:500',
            'captured_at' => 'nullable|date',
        ];
    }

    public static function jpeg(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        $mime = $file->getMimeType();
        if (in_array($mime, self::HEIC_TYPES, true)) {
            abort_unless(extension_loaded('imagick'), 422, 'HEIC conversion is unavailable on this server. Export this photo as JPG or PNG and retry.');
            try {
                $image = new \Imagick($path);
                $image->setIteratorIndex(0);
                abort_if($image->getImageWidth() * $image->getImageHeight() > 50000000, 422, 'Photo dimensions are too large.');
                $image->autoOrientImage();
                if (max($image->getImageWidth(), $image->getImageHeight()) > 1600) {
                    $image->thumbnailImage(1600, 1600, true);
                }
                $image->setImageFormat('jpeg');
                $image->setImageCompressionQuality(82);

                return $image->getImageBlob();
            } catch (\ImagickException) {
                abort(422, 'This photo could not be decoded.');
            }
        }

        $size = @getimagesize($path);
        abort_unless($size && $size[0] * $size[1] <= 50000000, 422, 'Photo dimensions are too large or invalid.');
        $image = @imagecreatefromstring((string) file_get_contents($path));
        abort_unless($image, 422, 'This photo could not be decoded.');
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $orientation = (@exif_read_data($path)['Orientation']) ?? 1;
            if (in_array($orientation, [2, 5, 7], true)) {
                imageflip($image, IMG_FLIP_HORIZONTAL);
            } elseif ($orientation === 4) {
                imageflip($image, IMG_FLIP_VERTICAL);
            }
            $rotation = match ($orientation) {
                3 => 180, 5, 8 => 90, 6, 7 => -90, default => 0,
            };
            if ($rotation) {
                $image = imagerotate($image, $rotation, 0);
            }
        }

        return self::resize($image, 1600, 82);
    }

    public static function thumbnail(string $jpeg): string
    {
        return self::resize(imagecreatefromstring($jpeg), 400, 80);
    }

    private static function resize(\GdImage $source, int $max, int $quality): string
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $ratio = min(1, $max / max($width, $height));
        $target = imagecreatetruecolor(max(1, (int) ($width * $ratio)), max(1, (int) ($height * $ratio)));
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);
        ob_start();
        imagejpeg($target, null, $quality);

        return ob_get_clean();
    }
}
