<?php

namespace App\Http\Controllers;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    protected string $imagesPath = 'images/avatars/';

    protected array $genderConfig = [
        'male' => ['path' => 'male/', 'count' => 39],
        'female' => ['path' => 'female/', 'count' => 41],
        'random' => ['path' => 'v1/', 'count' => 80], // backwards compatibility
    ];

    protected array $colors = [
        '#8B5CF6', // Rich Violet - excellent contrast both themes
        '#EF4444', // Vibrant Red - strong visibility
        '#F59E0B', // Warm Amber - balanced luminance
        '#10B981', // Fresh Emerald - great dual contrast
        '#06B6D4', // Cool Cyan - works in both modes
        '#EC4899', // Bright Pink - maintains vibrancy
        '#F97316', // Bold Orange - optimal contrast
        '#3B82F6', // True Blue - classic dual-theme color
        '#8B5A2B', // Rich Brown - earthy and balanced
        '#6366F1', // Deep Indigo - sophisticated contrast
        '#14B8A6', // Teal Green - professional look
        '#DC2626', // Deep Red - strong presence
        '#059669', // Forest Green - natural balance
        '#7C3AED', // Purple - refined and visible
        '#BE185D', // Magenta - vibrant contrast
        '#0891B2', // Sky Blue - fresh and clear
        '#CA8A04', // Golden Yellow - warm visibility
        '#525252', // Neutral Gray - perfect balance
    ];

    protected NameGenderDetector $genderDetector;

    public function __construct()
    {
        $this->genderDetector = new NameGenderDetector();
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

        if (file_exists($filePath)) {
            return response()->file($filePath)
                ->header('Content-Type', 'image/webp')
                ->header('Cache-Control', 'public, max-age=31536000, immutable');
        }

        $manager = new ImageManager(Driver::class);

        $canvas = $manager->create(1024, 1024);

        $canvas->drawCircle(512, 512, function (CircleFactory $circle) use ($selectedColor) {
            $circle->radius(511);
            $circle->background($selectedColor);
        });

        try {
            $selectedImage = $manager->read($selectedImage);
            $selectedImage->resize(920, 920);
        } catch (\Exception $e) {
            dd($selectedImage);
        }

        $canvas->place($selectedImage, 'center', 0, 10);

        if (hexdec(substr($hash, 16, 8)) % 2 == 0) {
            $canvas->flop();
        }

        $canvas->scale(128, 128)->sharpen(2);
        $webp = $canvas->toWebp(100);

        Storage::disk('public')->put("avatars/generated/{$fileName}", $webp);

        return response($webp)
            ->header('Content-Type', 'image/webp')
            ->header('Content-Disposition', 'inline; filename="avatar.webp"')
            ->header('Cache-Control', 'public, max-age=31536000, immutable');
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
