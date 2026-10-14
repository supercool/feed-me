<?php

namespace craft\feedme\controllers;

use craft\feedme\Plugin;
use craft\web\Controller;

class BaseController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @return \yii\web\Response
     */
    public function actionSettings()
    {
        $this->requireAdmin(false);

        $settings = Plugin::$plugin->getSettings();

        return $this->renderTemplate('feed-me/settings/general', [
            'settings' => $settings,
        ]);
    }
}
