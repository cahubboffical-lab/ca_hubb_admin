<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Services\HelperService;
use App\Services\CachingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Intervention\Image\ImageManagerStatic;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class AddWatermarkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $imagePath;
    public string $extension;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 300; // 5 minutes

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 60; // Wait 60 seconds before retry

    /**
     * Create a new job instance.
     *
     * @param string $imagePath
     * @param string $extension
     */
    public function __construct(string $imagePath, string $extension)
    {
        $this->imagePath = $imagePath;
        $this->extension = $extension;
    }

    public function handle(): void
    {
        try {
            // Log::info('AddWatermarkJob started', [
            //     'imagePath' => $this->imagePath,
            //     'extension' => $this->extension,
            //     'file_exists' => file_exists($this->imagePath)
            // ]);

            $startTime = microtime(true);

            // Get all settings from cache
            $settings = CachingService::getSystemSettings()->toArray();

            // Check if watermark is enabled
            $watermarkEnabled = isset($settings['watermark_enabled']) && (int)$settings['watermark_enabled'] === 1;
            if (!$watermarkEnabled) {
                // Log::info('Watermark is disabled, skipping watermark job');
                return;
            }
            
            // Log::info('Watermark is enabled, processing job');
            
            // Helper function to extract relative path from URL or return path as is
            $extractPathFromUrl = function($pathOrUrl) {
                if (empty($pathOrUrl)) {
                    return null;
                }
                
                // If it's a URL, extract the path after /storage/
                if (filter_var($pathOrUrl, FILTER_VALIDATE_URL)) {
                    $parsedUrl = parse_url($pathOrUrl);
                    $path = $parsedUrl['path'] ?? '';
                    
                    // Extract path after /storage/
                    if (preg_match('#/storage/(.+)$#', $path, $matches)) {
                        return $matches[1];
                    }
                    
                    // If /storage/ not found, try to extract from path
                    if (preg_match('#/([^/]+/.+)$#', $path, $matches)) {
                        return $matches[1];
                    }
                }
                
                // Return as is if it's already a relative path
                return $pathOrUrl;
            };
            
            // Get watermark image path
            $watermarkImage = $settings['watermark_image'] ?? null;
            $watermarkPath = null;
            $disk = config('filesystems.default');

            if (!empty($watermarkImage)) {
                // Extract relative path from URL if needed
                $watermarkRelativePath = $extractPathFromUrl($watermarkImage);
                
                // Log::info('Watermark image path', [
                //     'original' => $watermarkImage,
                //     'extracted' => $watermarkRelativePath
                // ]);
                
                // Try to get absolute path from storage
                if ($watermarkRelativePath && Storage::disk($disk)->exists($watermarkRelativePath)) {
                    $watermarkPath = Storage::disk($disk)->path($watermarkRelativePath);
                    // Log::info('Watermark found in storage', ['path' => $watermarkPath]);
                } else {
                    // Try direct path if it's a file path
                    if (file_exists($watermarkImage)) {
                        $watermarkPath = $watermarkImage;
                        // Log::info('Watermark found as direct path', ['path' => $watermarkPath]);
                    } else {
                        // Fallback to public path
                        $watermarkPath = public_path('assets/images/logo/' . basename($watermarkImage));
                        // Log::info('Trying public path fallback', ['path' => $watermarkPath]);
                    }
                }
            }

            // If watermark image not found, try company logo
            if (empty($watermarkPath) || !file_exists($watermarkPath)) {
                Log::info('Watermark image not found, trying company logo');
                $companyLogo = $settings['company_logo'] ?? null;
                
                if (!empty($companyLogo)) {
                    // Extract relative path from URL if needed
                    $companyLogoRelativePath = $extractPathFromUrl($companyLogo);
                    
                    if ($companyLogoRelativePath && Storage::disk($disk)->exists($companyLogoRelativePath)) {
                        $watermarkPath = Storage::disk($disk)->path($companyLogoRelativePath);
                        // Log::info('Company logo found in storage', ['path' => $watermarkPath]);
                    } elseif (file_exists($companyLogo)) {
                        $watermarkPath = $companyLogo;
                        // Log::info('Company logo found as direct path', ['path' => $watermarkPath]);
                    } else {
                        $watermarkPath = public_path('assets/images/logo/' . basename($companyLogo));
                        // Log::info('Trying company logo public path', ['path' => $watermarkPath]);
                    }
                } else {
                    $watermarkPath = public_path('assets/images/logo/logo.png');
                    // Log::info('Using default logo path', ['path' => $watermarkPath]);
                }
            }

            if (!file_exists($watermarkPath)) {
                Log::error('Watermark not found after all attempts', [
                    'watermarkPath' => $watermarkPath,
                    'watermarkImage' => $watermarkImage,
                    'companyLogo' => $settings['company_logo'] ?? null
                ]);
                return;
            }

            // Log::info('Watermark file found', ['path' => $watermarkPath]);

            // Get watermark settings
            $opacity = isset($settings['watermark_opacity']) ? (int)$settings['watermark_opacity'] : 25;
            $size = isset($settings['watermark_size']) ? (int)$settings['watermark_size'] : 10;
            $style = $settings['watermark_style'] ?? 'tile';
            $position = $settings['watermark_position'] ?? 'center';
            $rotation = isset($settings['watermark_rotation']) ? (int)$settings['watermark_rotation'] : -30;
            // Intervention/Image rotates in the opposite direction compared to our UI/CSS preview.
            // To keep backend output consistent with the admin preview, invert the rotation sign.
            $appliedRotation = -$rotation;

            // Load image
            $image = Image::make($this->imagePath);
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            // Only resize very large images (over 3000px) to speed up processing
            // This maintains quality for most images while improving performance
            $maxDimension = 3000;
            $needsResize = false;

            if ($originalWidth > $maxDimension || $originalHeight > $maxDimension) {
                $needsResize = true;
                if ($originalWidth > $originalHeight) {
                    $image->resize($maxDimension, null, fn($c) => $c->aspectRatio());
                } else {
                    $image->resize(null, $maxDimension, fn($c) => $c->aspectRatio());
                }
            }

            // Load and prepare watermark once
            $watermark = Image::make($watermarkPath);
            $watermark->opacity($opacity);
            $watermarkWidth = $image->width() * ($size / 100);
            $watermark->resize($watermarkWidth, null, fn($c) => $c->aspectRatio());
            
            // Store original dimensions before rotation
            // Rotate the watermark
            $watermark->rotate($appliedRotation);

            /**
             * 🧩 Apply watermark based on style (optimized)
             */
            if ($style === 'tile') {
                // Optimized tiling: Calculate optimal spacing to limit operations
                $baseSpacing = 1.5;
                $xStep = (int)($watermark->width() * $baseSpacing);
                $yStep = (int)($watermark->height() * $baseSpacing);

                // Calculate number of tiles and optimize spacing if too many
                $tilesX = (int)ceil($image->width() / $xStep);
                $tilesY = (int)ceil($image->height() / $yStep);
                $totalTiles = $tilesX * $tilesY;

                // Limit to max 150 tiles for performance (adjust spacing if needed)
                $maxTiles = 150;
                if ($totalTiles > $maxTiles) {
                    $factor = sqrt($totalTiles / $maxTiles);
                    $xStep = (int)($xStep * $factor);
                    $yStep = (int)($yStep * $factor);
                    // Recalculate after adjustment
                    $tilesX = (int)ceil($image->width() / $xStep);
                    $tilesY = (int)ceil($image->height() / $yStep);
                }

                // Apply tiles efficiently
                for ($y = 0; $y < $image->height(); $y += $yStep) {
                    for ($x = 0; $x < $image->width(); $x += $xStep) {
                        $image->insert($watermark, 'top-left', $x, $y);
                    }
                }
            } else {
                $padding = 10;
                // Single watermark at specified position.
                // IMPORTANT: rely on Intervention's anchor positions using the watermark's
                // real dimensions AFTER rotation to avoid left/right shifting.
                switch ($position) {
                    case 'top-left':
                        $image->insert($watermark, 'top-left', $padding, $padding);
                        break;
                    case 'top-right':
                        $image->insert($watermark, 'top-right', $padding, $padding);
                        break;
                    case 'bottom-left':
                        $image->insert($watermark, 'bottom-left', $padding, $padding);
                        break;
                    case 'bottom-right':
                        $image->insert($watermark, 'bottom-right', $padding, $padding);
                        break;
                    case 'center':
                    default:
                        $image->insert($watermark, 'center');
                        break;
                }
            }

            /**
             * 💾 Save optimized image (replace original)
             */
            $image->encode($this->extension, 85)->save($this->imagePath);

            /**
             * ⚙️ Optimize image further (lossless compression)
             */
            OptimizerChainFactory::create()->optimize($this->imagePath);
        } catch (\Throwable $e) {
            Log::error('Error in AddWatermarkJob: ' . $e->getMessage(), [
                'imagePath' => $this->imagePath,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
