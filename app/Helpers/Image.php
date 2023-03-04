<?php

declare(strict_types=1);

namespace App\Helpers;

class Image
{
    public const AVATAR_MAX_WIDTH = 512;
    public const AVATAR_MAX_HEIGHT = 512;

    public static function resizeAvatar(string $path): void
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        $image = imagecreatefromstring(file_get_contents($path));
        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = $width / $height;
        $newWidth = $width;
        $newHeight = $height;

        if ($width > $height) {
            if ($width > self::AVATAR_MAX_WIDTH) {
                $newWidth = self::AVATAR_MAX_WIDTH;
                $newHeight = $newWidth / $ratio;
            }
        } else {
            if ($height > self::AVATAR_MAX_HEIGHT) {
                $newHeight = self::AVATAR_MAX_HEIGHT;
                $newWidth = $newHeight * $ratio;
            }
        }

        $newImage = imagecreatetruecolor((int) $newWidth, (int) $newHeight);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, (int) $newWidth, (int) $newHeight, $width, $height);

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($newImage, $path);
                break;
            case 'png':
                imagepng($newImage, $path);
                break;
            default:
                throw new \Exception('Unsupported image type.');
        }
    }
}
