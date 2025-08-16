<?php

namespace App\Http\Controllers;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class AvatarController extends Controller
{
    protected string $imagesPath = 'images/avatars/';

    protected array $genderConfig = [
        'male' => ['path' => 'male/', 'count' => 27],
        'female' => ['path' => 'female/', 'count' => 31],
        'random' => ['path' => 'v1/', 'count' => 58], // backwards compatibility
    ];

    protected array $colors = [
        '#A78BFA', // Medium Purple
        '#FB7185', // Rose
        '#F59E0B', // Amber
        '#10B981', // Emerald
        '#06B6D4', // Cyan
        '#8B5CF6', // Violet
        '#F97316', // Orange
        '#EF4444', // Red
        '#3B82F6', // Blue
        '#84CC16', // Lime
        '#EC4899', // Pink
        '#6366F1', // Indigo
        '#14B8A6', // Teal
        '#F59E0B', // Yellow
        '#8B5A2B', // Brown
        '#6B7280', // Gray
        '#DC2626', // Crimson
        '#059669', // Green
    ];

    protected NameGenderDetector $genderDetector;
    protected static $imageManagerInstance = null;
    protected static $loadedImages = [];

    public function __construct()
    {
        $this->genderDetector = new NameGenderDetector();
    }

    /**
     * Get singleton ImageManager instance to avoid recreation overhead
     */
    protected function getImageManager(): ImageManager
    {
        if (self::$imageManagerInstance === null) {
            self::$imageManagerInstance = new ImageManager(Driver::class);
        }
        return self::$imageManagerInstance;
    }

    /**
     * Cache loaded base images in memory to avoid repeated file reads
     */
    protected function getBaseImage(string $imagePath): \Intervention\Image\Interfaces\ImageInterface
    {
        $cacheKey = md5($imagePath);
        
        if (!isset(self::$loadedImages[$cacheKey])) {
            self::$loadedImages[$cacheKey] = $this->getImageManager()->read($imagePath);
        }
        
        // Return a copy to avoid modifying the cached image
        return clone self::$loadedImages[$cacheKey];
    }

    public function generate(?string $name = null, ?string $gender = null)
    {
        $colorIndex = request()->get('color') ?? null;
        $country = request()->get('country') ?? null; // NEW: Country parameter for better detection

        // Handle empty name
        if (!$name) {
            $name = Str::random(6);
            $gender = $gender ?? 'random';
            return redirect()->route('avatar.generate', ['name' => $name, 'gender' => $gender, 'color' => $colorIndex]);
        }

        // Enhanced automatic gender detection if not specified
        if (!$gender) {
            // Use smart detection that considers multiple countries for ambiguous names
            $gender = $this->genderDetector->detectGenderSmart($name);
            
            // If country is specified, try country-specific detection first
            if ($country) {
                $countrySpecificGender = $this->genderDetector->detectGender($name, $country);
                if ($countrySpecificGender !== 'random') {
                    $gender = $countrySpecificGender;
                }
            }
        }

        // Validate gender parameter
        if (!array_key_exists($gender, $this->genderConfig)) {
            $gender = 'random';
        }

        if ($colorIndex < 0 || $colorIndex > count($this->colors) - 1) {
            $colorIndex = null;
        }

        $hash = md5($name);
        
        // Get gender-specific configuration
        $genderData = $this->genderConfig[$gender];
        $genderPath = $genderData['path'];
        $totalImages = $genderData['count'];

        // Select image based on gender and available count
        $imageIndex = hexdec(substr($hash, 0, 8)) % $totalImages;
        
        // Build image path based on gender
        if ($gender === 'random') {
            $selectedImage = public_path($this->imagesPath . $genderPath . ($imageIndex + 1) . '.png');
        } else {
            // For male/female, get list of actual files and select from them
            $genderDir = public_path($this->imagesPath . $genderPath);
            $files = glob($genderDir . '*.png');
            if (empty($files)) {
                // Fallback to random if no files found
                $selectedImage = public_path($this->imagesPath . 'v1/' . ($imageIndex % 58 + 1) . '.png');
            } else {
                sort($files); // Ensure consistent ordering
                $selectedFile = $files[$imageIndex % count($files)];
                $selectedImage = $selectedFile; // Use full path directly
            }
        }

        if (!$colorIndex) {
            $colorIndex = hexdec(substr($hash, 8, 8)) % count($this->colors);
        }
        $selectedColor = $this->colors[$colorIndex];

        // Enhanced filename with country and detection method
        $detectedGender = $this->genderDetector->detectGenderSmart($name);
        $countryCode = $country ? '_' . strtolower($country) : '';
        $cacheKey = $gender === $detectedGender ? $gender : $gender . '_override';
        $fileName = "{$hash}_{$cacheKey}{$countryCode}_{$colorIndex}.webp";
        $filePath = storage_path("app/public/avatars/{$fileName}");

        // Check if file exists in cache first (faster than file_exists)
        $avatarExists = Cache::remember("avatar_exists_{$fileName}", 3600, function() use ($filePath) {
            return file_exists($filePath);
        });

        if ($avatarExists && file_exists($filePath)) {
            $response = response()->file($filePath);
            $response->headers->set('Content-Type', 'image/webp');
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            $response->headers->set('ETag', '"' . $hash . '"');
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T', filemtime($filePath)));
            return $response;
        }

        $manager = $this->getImageManager();

        $canvas = $manager->create(1024, 1024);

        $canvas->drawCircle(512, 512, function (CircleFactory $circle) use ($selectedColor) {
            $circle->radius(511);
            $circle->background($selectedColor);
        });

        try {
            $selectedImage = $this->getBaseImage($selectedImage);
            $selectedImage->resize(920, 920);
        } catch (\Exception $e) {
            \Log::error('Avatar generation failed', ['image_path' => $selectedImage, 'error' => $e->getMessage()]);
            abort(500, 'Avatar generation failed');
        }

        $canvas->place($selectedImage, 'center', 0, 10);

        if (hexdec(substr($hash, 16, 8)) % 2 == 0) {
            $canvas->flop();
        }

        $canvas->scale(128, 128)->sharpen(2);
        $webp = $canvas->toWebp(100);

        // Store the generated avatar
        Storage::disk('public')->put("avatars/{$fileName}", $webp);
        
        // Update cache to reflect the new file exists
        Cache::put("avatar_exists_{$fileName}", true, 3600);

        return response($webp)
            ->header('Content-Type', 'image/webp')
            ->header('Content-Disposition', 'inline; filename="avatar.webp"')
            ->header('Cache-Control', 'public, max-age=31536000, immutable')
            ->header('ETag', '"' . $hash . '"')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T'));
    }

    /**
     * Clear memory caches to prevent memory leaks in long-running processes
     */
    public static function clearMemoryCache(): void
    {
        self::$loadedImages = [];
        if (self::$imageManagerInstance) {
            self::$imageManagerInstance = null;
        }
    }

    /**
     * Simple gender detection endpoint (for testing/debugging)
     */
    public function detectGender(string $name)
    {
        $country = request()->get('country');
        $detectedGender = $this->genderDetector->detectGender($name, $country);
        
        return response()->json([
            'name' => $name,
            'detected_gender' => $detectedGender,
            'country' => $country
        ]);
    }

    /**
     * Enhanced gender detection with detailed information
     */
    public function detectGenderDetailed(string $name)
    {
        $country = request()->get('country');
        $detailedInfo = $this->genderDetector->getDetailedGender($name, $country);
        
        // Also include smart detection result
        $smartGender = $this->genderDetector->detectGenderSmart($name);
        $detailedInfo['smart_detection'] = $smartGender;
        
        return response()->json($detailedInfo);
    }

    /**
     * Country-specific detection comparison
     */
    public function compareCountries(string $name)
    {
        $countries = ['US', 'GB', 'DE', 'FR', 'IT', 'ES', 'NL', 'SE', 'NO', 'DK'];
        $results = [];
        
        // Default detection
        $results['default'] = $this->genderDetector->detectGender($name);
        
        // Country-specific results
        foreach ($countries as $country) {
            $results[$country] = $this->genderDetector->detectGender($name, $country);
        }
        
        // Smart detection
        $results['smart'] = $this->genderDetector->detectGenderSmart($name);
        
        return response()->json([
            'name' => $name,
            'results' => $results,
            'recommendation' => $results['smart']
        ]);
    }
}
