<?php

namespace craft\feedme\helpers;

use Craft;
use craft\feedme\helpers\urlvalidator\UrlValidator;
use craft\feedme\Plugin;
use craft\fields\data\ColorData;
use craft\helpers\FileHelper;
use craft\validators\ColorValidator;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

class BaseHelper
{
    // Public Methods
    // =========================================================================

    /**
     * @param $value
     * @return bool|mixed|void
     */
    public static function parseBoolean($value)
    {
        $result = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        // Additional checks
        if (is_array($value)) {
            return;
        }

        // Also check for translated values of boolean-like terms
        if (strtolower($value) === Craft::t('feed-me', 'yes')) {
            $result = true;
        }

        if (strtolower($value) === Craft::t('feed-me', 'on')) {
            $result = true;
        }

        if (strtolower($value) === Craft::t('feed-me', 'open')) {
            $result = true;
        }

        if (strtolower($value) === Craft::t('feed-me', 'enabled')) {
            $result = true;
        }

        if (strtolower($value) === Craft::t('feed-me', 'live')) {
            $result = true;
        }

        if (strtolower($value) === Craft::t('feed-me', 'active')) {
            $result = true;
        }

        if (strtolower($value) === Craft::t('feed-me', 'y')) {
            $result = true;
        }


        if (strtolower($value) === Craft::t('feed-me', 'no')) {
            $result = false;
        }

        if (strtolower($value) === Craft::t('feed-me', 'off')) {
            $result = false;
        }

        if (strtolower($value) === Craft::t('feed-me', 'closed')) {
            $result = false;
        }

        if (strtolower($value) === Craft::t('feed-me', 'disabled')) {
            $result = false;
        }

        if (strtolower($value) === Craft::t('feed-me', 'inactive')) {
            $result = false;
        }

        if (strtolower($value) === Craft::t('feed-me', 'n')) {
            $result = false;
        }

        return $result;
    }

    /**
     * @param $value
     * @return ColorData|string|null
     */
    public static function parseColor($value)
    {
        if ($value instanceof ColorData) {
            return $value;
        }

        if (!$value || $value === '#') {
            return null;
        }

        $value = strtolower($value);

        if ($value[0] !== '#') {
            $value = '#' . $value;
        }

        if (strlen($value) === 4) {
            $value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
        }

        return ColorValidator::normalizeColor($value);
    }

    /**
     * @param $userAgent
     * @return string
     */
    public static function getBrowserName($userAgent)
    {
        if (strpos($userAgent, 'Opera') || strpos($userAgent, 'OPR/')) {
            return 'Opera';
        }

        if (strpos($userAgent, 'Edge')) {
            return 'Edge';
        }

        if (strpos($userAgent, 'Chrome')) {
            return 'Chrome';
        }

        if (strpos($userAgent, 'Safari')) {
            return 'Safari';
        }

        if (strpos($userAgent, 'Firefox')) {
            return 'Firefox';
        }

        if (strpos($userAgent, 'MSIE') || strpos($userAgent, 'Trident/7')) {
            return 'Internet Explorer';
        }

        return 'Other';
    }

    /**
     * Checks if the path can be considered local based on its scheme (or lack of).
     *
     * @param string $path
     * @return bool
     */
    public static function isLocalPath(string $path): bool
    {
        $scheme = parse_url($path, PHP_URL_SCHEME);

        return $scheme === null || $scheme === false || strlen($scheme) === 1 || strtolower($scheme) === 'file';
    }

    /**
     * Returns whether a local file can be read as a feed or asset source.
     *
     * Files whose names start with a dot, and files within Craft’s system directories, the filesystem root
     * or sensitive OS directories are not allowed.
     *
     * @param string $filepath The real path of the file (as returned by `realpath()`)
     * @return bool
     */
    public static function isFilepathAllowed(string $filepath): bool
    {
        // disallow if the filename starts with a dot
        $basename = basename($filepath);
        if (strpos($basename, '.') === 0) {
            return false;
        }

        // disallow if the $filepath is within one of the restricted directories
        if (self::isRestrictedDir($filepath)) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether the given path is located in what's considered a sensitive/restricted directory.
     *
     * Port of `craft\services\Security::isRestrictedDir()` from Craft 5.10.9+, which isn’t available in Craft 3.
     *
     * @param string $path
     * @return bool
     */
    public static function isRestrictedDir(string $path): bool
    {
        $path = self::normalizeAbsolutePath($path);

        // is it located within a Craft-specific system directory
        foreach (self::getSystemPaths() as $dir) {
            if (!$dir) {
                continue;
            }

            $dir = self::normalizeAbsolutePath(realpath($dir) ?: $dir);
            if (strpos("$path/", "$dir/") === 0 || strpos("$dir/", "$path/") === 0) {
                return true;
            }
        }

        // is it located within a sensitive os directory
        if (PHP_OS_FAMILY === 'Windows') {
            // is it located directly in the filesystem's root (e.g. C:\myfile.json, \\server\sharename\myfile.json)
            // we're using forward slashes cause the path was already normalized
            if (preg_match('#^(?:[A-Za-z]:/[^/]+|//[^/]+/[^/]+/[^/]+)$#', $path)) {
                return true;
            }

            $winRoot = FileHelper::normalizePath($_SERVER['SystemRoot'] ?? 'C:\\Windows', '/');
            $drive = substr($winRoot, 0, 3); // e.g. "C:/" because we've normalized it
            $sensitiveDirs = [
                $winRoot, // C:/Windows
                $drive . 'Program Files',
                $drive . 'Program Files (x86)',
                $drive . 'ProgramData',
            ];
            // Windows paths are case-insensitive
            $path = strtolower($path);
            $sensitiveDirs = array_map('strtolower', $sensitiveDirs);
        } else {
            // is it located directly in the filesystem's root (e.g. /myfile.json)
            $parent = dirname($path);
            if ($parent === dirname($parent)) {
                return true;
            }

            // non-windows-based sensitive directories
            $sensitiveDirs = [
                '/boot',
                '/dev',
                '/etc',
                '/proc',
                '/root',
                '/sys',
            ];
        }

        foreach ($sensitiveDirs as $dir) {
            if ($path === $dir || strpos("$path/", "$dir/") === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the URL validator configured for Feed Me.
     *
     * @return UrlValidator
     */
    public static function urlValidator(): UrlValidator
    {
        return new UrlValidator(null, [
            // only the hosts listed in config/feed-me.php can resolve to private IP addresses
            'allowedPrivateHosts' => (array)(Plugin::$plugin->getSettings()->allowedPrivateHosts ?? []),
        ]);
    }

    /**
     * Resolves a redirect location (which can be relative) against the URL that returned it.
     *
     * @param string $baseUrl
     * @param string $location
     * @return string
     */
    public static function resolveRedirectUrl(string $baseUrl, string $location): string
    {
        return (string)UriResolver::resolve(new Uri($baseUrl), new Uri($location));
    }

    /**
     * Returns whether a URL uses the http or https scheme.
     *
     * @param mixed $url
     * @return bool
     */
    public static function isHttpUrl($url): bool
    {
        if (!is_string($url) || $url === '') {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true);
    }

    /**
     * Normalizes a path to use forward slashes, and makes it absolute (relative to the current working directory).
     *
     * @param string $path
     * @return string
     */
    private static function normalizeAbsolutePath(string $path): string
    {
        $path = FileHelper::normalizePath($path, '/');

        if (strpos($path, '/') !== 0 && !preg_match('/^[A-Za-z]:\//', $path)) {
            $path = FileHelper::normalizePath(getcwd() . '/' . $path, '/');
        }

        return rtrim($path, '/');
    }

    /**
     * Returns Craft’s system directories.
     *
     * @return string[]
     */
    private static function getSystemPaths(): array
    {
        $pathService = Craft::$app->getPath();

        // Path::getSystemPaths() was added in Craft 3.7.17
        if (method_exists($pathService, 'getSystemPaths')) {
            return $pathService->getSystemPaths();
        }

        return [
            Craft::getAlias('@contentMigrations'),
            Craft::getAlias('@lib'),
            $pathService->getConfigPath(),
            $pathService->getStoragePath(),
            $pathService->getSiteTemplatesPath(),
            $pathService->getSiteTranslationsPath(),
            $pathService->getVendorPath(),
        ];
    }
}
