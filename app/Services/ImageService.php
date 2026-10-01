<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores uploaded images safely.
 *
 * An upload is never saved as it arrived. The image is decoded, resized and
 * written again as a new WebP file with a random name. That removes anything
 * hidden inside the original file (scripts, metadata, a fake extension) and
 * keeps pages fast.
 */
class ImageService
{
    /**
     * The disk that holds public images (storage/app/public).
     */
    private const DISK = 'public';

    /**
     * Validation rules for an optional image field. Use in a Form Request:
     * 'image' => ImageService::rules().
     *
     * @return list<string>
     */
    public static function rules(): array
    {
        return [
            'nullable',
            'file',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'extensions:jpg,jpeg,png,webp',
            'max:'.config('community.max_image_kilobytes'),
            'dimensions:max_width=6000,max_height=6000',
        ];
    }

    /**
     * Resize the upload so its longest side is at most $maxSide pixels,
     * save it as WebP in $folder, and return the stored path.
     */
    public function store(UploadedFile $file, string $folder, int $maxSide = 1600): string
    {
        $image = $this->decode($file);

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxSide / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Keep transparent areas transparent.
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagewebp($resized, null, 82);
        $contents = (string) ob_get_clean();

        $path = $folder.'/'.Str::random(40).'.webp';

        Storage::disk(self::DISK)->put($path, $contents);

        return $path;
    }

    /**
     * Delete a stored image. Does nothing when there is no image.
     */
    public function delete(?string $path): void
    {
        if ($path !== null) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * The address a browser uses to load a stored image.
     */
    public static function url(?string $path): ?string
    {
        return $path === null ? null : '/storage/'.$path;
    }

    /**
     * Read the upload as an image, based on what the file really contains
     * and not on its name. Fails validation if it is not a JPEG, PNG or WebP.
     */
    private function decode(UploadedFile $file): GdImage
    {
        $type = @getimagesize($file->getRealPath())[2] ?? null;

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG => @imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file->getRealPath()),
            default => false,
        };

        if ($image === false) {
            throw ValidationException::withMessages([
                'image' => 'This file could not be read as an image. Upload a JPEG, PNG or WebP.',
            ]);
        }

        // PNG files with a colour palette must be converted before resizing.
        imagepalettetotruecolor($image);

        return $image;
    }
}
