<?php

namespace craft\feedme\models;

use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $pluginName = 'Feed Me';

    /**
     * @var int
     */
    public $cache = 60;

    /**
     * @var string
     */
    public $enabledTabs = '*';

    /**
     * @var array
     */
    public $clientOptions = [];

    /**
     * @var \string[][]
     */
    public $requestOptions = [
        'headers' => [
            'User-Agent' => 'Feed Me',
        ],
    ];

    /**
     * @var bool
     */
    public $compareContent = true;

    /**
     * @var string
     */
    public $skipUpdateFieldHandle = '';

    /**
     * @var int
     */
    public $backupLimit = 100;

    /**
     * @var string
     */
    public $dataDelimiter = '-|-';

    /**
     * @var string
     */
    public $csvColumnDelimiter = ',';

    /**
     * @var array|bool No longer has any effect: feed data is never rendered as Twig. Kept so existing config files don't error.
     */
    public $parseTwig = [];

    /**
     * @var array
     */
    public $feedOptions = [];

    /**
     * @var int
     */
    public $sleepTime = 0;

    /**
     * @var bool
     */
    public $logging = true;

    /**
     * @var bool
     */
    public $runGcBeforeFeed = false;

    /**
     * @var
     */
    public $queueTtr;

    /**
     * @var
     */
    public $queueMaxRetry;

    /**
     * @var bool
     */
    public $assetDownloadCurl = false;

    /**
     * Hostnames that are allowed to resolve to private IP addresses, e.g. `'feeds.internal'` or `'*.ddev.site'`.
     * A leading `*.` wildcard matches any subdomain. Can only be set in `config/feed-me.php`.
     *
     * @var string[]
     * @since 4.9.2
     */
    public $allowedPrivateHosts = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();
        // allowedPrivateHosts, clientOptions, requestOptions, feedOptions can't be set from the UI so no point in storing them in the project config
        unset($fields['allowedPrivateHosts'], $fields['clientOptions'], $fields['requestOptions'], $fields['feedOptions']);

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return array_merge(parent::extraFields(), [
            'allowedPrivateHosts',
            'clientOptions',
            'requestOptions',
            'feedOptions',
        ]);
    }
}
