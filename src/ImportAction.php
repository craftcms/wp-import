<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\wpimport;

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

        if ($this->controller->prep) {
            $this->controller->prep();
        }

        if (!empty($this->controller->itemId)) {
            foreach ($this->controller->itemId as $id) {
                $this->controller->import($this->resource, $id);
            }
        } else {
            $this->controller->do("Importing $this->resource", function() {
                Console::indent();
                try {
                    foreach ($this->controller->items($this->resource) as $data) {
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
        }

        return ExitCode::OK;
    }
}
