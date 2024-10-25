<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\wpimport;

use Craft;
use craft\helpers\Console;
use Throwable;
use yii\base\Action;
use yii\console\ExitCode;

/**
 * @property Command $controller
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class ImportAction extends Action
{
    public string $resource;

    /**
     * Clears the caches.
     *
     * @return int
     */
    public function run(): int
    {
        if (!$this->controller->isSupported($this->resource, $reason)) {
            $this->controller->stderr(sprintf(
                "Importing %s isn’t supported%s\n",
                $this->resource,
                $reason ? ": $reason" : '.',
            ), Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($this->controller->dryRun) {
            $transaction = Craft::$app->db->beginTransaction();
        }

        try {
            $this->controller->do("Importing $this->resource", function() {
                Console::indent();
                try {
                    $items = $this->controller->items($this->resource, [
                        'include' => implode(',', $this->controller->itemId),
                    ]);
                    foreach ($items as $data) {
                        try {
                            $this->controller->import($this->resource, $data);
                        } catch (Throwable $e) {
                            if ($this->controller->failFast) {
                                throw $e;
                            }
                        }
                    }
                } finally {
                    Console::outdent();
                }
            });
        } catch (Throwable) {
            return ExitCode::UNSPECIFIED_ERROR;
        } finally {
            if (isset($transaction)) {
                $transaction->rollBack();
            }

            $this->controller->outputSummary();
        }

        return ExitCode::OK;
    }
}
