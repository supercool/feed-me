<?php

namespace craft\feedme\base;

use Cake\Utility\Hash;
use Craft;
use craft\base\Batchable;
use craft\base\Component;
use craft\feedme\Plugin;
use craft\helpers\UrlHelper;

/**
 *
 * @property-read mixed $name
 * @property-read mixed $class
 */
abstract class DataType extends Component implements Batchable
{
    // Properties
    // =========================================================================

    /**
     * @var array
     */
    protected array $feedData = [];

    // Public
    // =========================================================================

    /**
     * @return mixed
     */
    public function getName(): string
    {
        /** @phpstan-ignore-next-line */
        return static::$name;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return get_class($this);
    }

    /**
     * @param $array
     * @param $feed
     */
    public function setupPaginationUrl($array, $feed): void
    {
        if (!$feed->paginationNode) {
            return;
        }

        // Find the URL value in the feed
        $flatten = Hash::flatten($array, '/');
        $url = Hash::get($flatten, $feed->paginationNode);

        // resolve any aliases in the pagination URL
        $url = Craft::getAlias($url);

        // if the feed provides a root relative URL, make it whole again based on the feed.
        if ($url && UrlHelper::isRootRelativeUrl($url)) {
            $url = UrlHelper::hostInfo($feed->feedUrl) . $url;
        }

        // Pagination URLs come from the feed content, so only ever follow http(s) URLs
        if ($url && !self::isHttpUrl($url)) {
            Plugin::error('Ignoring pagination URL `{url}` as only http and https URLs are allowed.', ['url' => is_scalar($url) ? $url : gettype($url)]);
            $url = null;
        }

        // Replace the mapping value with the actual URL
        $feed->paginationUrl = $url ?: null;
    }

    /**
     * Returns whether the given value is an absolute http or https URL.
     *
     * @param mixed $url
     * @return bool
     */
    public static function isHttpUrl(mixed $url): bool
    {
        if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true);
    }

    /**
     * @inheritdoc
     */
    public function getSlice(int $offset, int $limit): iterable
    {
        $feedData = $this->feedData;

        if ($offset) {
            $feedData = array_slice($feedData, $offset);
        }

        if ($limit) {
            $feedData = array_slice($feedData, 0, $limit);
        }

        return $feedData;
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->feedData);
    }
}
