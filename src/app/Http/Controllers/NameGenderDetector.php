<?php

namespace App\Http\Controllers;

use GenderDetector\GenderDetector;
use GenderDetector\Gender;

class NameGenderDetector
{
    private GenderDetector $detector;

    public function __construct()
    {
        $this->detector = new GenderDetector();
    }

    /**
     * Detect gender from a first name using the professional gender detection library
     * 
     * @param string $name The first name to analyze
     * @param string|null $country Optional country code for more accurate detection (e.g., 'US', 'IT', 'FR')
     * @return string 'male', 'female', or 'random' if unisex/unknown
     */
    public function detectGender(string $name, ?string $country = null): string
    {
        $name = trim($name);
        
        // Handle empty names
        if (empty($name)) {
            return 'random';
        }

        // Extract first name from full name
        $firstName = $this->extractFirstName($name);
        
        // Use the professional gender detector
        $gender = $this->detector->getGender($firstName, $country);
        
        // Convert the enum result to our expected format
        return $this->mapGenderToOurFormat($gender);
    }

    /**
     * Extract first name from full name string
     */
    public function extractFirstName(string $fullName): string
    {
        $name = trim($fullName);
        
        // Split by space and take first part
        $parts = explode(' ', $name);
        $firstName = $parts[0] ?? '';
        
        // Remove common suffixes like Jr., Sr., III
        $firstName = preg_replace('/\s+(jr|sr|ii|iii|iv|v)\.?$/i', '', $firstName);
        
        // Remove any non-alphabetic characters (keeping international characters)
        $firstName = preg_replace('/[^a-zA-ZÀ-ÿĀ-žА-я]/u', '', $firstName);
        
        return $firstName;
    }

    /**
     * Map the professional library's Gender enum to our expected string format
     */
    private function mapGenderToOurFormat(?Gender $gender): string
    {
        if ($gender === null) {
            return 'random';
        }

        return match ($gender) {
            Gender::Male, Gender::MostlyMale => 'male',
            Gender::Female, Gender::MostlyFemale => 'female',
            Gender::Unisex => 'random',
            default => 'random'
        };
    }

    /**
     * Get detailed gender information including confidence level
     * 
     * @param string $name The first name to analyze
     * @param string|null $country Optional country code
     * @return array Detailed information about the detection
     */
    public function getDetailedGender(string $name, ?string $country = null): array
    {
        $firstName = $this->extractFirstName($name);
        $gender = $this->detector->getGender($firstName, $country);
        
        return [
            'name' => $firstName,
            'original_input' => $name,
            'detected_gender' => $this->mapGenderToOurFormat($gender),
            'raw_result' => $gender?->name ?? 'Unknown',
            'country' => $country,
            'confidence' => $this->getConfidenceLevel($gender),
            'is_confident' => $this->isConfidentDetection($gender)
        ];
    }

    /**
     * Get confidence level based on the gender result
     */
    private function getConfidenceLevel(?Gender $gender): string
    {
        if ($gender === null) {
            return 'none';
        }

        return match ($gender) {
            Gender::Male, Gender::Female => 'high',
            Gender::MostlyMale, Gender::MostlyFemale => 'medium',
            Gender::Unisex => 'low',
            default => 'none'
        };
    }

    /**
     * Check if the detection is confident (not unisex or unknown)
     */
    private function isConfidentDetection(?Gender $gender): bool
    {
        return $gender !== null && 
               $gender !== Gender::Unisex && 
               in_array($gender, [Gender::Male, Gender::Female, Gender::MostlyMale, Gender::MostlyFemale]);
    }

    /**
     * Detect gender with country-specific intelligence
     * This method tries to be smarter about country detection based on the name
     */
    public function detectGenderSmart(string $name): string
    {
        $firstName = $this->extractFirstName($name);
        
        // Try default detection first
        $defaultGender = $this->detector->getGender($firstName);
        
        // If we get a confident result, use it
        if ($this->isConfidentDetection($defaultGender)) {
            return $this->mapGenderToOurFormat($defaultGender);
        }
        
        // For ambiguous cases, try common countries to see if there's consensus
        $countries = ['US', 'GB', 'DE', 'FR', 'IT', 'ES'];
        $results = [];
        
        foreach ($countries as $country) {
            $gender = $this->detector->getGender($firstName, $country);
            if ($gender !== null) {
                $mapped = $this->mapGenderToOurFormat($gender);
                $results[] = $mapped;
            }
        }
        
        // If we have results, use the most common one
        if (!empty($results)) {
            $counts = array_count_values($results);
            arsort($counts);
            $mostCommon = array_key_first($counts);
            
            // Only use if there's a clear preference
            if ($counts[$mostCommon] > 1) {
                return $mostCommon;
            }
        }
        
        // Fallback to default or random
        return $this->mapGenderToOurFormat($defaultGender);
    }
} 