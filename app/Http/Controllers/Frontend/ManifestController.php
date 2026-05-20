<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;


class ManifestController extends Controller
{
    public function show(): \Illuminate\Http\JsonResponse
    {
        $config = config('laravelpwa.manifest');

        $i = 0;
        $icons = [];
        foreach ($config['icons'] as $size => $icon) {
            $icons[$i] = [
                'src' => $icon['path'],
                'sizes' => $size,
                'type' => 'image/png',
                'purpose' => $icon['purpose'] ?? 'any',
            ];
            $i++;
        }

        $i= 0;
        $shortcuts = [];
        if (isset($config['shortcuts'])) {
            foreach ($config['shortcuts'] as $shortcut) {
                $shortcutItem = [
                    'name' => $shortcut['name'],
                    'description' => $shortcut['description'],
                    'url' => $shortcut['url'],
                ];
                if (isset($shortcut['icons'])) {
                    $shortcutItem['icons'] = [
                        [
                            'src' => $shortcut['icons']['src'] ?? '',
                            'purpose' => $shortcut['icons']['purpose'] ?? 'any',
                            'type' => 'image/png',
                        ]
                    ];
                }
                $shortcuts[$i] = $shortcutItem;
                $i++;
            }
        }

        return response()->json([
            'name' => $config['name'],
            'short_name' => $config['short_name'],
            'start_url' => $config['start_url'],
            'display' => $config['display'],
            'background_color' => $config['background_color'],
            'theme_color' => $config['theme_color'],
            'orientation' => $config['orientation'],
            'status_bar' => $config['status_bar'],
            'icons' => $icons,
            'shortcuts' => $shortcuts,
        ], 200, [
            'Content-Type' => 'application/manifest+json',
        ]);
    }
}