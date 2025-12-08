<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WebManifestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $brandName = brand_name();
        $shortName = strtoupper((string) preg_replace('/[^a-z0-9]/i', '', substr($brandName, 0, 10))) ?: 'CRM';

        return response()->json([
            'name' => $brandName,
            'short_name' => $shortName,
            'icons' => [
                [
                    'src' => '/web-app-manifest-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
                [
                    'src' => '/web-app-manifest-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
            'theme_color' => '#ffffff',
            'background_color' => '#ffffff',
            'display' => 'standalone',
        ], 200, [
            'Content-Type' => 'application/manifest+json',
        ]);
    }
}

