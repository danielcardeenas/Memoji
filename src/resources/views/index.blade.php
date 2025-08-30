<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tapback Memoji's - Open Source Memojis for your apps, designs, and websites.</title>
    <meta name="description" content="Open source memojis for your apps, designs, and websites.">
    <meta name="keywords" content="memoji, avatar, api, open source, webp, svg, image, design, website">
    <meta name="author" content="Wes Wimell">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@wes_wim">
    <meta name="twitter:creator" content="@wes_wim">
    <meta name="twitter:title" content="Tapback Memoji's - Open Source Memojis">
    <meta name="twitter:description" content="Open source memojis for your apps, designs, and websites.">
    <meta name="twitter:image" content="{{ env('APP_URL') }}/images/og-image.png">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
<body class="bg-white text-black font-mono dark:bg-black dark:text-white">
    <div class="px-4 py-12  mx-auto max-w-max">
        <!-- Header Section -->
        <header class="text-center mb-12">
            <div class="flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-smile"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>
            </div>
            <h1 class="text-2xl font-semibold mb-2">Tapback Memoji's</h1>
            <p class="text-gray-600 dark:text-gray-400 text-base">API for Apple Memoji avatars.</p>
            <div class="flex items-center justify-center scale-125 mt-4 hover:scale-[1.27] transition-transform duration-50">
                <a href="https://github.com/wimell/tapback-memojis" target="_blank">
                    <img alt="GitHub Repo stars" src="https://img.shields.io/github/stars/wimell/tapback-memojis?style=social&label=Star on Github">
                </a>
            </div>

        </header>

        <!-- Main Content Section -->
        <main class="space-y-12">

            <!-- Gender Demo Section -->
            <section class="text-center">
                <h2 class="text-lg font-semibold mb-6">👨👩 Gender-Specific Avatars</h2>
                <div class="grid grid-cols-2 gap-8 max-w-md mx-auto">
                    <div>
                        <h3 class="text-sm font-medium mb-3 text-gray-600 dark:text-gray-400">Male Avatars</h3>
                        <div class="grid grid-cols-3 gap-2">
                            @for ($i = 0; $i < 6; $i++)
                                <div class="w-16 h-16 rounded-full bg-white overflow-hidden dark:bg-black mx-auto">
                                    <img src="{{ route('avatar.generate.gender', ['name' => 'male' . ($i), 'gender' => 'male']) }}?color={{ $i + 5 }}"
                                         class="w-full h-auto"
                                         loading="lazy"
                                         alt="Male Avatar">
                                </div>
                            @endfor
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium mb-3 text-gray-600 dark:text-gray-400">Female Avatars</h3>
                        <div class="grid grid-cols-3 gap-2">
                            @for ($i = 0; $i < 6; $i++)
                                <div class="w-16 h-16 rounded-full bg-white overflow-hidden dark:bg-black mx-auto">
                                    <img src="{{ route('avatar.generate.gender', ['name' => 'female' . ($i), 'gender' => 'female']) }}?color={{ $i + 11 }}"
                                         class="w-full h-auto"
                                         loading="lazy"
                                         alt="Female Avatar">
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-5 md:grid-cols-10 gap-3 max-w-max mx-auto md:hidden">
                @for ($i = 0; $i < 15; $i++)
                        <div class="w-16 h-16  rounded-full bg-white overflow-hidden dark:bg-black">
                            <img src="{{ route('avatar.generate', ['name' => 'user' . ($i), 'color' => $i]) }}"
                                 class="w-full h-auto"
                                 loading="lazy"
                                 alt="Memoji Avatar">
                        </div>
                @endfor
            </div>


            <!-- API Usage Section -->
            <div>
                <h2 class="text-lg font-semibold mb-4">🔗 Usage</h2>
            <section class="bg-neutral-100 p-6 rounded-lg dark:bg-neutral-950">

                <p class="text-gray-600 text-sm dark:text-gray-400 mb-3">🤖 <strong>Smart auto-detection</strong> (detects gender from name automatically):</p>
                <code class="block bg-white p-3 rounded-md text-xs mb-3 dark:bg-neutral-900">
                    {{ env('APP_URL') }}/api/avatar/{name}.webp
                </code>
                <p class="text-gray-600 text-sm dark:text-gray-400 mb-3">Examples: <em>john.webp → male avatars</em>, <em>sarah.webp → female avatars</em></p>
                
                <p class="text-gray-600 text-sm dark:text-gray-400 mb-3">🎯 <strong>Manual gender override:</strong></p>
                <code class="block bg-white p-3 rounded-md text-xs mb-3 dark:bg-neutral-900">
                    {{ env('APP_URL') }}/api/avatar/{name}/{gender}.webp
                </code>
                <p class="text-gray-600 text-sm dark:text-gray-400 mb-1">Gender options: <strong>male</strong>, <strong>female</strong>, <strong>random</strong></p>
                <p class="text-gray-600 text-sm dark:text-gray-400 mb-1">Add color parameter: <strong>?color=0-17</strong></p>
                <p class="text-gray-600 text-sm dark:text-gray-400 mb-3">Add palette parameter: <strong>?palette=default</strong> or <strong>?palette=pale</strong> (for light themes)</p>
                <p class="text-gray-600 text-sm dark:text-gray-400 mb-3">Examples: <em>john.webp?color=5&palette=pale</em>, <em>sarah/female.webp?palette=default</em></p>
                <p class="text-gray-600 text-sm dark:text-gray-400 mb-3">For a random avatar:</p>
                <code class="block bg-white p-3 rounded-md text-xs dark:bg-neutral-900">
                    {{ env('APP_URL') }}/api/avatar.webp
                </code>
            </section>
            </div>

            <!-- Preview Avatars Section -->
            <section>
                @php
                // Keeping the existing color pattern
                $colorPattern = [
                    [7, 7, 7, 7, 10, 10, 11, 12, 12, 2],
                    [7, 7, 7, 10, 10, 11, 12, 12, 2, 3],
                    [7, 7, 10, 10, 11, 11, 12, 2, 3, 1],
                    [7, 10, 10, 11, 11, 12, 2, 3, 1, 4],
                    [10, 10, 11, 12, 12, 2, 3, 1, 4, 5],
                    [10, 11, 12, 12, 2, 3, 1, 4, 5, 6],
                    [11, 12, 12, 2, 3, 1, 4, 5, 6, 17],
                    [12, 12, 2, 3, 1, 4, 5, 6, 17, 12],
                    [12, 2, 3, 1, 4, 5, 6, 17, 12, 12],
                    [2, 3, 1, 4, 5, 6, 17, 12, 12, 12],
                ];
                @endphp

                <div class="grid-cols-5 md:grid-cols-10 gap-3 max-w-max mx-auto hidden md:grid">
                    @foreach($colorPattern as $rowIndex => $row)
                        @foreach($row as $colIndex => $colorIndex)
                            <div class="w-16 h-16  rounded-full bg-white overflow-hidden dark:bg-black">
                                <img src="{{ route('avatar.generate', ['name' => 'user' . ($rowIndex * 10 + $colIndex), 'color' => $colorIndex]) }}"
                                     class="w-full h-auto"
                                     loading="lazy"
                                     alt="Memoji Avatar">
                            </div>
                        @endforeach
                    @endforeach
                </div>


            </section>

            <!-- Palette Comparison Section -->
            <section>
                <h2 class="text-lg font-semibold mb-4">🎨 Color Palettes</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-md font-medium mb-3 text-center">Default Palette</h3>
                        <div class="grid grid-cols-6 gap-2 max-w-max mx-auto">
                            @for ($i = 0; $i < 12; $i++)
                                <div class="w-12 h-12 rounded-full bg-white overflow-hidden dark:bg-black">
                                    <img src="{{ route('avatar.generate', ['name' => 'demo' . $i, 'color' => $i, 'palette' => 'default']) }}"
                                         class="w-full h-auto"
                                         loading="lazy"
                                         alt="Default Palette Avatar">
                                </div>
                            @endfor
                        </div>
                    </div>
                    <div>
                        <h3 class="text-md font-medium mb-3 text-center">Pale Palette (Light Themes)</h3>
                        <div class="grid grid-cols-6 gap-2 max-w-max mx-auto">
                            @for ($i = 0; $i < 12; $i++)
                                <div class="w-12 h-12 rounded-full bg-white overflow-hidden dark:bg-black">
                                    <img src="{{ route('avatar.generate', ['name' => 'demo' . $i, 'color' => $i, 'palette' => 'pale']) }}"
                                         class="w-full h-auto"
                                         loading="lazy"
                                         alt="Pale Palette Avatar">
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </section>

            <!-- Example Usage Section -->
            <section class="bg-neutral-100 p-6 rounded-lg dark:bg-neutral-950">
                <h2 class="text-lg font-semibold mb-4">🔗 Example Usage</h2>
                <p class="text-gray-600 text-sm dark:text-gray-400 mb-3">🤖 <strong>Auto-detection</strong> (smart gender detection from names):</p>
                <code class="block bg-white p-3 rounded-md text-xs text-green-500 dark:bg-neutral-900 mb-3">
                    &lt;img src="{{ env('APP_URL') }}/api/avatar/john.webp" alt="Auto-detected Male Avatar"&gt;
                </code>
                <code class="block bg-white p-3 rounded-md text-xs text-green-500 dark:bg-neutral-900 mb-3">
                    &lt;img src="{{ env('APP_URL') }}/api/avatar/sarah.webp" alt="Auto-detected Female Avatar"&gt;
                </code>
                <p class="text-gray-600 text-sm dark:text-gray-400 mb-3">🎯 <strong>Manual override</strong> (specify gender explicitly):</p>
                <code class="block bg-white p-3 rounded-md text-xs text-green-500 dark:bg-neutral-900 mb-3">
                    &lt;img src="{{ env('APP_URL') }}/api/avatar/alex/male.webp?color=5" alt="Male Avatar Override"&gt;
                </code>
                <p class="text-gray-600 text-sm dark:text-gray-400 mb-3">🧪 <strong>Test professional gender detection:</strong></p>
                <code class="block bg-white p-3 rounded-md text-xs text-blue-500 dark:bg-neutral-900 mb-2">
                    GET {{ env('APP_URL') }}/api/detect-gender/andrea?country=US → {"detected_gender":"female"}
                </code>
                <code class="block bg-white p-3 rounded-md text-xs text-blue-500 dark:bg-neutral-900 mb-2">
                    GET {{ env('APP_URL') }}/api/detect-gender/andrea/detailed → Full analysis with confidence
                </code>
                <code class="block bg-white p-3 rounded-md text-xs text-blue-500 dark:bg-neutral-900">
                    GET {{ env('APP_URL') }}/api/detect-gender/robin/compare → Compare across 10 countries
                </code>
            </section>

            <!-- Features Section -->
            <section>
                <h2 class="text-lg font-semibold mb-4">Features</h2>
                <ul class="list-disc list-inside text-gray-600 text-sm dark:text-gray-400 space-y-2">
                    <li>🤖 <strong>Professional gender detection</strong> with 40,000+ names database</li>
                    <li>🌍 <strong>Country-specific detection</strong> (US, IT, FR, DE, etc.)</li>
                    <li>🎯 Manual gender override (male, female, random)</li>
                    <li>📊 <strong>Confidence levels</strong> (high, medium, low)</li>
                    <li>🧠 <strong>Smart detection</strong> with multi-country consensus</li>
                    <li>27 unique male avatars, 31 female avatars</li>
                    <li>18 customizable background colors</li>
                    <li>🔍 Advanced name parsing with international character support</li>
                    <li>Unique avatars based on input string</li>
                    <li>Consistent generation for the same input</li>
                    <li>100% backwards compatible</li>
                    <li>No authentication required</li>
                    <li>Fast response times</li>
                    <li>Suitable for various applications</li>
                </ul>
            </section>
        </main>

        <!-- Footer Section -->
        <footer class="border-t border-gray-200 dark:border-gray-800 pt-6 text-center text-gray-500 text-xs mt-12">
            <p>&copy; {{ date('Y') }} Memoji Avatar API. All rights reserved.</p>
        </footer>
    </div>

    @if (app()->environment('production'))
    <script data-goatcounter="https://tapback.goatcounter.com/count"
        async src="//gc.zgo.at/count.js"></script>
    @endif

</body>
</html>

