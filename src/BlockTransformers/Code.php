<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\blocktransformers;

use craft\elements\Entry;
use craft\helpers\Html;
use craft\wpimport\BaseBlockTransformer;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Code extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/code';
    }

    public function render(array $data, Entry $entry): string
    {
        // `<pre class="wp-block-code"><code>` → `<pre><code>`
        $node = (new Crawler($data['innerHTML']))->filter('code');
        if (!$node->count()) {
            return '';
        }
        $code = $node->html();
        return Html::beginTag('pre') .
            Html::tag('code', $code, [
                'class' => sprintf('language-%s', $this->detectLanguage($code)),
            ]) .
            Html::endTag('pre');
    }

    private function detectLanguage(string $code): string
    {
        $code = trim(Html::decode($code));
        if (preg_match('/^\s*<\w+/m', $code)) {
            return 'html';
        }
        if (preg_match('/^\s*(var|let|const)\b/m', $code)) {
            return 'javascript';
        }
        if (preg_match('/^\s*(\.|#)\b/m', $code)) {
            return 'css';
        }
        if (str_contains($code, '<?')) {
            return 'php';
        }
        return 'plaintext';
    }
}
