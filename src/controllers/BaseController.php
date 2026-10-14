<?php

namespace craft\feedme\controllers;

use Craft;
use craft\feedme\Plugin;
use craft\web\Controller;
use yii\db\Exception;
use yii\web\Response;

class BaseController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @return Response
     */
    public function actionSettings(): Response
    {
        $this->requireAdmin();

        $settings = Plugin::$plugin->getSettings();

        return $this->renderTemplate('feed-me/settings/general', [
            'settings' => $settings,
        ]);
    }

    /**
     * @return Response
     * @throws Exception
     */
    public function actionClearTasks(): Response
    {
        // Deleting the whole queue is destructive, so restrict it to admins and POST requests
        $this->requirePostRequest();
        $this->requireAdmin(false);

        // Function to clear (delete) all stuck tasks.
        Craft::$app->getDb()->createCommand()
            ->delete('{{%queue}}')
            ->execute();

        return $this->redirect('feed-me/utilities');
    }
}
