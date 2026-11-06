<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;

class MomentImageFiles
{
    public static function valid(?string $path): bool { return is_string($path) && preg_match('~^moment-images/[a-f0-9]{48}\.(webp|jpg)$~D', $path) === 1; }
    public static function remove(?string $path): void { if (self::valid($path)) Storage::disk('local')->delete($path); }
    public function save(string $bytes): string
    {
        if (!function_exists('imagecreatefromstring')) throw new \RuntimeException('PHP GD ontbreekt.');
        $info = @getimagesizefromstring($bytes);
        if (!in_array($info['mime'] ?? '', ['image/png', 'image/jpeg'], true) || strlen($bytes) > 12 * 1024 * 1024 || ($info[0] ?? 0) > 2048 || ($info[1] ?? 0) > 2048 || min($info[0] ?? 0, $info[1] ?? 0) < 128) throw new \RuntimeException('Ongeldige momentjesafbeelding.');
        $source = @imagecreatefromstring($bytes);
        if (!$source) throw new \RuntimeException('Afbeelding niet leesbaar.');
        $size = 384;
        $out = imagecreatetruecolor($size, $size);
        try {
            imagefill($out, 0, 0, imagecolorallocate($out, 255, 253, 245));
            // Centre crop; request prompts keep the single subject in the centre.
            $side = min(imagesx($source), imagesy($source));
            imagecopyresampled($out, $source, 0, 0, (int) ((imagesx($source) - $side) / 2), (int) ((imagesy($source) - $side) / 2), $size, $size, $side, $side);
            $webp = function_exists('imagewebp');
            ob_start();
            try { $ok = $webp ? imagewebp($out, null, 78) : imagejpeg($out, null, 80); $small = ob_get_contents(); }
            finally { ob_end_clean(); }
            if (!$ok || !$small || strlen($small) > 200 * 1024) throw new \RuntimeException('Kleine afbeelding maken mislukt.');
            $path = 'moment-images/'.bin2hex(random_bytes(24)).($webp ? '.webp' : '.jpg');
            if (!Storage::disk('local')->put($path, $small)) throw new \RuntimeException('Afbeelding opslaan mislukt.');
            return $path;
        } finally { imagedestroy($source); imagedestroy($out); }
    }
}
