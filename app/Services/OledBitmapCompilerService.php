<?php

namespace App\Services;

use App\Models\OledPart;
use Illuminate\Support\Facades\Storage;

class OledBitmapCompilerService
{
    /**
     * Compile editor json frames into 1KB (2048 hex chars) SSD1306 monochrome bitmaps.
     * 
     * @param array $editorData
     * @return array
     */
    public function compile(array $editorData): array
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $frames = $editorData['frames'] ?? [];
        $compiledFrames = [];

        foreach ($frames as $frameIndex => $frame) {
            // 1. Create 128x64 GD truecolor image (black background)
            $canvas = imagecreatetruecolor(128, 64);
            $black = imagecolorallocate($canvas, 0, 0, 0);
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $black);

            // Sort layers by zIndex
            $layers = $frame['layers'] ?? [];
            usort($layers, function ($a, $b) {
                return ($a['zIndex'] ?? 0) <=> ($b['zIndex'] ?? 0);
            });

            foreach ($layers as $layer) {
                $partId = $layer['part_id'] ?? null;
                $x = intval($layer['x'] ?? 0);
                $y = intval($layer['y'] ?? 0);
                $w = isset($layer['width']) ? intval($layer['width']) : null;
                $h = isset($layer['height']) ? intval($layer['height']) : null;

                $part = OledPart::find($partId);
                if (!$part || !file_exists(public_path($part->file_path))) {
                    continue;
                }

                $partImg = $this->loadResourceImage(public_path($part->file_path));
                if (!$partImg) {
                    continue;
                }

                $srcW = imagesx($partImg);
                $srcH = imagesy($partImg);
                $destW = $w ? $w : $srcW;
                $destH = $h ? $h : $srcH;

                imagecopyresampled(
                    $canvas, $partImg,
                    $x, $y, 0, 0,
                    $destW, $destH, $srcW, $srcH
                );
                imagedestroy($partImg);
            }

            // 2. Generate SSD1306 1KB binary buffer (128 * 64 / 8 = 1024 bytes)
            $bytes = array_fill(0, 1024, 0);

            for ($y = 0; $y < 64; $y++) {
                for ($x = 0; $x < 128; $x++) {
                    $rgb = imagecolorat($canvas, $x, $y);
                    $colors = imagecolorsforindex($canvas, $rgb);
                    $brightness = ($colors['red'] * 299 + $colors['green'] * 587 + $colors['blue'] * 114) / 1000;
                    
                    if ($brightness > 128) {
                        $byteIndex = $x + intval($y / 8) * 128;
                        $bitIndex = $y % 8;
                        $bytes[$byteIndex] |= (1 << $bitIndex);
                    }
                }
            }

            imagedestroy($canvas);

            // 3. Convert to 2048 characters HEX string
            $hexStr = '';
            foreach ($bytes as $b) {
                $hexStr .= str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
            }
            $compiledFrames[] = strtoupper($hexStr);
        }

        return $compiledFrames;
    }

    private function loadResourceImage(string $path)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'png') {
            return @imagecreatefrompng($path);
        } elseif ($ext === 'jpg' || $ext === 'jpeg') {
            return @imagecreatefromjpeg($path);
        } elseif ($ext === 'gif') {
            return @imagecreatefromgif($path);
        }
        return null;
    }
}
