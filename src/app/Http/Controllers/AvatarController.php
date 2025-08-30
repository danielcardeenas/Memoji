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

    protected array $paleColors = [
        '#C4B5FD', // Pale Violet - soft and light
        '#FCA5A5', // Pale Red - gentle and warm
        '#FDE68A', // Pale Amber - soft yellow tone
        '#86EFAC', // Pale Emerald - light mint green
        '#67E8F9', // Pale Cyan - soft sky blue
        '#F9A8D4', // Pale Pink - gentle rose
        '#FDBA74', // Pale Orange - soft peach
        '#93C5FD', // Pale Blue - light sky
        '#D2B48C', // Pale Brown - soft tan
        '#A5B4FC', // Pale Indigo - light lavender
        '#7DD3FC', // Pale Teal - soft aqua
        '#F87171', // Pale Deep Red - soft coral
        '#6EE7B7', // Pale Forest Green - mint
        '#C084FC', // Pale Purple - soft lilac
        '#F472B6', // Pale Magenta - soft rose
        '#38BDF8', // Pale Sky Blue - light blue
        '#FCD34D', // Pale Golden Yellow - soft gold
        '#9CA3AF', // Pale Gray - light neutral
    ];

    protected NameGenderDetector $genderDetector;

    public function __construct()
    {
        $this->genderDetector = new NameGenderDetector();
    }

    public function generate(?string $name = null, ?string $gender = null)
    {
        $colorIndex = request()->get('color') ?? null;
        $palette = request()->get('palette') ?? 'default'; // NEW: Palette parameter (default or pale)
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

        // Validate palette parameter
        if (!in_array($palette, ['default', 'pale'])) {
            $palette = 'default';
        }

        // Select color palette based on parameter
        $selectedColorPalette = $palette === 'pale' ? $this->paleColors : $this->colors;

        if ($colorIndex < 0 || $colorIndex > count($selectedColorPalette) - 1) {
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
            $colorIndex = hexdec(substr($hash, 8, 8)) % count($selectedColorPalette);
        }
        $selectedColor = $selectedColorPalette[$colorIndex];

        // Enhanced filename with country, palette, and detection method
        $detectedGender = $this->genderDetector->detectGenderSmart($name);
        $countryCode = $country ? '_' . strtolower($country) : '';
        $paletteCode = $palette === 'pale' ? '_pale' : '';
        $cacheKey = $gender === $detectedGender ? $gender : $gender . '_override';
        $fileName = "{$hash}_{$cacheKey}{$countryCode}{$paletteCode}_{$colorIndex}.webp";
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


}
