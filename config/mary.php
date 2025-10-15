<?php

return [
    /**
     * Default component prefix.
     *
     * Make sure to clear view cache after renaming with `php artisan view:clear`
     *
     *    prefix => ''
     *              <x-button />
     *              <x-card />
     *
     *    prefix => 'mary-'
     *               <x-mary-button />
     *               <x-mary-card />
     *
     */
    'prefix' => '',

    /**
     * Default route prefix.
     *
     * Some maryUI components make network request to its internal routes.
     *
     *      route_prefix => ''
     *          - Spotlight: '/mary/spotlight'
     *          - Editor: '/mary/upload'
     *          - ...
     *
     *      route_prefix => 'my-components'
     *          - Spotlight: '/my-components/mary/spotlight'
     *          - Editor: '/my-components/mary/upload'
     *          - ...
     */
    'route_prefix' => '',

    /**
     * Icon settings
     */
    'icons' => [
        /**
         * Icon package name
         *
         * The full Composer package name of the icon package you want to use.
         * This should be in vendor/package format.
         *
         * Check https://github.com/driesvints/blade-icons for a list of available packages.
         * 
         * Example:
         *   - 'blade-ui-kit/blade-heroicons' for Heroicons (default)
         */
        'package' => 'blade-ui-kit/blade-heroicons',

        /**
         * Icon prefix
         *
         * The prefix used by your chosen icon package.
         * This will be prepended to icon names when rendering.
         * 
         * Check the documentation of your chosen icon package for the prefix.
         * 
         * Example:
         *   - 'heroicon' for Heroicons (default)
         */
        'prefix' => 'heroicon',

        /**
         * Common icon mappings
         *
         * These are commonly used icons throughout MaryUI components.
         * You can override these when using different icon packages.
         *
         * Note: These should be the icon names WITHOUT the prefix.
         * The prefix will be automatically added based on your icon package.
         */
        'common' => [
            // Close/dismiss/clear actions
            'close' => 'o-x-mark',

            // Navigation and separators
            'chevron-right' => 'o-chevron-right',
            'chevron-down' => 'o-chevron-down',
            'chevron-left' => 'o-chevron-left',
            'chevron-up' => 'o-chevron-up',
            'chevron-up-down' => 'o-chevron-up-down',

            // Password visibility toggle
            'eye' => 'o-eye',
            'eye-slash' => 'o-eye-slash',

            // Theme toggle
            'sun' => 'o-sun',
            'moon' => 'o-moon',

            // Actions and controls
            'x-circle' => 'o-x-circle',
            'scissors' => 'o-scissors',
            'plus-circle' => 'o-plus-circle',
            'backspace' => 'o-backspace',
            'bars-3-bottom-right' => 'o-bars-3-bottom-right',
        ],
    ],

    /**
     * Components settings
     */
    'components' => [
        'spotlight' => [
            'class' => 'App\Support\Spotlight',
        ]
    ]
];
