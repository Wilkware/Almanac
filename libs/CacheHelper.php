<?php

/**
 * CacheHelper.php
 *
 * Part of the Trait-Libraray for IP-Symcon Modules.
 *
 * @package       traits
 * @author        Heiko Wilknitz <heiko@wilkware.de>
 * @copyright     2025 Heiko Wilknitz
 * @link          https://wilkware.de
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

declare(strict_types=1);

/**
 * Helper class for the JSON cache handling.
 */
trait CacheHelper
{
    /**
     * Retrieves and decompresses content from the buffer.
     * Returns the given default content if the buffer is empty or invalid.
     *
     * @param string $name  The buffer name.
     * @param string $empty The default content to return if the buffer is empty or invalid.
     * @return string The decompressed content from the buffer.
     */
    protected function GetCache(string $name, string $empty = '{}'): string
    {
        // no name, no value
        if ($name === '') {
            $this->SendDebug(__FUNCTION__, "Invalid gzdecode() for buffer '{$name}'", 0);
            return $empty;
        }
        // try to extract data
        $encoded = $this->GetBuffer($name);
        $decoded = (!empty($encoded) && ($tmp = gzdecode($encoded)) !== false) ? $tmp : $empty;
        return $decoded;
    }

    /**
     * Stores compressed content in the buffer under the given name.
     *
     * @param string $name    The buffer name.
     * @param string $content The uncompressed content to store (e.g., JSON string).
     * @return void
     */
    protected function SetCache(string $name, string $content): void
    {
        if ($name === '') {
            return;
        }
        // Maximum compression (optional)
        $encoded = gzencode($content, 9);
        $this->SetBuffer($name, $encoded);
    }

    /**
     * Removes cached entries matching a given pattern.
     * If no pattern is provided, clears the entire cache.
     *
     * @param string $name    The buffer name.
     * @param string $pattern Associated pattern per cache item.
     * @return void
     */
    protected function ClearCache(string $name, string $pattern = ''): void
    {
        $cache = json_decode($this->GetCache($name), true);

        if (!is_array($cache)) {
            $cache = [];
        }

        if ($pattern === '') {
            $this->SendDebug(__FUNCTION__, $name . ' cache cleared!', 0);
            $this->SetCache($name, '{}');
            return;
        }

        foreach ($cache as $url => $entry) {
            if (strpos($url, $pattern) !== false) {
                unset($cache[$url]);
            }
        }

        $this->SetCache($name, json_encode($cache));
        $this->SendDebug(__FUNCTION__, 'Cache ' . $name . ' cleared for: ' . $pattern, 0);
    }

    /**
     * Returns an overview of the current cache content, including remaining lifetime per entry.
     *
     * @param string $name The buffer name.
     * @return list<array<string, string>> An array with cache information.
     */
    protected function GetCacheInfo(string $name): array
    {
        $cache = json_decode($this->GetCache('UrlCache'), true);

        if (!is_array($cache)) {
            return [];
        }

        $info = [];
        foreach ($cache as $url => $entry) {
            $timeout = $this->GetCacheTimeoutForUrl($url);
            $remaining = ($timeout === 0) ? '∞' : max(0, ($entry['timestamp'] + $timeout) - time());
            $info[] = [
                'Url'       => $url,
                'CachedAt'  => date('Y-m-d H:i:s', $entry['timestamp']),
                'Remaining' => $remaining === '∞' ? '∞' : round($remaining / 60) . ' min'
            ];
        }

        return $info;
    }
}