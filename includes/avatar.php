<?php
/**
 * SAKMS - Avatar Helper
 * Single source of truth for staff profile picture URLs.
 */

if (!function_exists('buildAvatarUrl')) {
    /**
     * Return the local asset URL for a staff member.
     * Matches on the first word of the name (case-insensitive).
     *
     * @param string $name  Full name or first name of the staff member.
     * @return string       Absolute web URL to the profile image.
     */
    function buildAvatarUrl(string $name): string {
        // Web-root base path — update here if the server path changes
        $base = '/sales_assistant_KPI_monitoring_system/asset/avatars/';

        // Map first names → image filenames
        static $avatars = null;
        if ($avatars === null) {
            $avatars = [
                'emily'  => 'emily.png',
                'aisyah' => 'aisyah.jpg',
                'farah'  => 'farah.jpg',
                'lisa'   => 'lisa.jpg',
                'susan'  => 'susan.jpeg',
                'sally'  => 'sally.jpg',
                'adam'   => 'adam.jpg',
                'alex'   => 'alex.jpg',
                'ali'    => 'ali.jpg',
                'daniel' => 'daniel.jpg',
                'john'   => 'john.jpg',
                'kamal'  => 'kamal.jpg',
                'kelvin' => 'kelvin.jpg',
                'marcus' => 'marcus.jpg',
            ];
        }

        // Extract and normalise the first word of the name for lookup
        $first = strtolower(trim(explode(' ', trim($name))[0]));

        $file = $avatars[$first] ?? 'default.jpg';
        return $base . $file;
    }
}

if (!function_exists('buildAvatarMap')) {
    /**
     * Build a name → URL map for an array of full names.
     * Useful for passing the full avatar lookup to JavaScript as JSON.
     *
     * @param string[] $names  Array of staff full names.
     * @return array<string,string>  ['Full Name' => 'url', ...]
     */
    function buildAvatarMap(array $names): array {
        $map = [];
        foreach ($names as $name) {
            $map[$name] = buildAvatarUrl($name);
        }
        return $map;
    }
}
