<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Console;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Console\PromptTask;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElement;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Heading;
use CraftCms\Cms\FieldLayout\LayoutElements\Markdown;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Plugins;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\UrlValidator\GuzzleMiddleware;
use CraftCms\UrlValidator\UrlValidationException;
use CraftCms\UrlValidator\UrlValidator as CraftUrlValidator;
use CraftCms\WpImport\AcfAdapters\BaseAcfAdapter;
use CraftCms\WpImport\BlockTransformers\BaseBlockTransformer;
use CraftCms\WpImport\Events\AcfAdapterTypesResolving;
use CraftCms\WpImport\Events\BlockTransformerTypesResolving;
use CraftCms\WpImport\Events\ImporterTypesResolving;
use CraftCms\WpImport\Exceptions\ImportException;
use CraftCms\WpImport\Exceptions\ReportableExceptionInterface;
use CraftCms\WpImport\Exceptions\UnknownAcfFieldTypeException;
use CraftCms\WpImport\Exceptions\UnknownBlockTypeException;
use CraftCms\WpImport\Generators\EntryTypes\Media as MediaEntryType;
use CraftCms\WpImport\Generators\Fields\Media as MediaField;
use CraftCms\WpImport\Generators\Fields\PostContent;
use CraftCms\WpImport\Generators\Fields\RichCaption;
use CraftCms\WpImport\Generators\Fields\WpId;
use CraftCms\WpImport\Importers\BaseConfigurableImporter;
use CraftCms\WpImport\Importers\BaseImporter;
use CraftCms\WpImport\Importers\Media;
use CraftCms\WpImport\Importers\PostType;
use CraftCms\WpImport\Importers\Taxonomy;
use CraftCms\WpImport\Importers\User as UserImporter;
use DOMElement;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use Illuminate\Console\Attributes\Aliases;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Prompts\Support\Logger;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

/**
 * Imports content from WordPress.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
#[Aliases(['wp-import/import'])]
#[Description('Imports content from WordPress.')]
#[Signature('wp-import
    {--api-url= : The base API URL (e.g. `https://example.com/wp-json/wp/v2/`)}
    {--username= : An administrator’s username}
    {--password= : An application password for the user defined by `--username`}
    {--type=* : The content types to import (`post`, `page`, `media`, `category`, `tag`, `user`, or a custom post type/taxonomy’s slug)}
    {--item-id=* : The item ID(s) to import}
    {--page= : The page to import from the API}
    {--per-page=100 : The number of items to fetch in each page}
    {--disk= : The filesystem disk the “WordPress Content” volume should use, if it doesn’t exist yet (defaults to `public`)}
    {--update : Reimport items that have already been imported}
    {--dry-run : Treat this as a dry run only}
    {--fail-fast : Abort the import on the first error encountered}
    {--no-summary : Don’t show an error summary at the end of the import}
    {--allowed-private-hosts=* : Hostnames that are allowed to resolve to private IP addresses, for WordPress sites or media hosted on an internal network (e.g. `my-wordpress-project.ddev.site`). A leading `*.` wildcard matches any subdomain.}')]
class ImportCommand extends Command
{
    use CraftCommand;

    /**
     * @var string|null The base API URL (e.g. `https://example.com/wp-json/wp/v2/`)
     */
    public ?string $apiUrl = null;

    /**
     * @var string|null An administrator’s username
     */
    public ?string $username = null;

    /**
     * @var string|null An application password for the user defined by `username`
     */
    public ?string $password = null;

    /**
     * @var string[] The content types to import (`post`, `page`, `media`,
     *               `category`, `tag`, `user`, or a custom post type/taxonomy’s slug)
     */
    public array $type = [];

    /**
     * @var int[] The item ID(s) to import
     */
    public array $itemId = [];

    /**
     * @var int|null The page to import from the API
     */
    public ?int $page = null;

    /**
     * @var int The number of items to fetch in each page
     */
    public int $perPage = 100;

    /**
     * @var string The filesystem disk the “WordPress Content” volume should use, if it doesn’t exist yet
     */
    public string $disk = 'public';

    /**
     * @var bool Reimport items that have already been imported
     */
    public bool $update = false;

    /**
     * @var bool Treat this as a dry run only
     */
    public bool $dryRun = false;

    /**
     * @var bool Abort the import on the first error encountered
     */
    public bool $failFast = false;

    /**
     * @var bool Show an error summary at the end of the import
     */
    public bool $showSummary = true;

    /**
     * @var string[] Hostnames that are allowed to resolve to private IP addresses
     */
    public array $allowedPrivateHosts = [];

    /**
     * @var array<string,BaseImporter>
     */
    public array $importers;

    /**
     * @var array<string,BaseBlockTransformer>
     */
    private array $blockTransformers;

    /**
     * @var array<string,BaseAcfAdapter>
     */
    private array $acfAdapters;

    public Client $client;

    public array $wpInfo;

    private array $idMap = [];

    private int $importTotal = 0;

    /**
     * @var array<string,bool>
     */
    public array $unknownBlockTypes = [];

    /**
     * @var array<string,bool>
     */
    public array $unknownAcfFieldTypes = [];

    /**
     * @var Throwable[]
     */
    public array $errors = [];

    /**
     * @var Logger|null The logger for the task that’s currently running
     */
    private ?Logger $logger = null;

    /**
     * Imports items from WordPress.
     */
    public function handle(): int
    {
        // Let generators and other components reach the running command
        app()->instance(self::class, $this);

        $this->apiUrl = $this->option('api-url');
        $this->username = $this->option('username');
        $this->password = $this->option('password');
        $this->type = $this->listOption('type');
        $this->itemId = array_map('intval', $this->listOption('item-id'));
        $this->page = $this->option('page') !== null ? (int) $this->option('page') : null;
        $this->perPage = (int) $this->option('per-page');
        $this->disk = $this->option('disk') ?? $this->disk;
        $this->update = (bool) $this->option('update');
        $this->dryRun = (bool) $this->option('dry-run');
        $this->failFast = (bool) $this->option('fail-fast');
        $this->showSummary = ! $this->option('no-summary');
        $this->allowedPrivateHosts = $this->listOption('allowed-private-hosts');

        if (! $this->systemCheck()) {
            return self::SUCCESS;
        }

        $this->client = $this->createGuzzleClient([
            RequestOptions::VERIFY => false,
        ]);

        try {
            $urlValidator = new CraftUrlValidator(options: [
                'allowedPrivateHosts' => $this->allowedPrivateHosts,
            ]);
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException("Invalid --allowed-private-hosts value: {$e->getMessage()}");
        }

        // validate every request URL (including redirects), and pin its hostname/port to the validated IPs
        if (! GuzzleMiddleware::attach($this->client, $urlValidator)) {
            throw new RuntimeException('Request URLs can’t be validated, because the Guzzle client uses a custom handler.');
        }

        $this->captureApiInfo();
        $this->loadImporters();
        $this->editionCheck();
        $this->loadBlockTransformers();
        $this->loadAcfAdapters();

        if (! empty($this->type)) {
            $slugs = $this->type;
        } else {
            // Use this specific order so we don't need to do as many one-off item imports
            $slugs = [
                UserImporter::SLUG,
                Media::SLUG,
            ];
            // Add in the rest
            foreach ($this->importers as $importer) {
                $slug = $importer->slug();
                if (! in_array($slug, $slugs)) {
                    $slugs[] = $slug;
                }
            }
        }

        $slugs = array_filter($slugs, fn (string $slug) => $this->isSupported($slug));
        $totals = [];

        if ($this->input->isInteractive()) {
            $this->do('Fetching info', function () use ($slugs, &$totals) {
                foreach ($slugs as $slug) {
                    $importer = $this->importers[$slug];
                    $label = $importer->label();
                    if ($importer instanceof BaseConfigurableImporter) {
                        $label = sprintf('%s (%s)', $label, $importer->typeLabel());
                    }

                    $totals[] = [
                        $label,
                        $importer->slug(),
                        number_format($this->totalItems($slug)),
                    ];
                }
            });

            table(['Content Type', 'Slug', 'Total Items'], array_values(Arr::sort($totals, fn (array $item) => $item[0])));

            if (! confirm('Continue with the import?', true)) {
                info('Aborting.');

                return self::SUCCESS;
            }
        }

        if ($this->dryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($slugs as $slug) {
                $label = mb_strtolower($this->importers[$slug]->label());
                $this->do("Importing $label", function () use ($slug) {
                    $items = $this->items($slug, [
                        'include' => implode(',', $this->itemId),
                    ]);
                    foreach ($items as $data) {
                        try {
                            $this->import($slug, $data);
                        } catch (Throwable $e) {
                            if ($this->failFast) {
                                throw $e;
                            }
                        }
                    }
                });
            }
        } finally {
            if ($this->dryRun) {
                DB::rollBack();
                // prevent project config changes from persisting
                ProjectConfig::reset();
            }
        }

        $this->outputSummary();

        return self::SUCCESS;
    }

    /**
     * Runs an action as a task, or logs it within the task that’s already running.
     */
    public function do(string $description, callable $action): void
    {
        if ($this->logger) {
            $this->logger->line($description);
            try {
                $action();
            } catch (Throwable $e) {
                $this->logger->error(sprintf('%s: %s', $description, $e->getMessage()));
                throw $e;
            }

            return;
        }

        PromptTask::run($description, function (Logger $logger) use ($action) {
            $this->logger = $logger;
            try {
                $action();
            } finally {
                $this->logger = null;
            }
        }, output: $this->output);
    }

    /**
     * Outputs a warning, within the running task if there is one.
     */
    #[\Override]
    public function warn($string, $verbosity = null): void
    {
        if ($this->logger) {
            $this->logger->warning($string);

            return;
        }

        warning($string);
    }

    /**
     * Returns an array option’s values, splitting any comma-separated values.
     *
     * @return string[]
     */
    private function listOption(string $name): array
    {
        return Collection::make((array) $this->option($name))
            ->flatMap(fn (string $value) => explode(',', $value))
            ->map(fn (string $value) => trim($value))
            ->filter(fn (string $value) => $value !== '')
            ->values()
            ->all();
    }

    private function createGuzzleClient(array $config): Client
    {
        $guzzleConfig = Arr::merge([
            'headers' => [
                'User-Agent' => sprintf('Craft/%s %s', Cms::version(), Utils::defaultUserAgent()),
            ],
        ], config('craft.guzzle', []), $config);

        if ($proxy = Cms::config()->httpProxy) {
            $guzzleConfig['proxy'] = $proxy;
        }

        return new Client($guzzleConfig);
    }

    public function renderBlocks(array $blocks, Entry $entry): string
    {
        $html = '';
        $firstError = null;
        foreach ($blocks as $block) {
            try {
                $html .= $this->renderBlock($block, $entry);
            } catch (UnknownBlockTypeException $e) {
                // capture the block type and then keep going, so we can capture
                // any other unknown block types in here
                $this->unknownBlockTypes[$e->blockType] = true;
                $this->errors[] = $e;
                $firstError ??= $e;
            }
        }
        if ($firstError && $this->failFast) {
            throw $firstError;
        }

        return $html;
    }

    public function renderBlock(array $block, Entry $entry): string
    {
        if (empty($block['blockName'])) {
            // this post uses the classic editor
            return $this->renderClassicEditorHtml($block['innerHTML'], $entry);
        }

        if (! isset($this->blockTransformers[$block['blockName']])) {
            throw new UnknownBlockTypeException($block['blockName'], $block);
        }

        $html = $this->blockTransformers[$block['blockName']]->render($block, $entry);

        return trim($html)."\n";
    }

    private function renderClassicEditorHtml(string $html, Entry $entry): string
    {
        $crawler = new Crawler("<html><body>$html</body></html>");
        $body = $crawler->filter('body');

        // replace <img> tags with nested Media entries
        $body->filter('img')->each(function (Crawler $img) use ($entry) {
            foreach (Html::explodeClass($img->attr('class')) as $class) {
                if (preg_match('/^wp-image-(\d+)$/', $class, $match)) {
                    try {
                        $assetId = $this->import(Media::SLUG, (int) $match[1]);
                    } catch (Throwable) {
                        break;
                    }

                    /** @var DOMElement $imgNode */
                    $imgNode = $img->getNode(0);
                    $entryHtml = $this->createNestedMediaEntry($entry, $assetId);
                    $entryNode = $imgNode->ownerDocument->importNode(
                        (new Crawler($entryHtml))->filter('craft-entry')->getNode(0),
                    );
                    $imgNode->replaceWith($entryNode);
                }
            }
        });

        return $body->html();
    }

    /**
     * Creates a nested entry.
     *
     * @return string The `<craft-entry>` tag to be returned by `render()`
     */
    public function createNestedEntry(Entry $entry, callable $populate): string
    {
        $nestedEntry = new Entry;
        $nestedEntry->fieldId = PostContent::get()->id;
        $nestedEntry->ownerId = $entry->id;
        $populate($nestedEntry);
        $this->saveNestedEntry($nestedEntry);

        return sprintf('<craft-entry data-entry-id="%s">&nbsp;</craft-entry>', $nestedEntry->id);
    }

    /**
     * Creates a nested “Media” entry.
     *
     * @param  int|int[]  $assetIds
     */
    public function createNestedMediaEntry(Entry $entry, int|array $assetIds, ?string $caption = null): string
    {
        return $this->createNestedEntry($entry, function (Entry $nestedEntry) use ($assetIds, $caption) {
            $nestedEntry->setTypeId(MediaEntryType::get()->id);
            $nestedEntry->setFieldValue(MediaField::get()->handle, (array) $assetIds);

            if ($caption) {
                $nestedEntry->setFieldValue(RichCaption::get()->handle, $caption);
            }
        });
    }

    /**
     * Saves a nested entry.
     */
    public function saveNestedEntry(Entry $entry): void
    {
        $entry->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
        if (! Elements::saveElement($entry)) {
            throw new RuntimeException(sprintf(
                'Could not save nested %s entry: %s',
                $entry->getType()->name,
                implode(', ', $entry->getFirstErrors())
            ));
        }
    }

    public function fieldsForEntity(string $type, string $name): array
    {
        $acfFields = [];
        foreach ($this->fieldGroupsForEntity($type, $name) as $groupData) {
            $acfFields = array_merge($acfFields, $groupData['fields']);
        }

        return $acfFields;
    }

    private function fieldGroupsForEntity(string $type, string $name): Generator
    {
        foreach ($this->wpInfo['field_groups'] as $groupData) {
            if ($this->showFieldGroupForEntity($type, $name, $groupData)) {
                yield $groupData;
            }
        }
    }

    private function showFieldGroupForEntity(string $type, string $name, array $groupData): bool
    {
        foreach ($groupData['location'] as $rules) {
            if ($this->locationMatchesRules($type, $name, $rules)) {
                return true;
            }
        }

        return false;
    }

    private function locationMatchesRules(string $type, string $name, array $rules): bool
    {
        foreach ($rules as $rule) {
            if ($rule['param'] === $type) {
                $result = in_array($rule['value'], [$name, 'all']);

                return $rule['operator'] === '!=' ? ! $result : $result;
            }
        }

        return false;
    }

    private function clonedFields(array $fieldData): array
    {
        $clonedFields = $this->fieldsByKeys($fieldData['clone']);
        if ($fieldData['prefix_label'] ?? false) {
            foreach ($clonedFields as &$clonedFieldData) {
                $clonedFieldData['label'] = sprintf('%s %s', $fieldData['label'], $clonedFieldData['label']);
            }
        }
        if ($fieldData['prefix_name'] ?? false) {
            foreach ($clonedFields as &$clonedFieldData) {
                $clonedFieldData['name'] = sprintf('%s_%s', $fieldData['name'], $clonedFieldData['name']);
            }
        }

        return $clonedFields;
    }

    private function fieldsByKeys(array $keys): array
    {
        $fields = [];
        foreach ($keys as $key) {
            if (str_starts_with($key, 'group_')) {
                $group = $this->fieldGroupByKey($key);
                array_push($fields, ...$group['fields']);
            } else {
                $fields[] = $this->fieldByKey($key);
            }
        }

        return $fields;
    }

    private function fieldGroupByKey(string $key): array
    {
        foreach ($this->wpInfo['field_groups'] as $groupData) {
            if ($groupData['key'] === $key) {
                return $groupData;
            }
        }

        throw new InvalidArgumentException("Invalid field group key: $key");
    }

    private function fieldByKey(string $key): array
    {
        foreach ($this->wpInfo['field_groups'] as $groupData) {
            $fieldData = $this->findFieldByKey($key, $groupData['fields']);
            if ($fieldData) {
                return $fieldData;
            }
        }

        throw new InvalidArgumentException("Invalid field key: $key");
    }

    private function findFieldByKey(string $key, array $fields): ?array
    {
        foreach ($fields as $fieldData) {
            if ($fieldData['key'] === $key) {
                return $fieldData;
            }

            switch ($fieldData['type']) {
                case 'group':
                case 'repeater':
                    $fieldData = $this->findFieldByKey($key, $fieldData['sub_fields']);
                    if ($fieldData) {
                        return $fieldData;
                    }
                    break;
                case 'flexible_content':
                    foreach ($fieldData['layouts'] as $layoutData) {
                        $fieldData = $this->findFieldByKey($key, $layoutData['sub_fields']);
                        if ($fieldData) {
                            return $fieldData;
                        }
                    }
                    break;
            }
        }

        return null;
    }

    /**
     * @param  array[]  $fields
     * @return FieldLayoutElement[]
     */
    public function acfFieldElements(array $fields): array
    {
        return Collection::make($fields)
            ->map(fn (array $fieldData) => match ($fieldData['type']) {
                'accordion', 'tab' => [
                    new Heading([
                        'heading' => $fieldData['label'],
                    ]),
                ],
                'message' => [
                    new Markdown([
                        'content' => $fieldData['message'],
                    ]),
                ],
                'group' => [
                    new Heading([
                        'heading' => $fieldData['label'],
                    ]),
                    ...$this->acfFieldElements($fieldData['sub_fields']),
                ],
                'clone' => $this->acfFieldElements($this->clonedFields($fieldData)),
                default => array_filter([
                    $this->acfFieldElement($fieldData),
                ]),
            })
            ->flatten(1)
            ->values()
            ->all();
    }

    private function acfFieldElement(array $fieldData): ?CustomField
    {
        if (empty($fieldData['name'])) {
            return null;
        }

        // Give it a unique global handle
        $handle = sprintf('acf_%s_%s', Str::toHandle($fieldData['name']), $fieldData['ID']);
        $field = Fields::getFieldByHandle($handle);

        if (! $field) {
            $adapter = $this->acfAdapter($fieldData);
            $field = $adapter->create($fieldData);
            $field->name = $fieldData['label'] ?: "Untitled ACF Field {$fieldData['ID']}";
            $field->handle = $handle;
            $field->instructions = $fieldData['instructions'];

            $this->do(sprintf('Creating `%s` %s field', $field->name, $field::displayName()), function () use ($field) {
                if (! Fields::saveField($field)) {
                    throw new RuntimeException(implode(', ', $field->getFirstErrors()));
                }
            });
        }

        // pass the handle via the config, as it’s only applied to the field when the field is set
        $config = [
            'handle' => $this->normalizeAcfFieldHandle($fieldData['name']),
            'required' => (bool) $fieldData['required'],
        ];

        if ($fieldData['wrapper']['width']) {
            // get it to the closest 25% increment
            $config['width'] = (int) (round($fieldData['wrapper']['width'] / 25) * 25);
        }

        return new CustomField($field, $config);
    }

    private function acfAdapter(array $data): BaseAcfAdapter
    {
        if (! isset($this->acfAdapters[$data['type']])) {
            $this->unknownAcfFieldTypes[$data['type']] = true;
            throw $this->errors[] = new UnknownAcfFieldTypeException($data['type'], $data);
        }

        return $this->acfAdapters[$data['type']];
    }

    /**
     * Adds field layout elements to a field layout, within a certain tab.
     *
     * @param  FieldLayoutElement[]  $elements
     */
    public function addElementsToLayout(
        FieldLayout $fieldLayout,
        string $tabName,
        array $elements,
        bool $prependTab = false,
        bool $prependElements = false,
    ): void {
        $tabs = $fieldLayout->getTabs();
        $tab = Arr::first($tabs, fn (FieldLayoutTab $tab) => $tab->name === $tabName);
        $newTab = ! $tab;

        if ($newTab) {
            $tab = new FieldLayoutTab([
                'name' => $tabName,
                'layout' => $fieldLayout,
            ]);
            if ($prependTab) {
                array_unshift($tabs, $tab);
            } else {
                $tabs[] = $tab;
            }
            $fieldLayout->setTabs($tabs);
        }

        // figure out which of these elements aren't already included in the layout
        $allElements = $fieldLayout->getAllElements();
        $missingElements = [];

        foreach ($elements as $element) {
            $test = null;
            if ($element instanceof CustomField) {
                $test = fn ($e) => (
                    $e instanceof CustomField &&
                    $e->getFieldUid() === $element->getFieldUid() &&
                    $e->getField()->handle === $element->getField()->handle
                );
            } elseif ($element instanceof BaseField) {
                $test = fn ($e) => $e instanceof BaseField && $e->attribute() === $element->attribute();
            } elseif (! $newTab) {
                // only add non-field layout elements if it's a new tab
                continue;
            }
            if (! $test || Arr::first($allElements, $test) === null) {
                $missingElements[] = $element;
            }
        }

        if (! empty($missingElements)) {
            $tabElements = $tab->getElements();
            if ($prependElements) {
                array_unshift($tabElements, ...$missingElements);
            } else {
                array_push($tabElements, ...$missingElements);
            }
            $tab->setElements($tabElements);
        }
    }

    public function addAcfFieldsToLayout(string $type, string $name, FieldLayout $fieldLayout): void
    {
        foreach ($this->fieldGroupsForEntity($type, $name) as $groupData) {
            $this->addElementsToLayout($fieldLayout, $groupData['title'], $this->acfFieldElements($groupData['fields']));
        }
    }

    public function prepareAcfFieldValues(array $acfFields, array $acfValues): array
    {
        $fieldValues = [];

        foreach ($acfValues as $fieldName => $fieldValue) {
            if ($fieldValue === '' || $fieldValue === null) {
                continue;
            }

            $fieldData = Arr::first($acfFields, fn (array $fieldData) => $fieldData['name'] === $fieldName);
            if (! $fieldData) {
                continue;
            }

            switch ($fieldData['type']) {
                case 'group':
                    $fieldValues = array_merge(
                        $fieldValues,
                        $this->prepareAcfFieldValues($fieldData['sub_fields'], is_array($fieldValue) ? $fieldValue : []),
                    );
                    break;
                case 'clone':
                    $fieldValues = array_merge(
                        $fieldValues,
                        $this->prepareAcfFieldValues($this->clonedFields($fieldData), is_array($fieldValue) ? $fieldValue : []),
                    );
                    break;
                default:
                    $handle = $this->normalizeAcfFieldHandle($fieldName);
                    $fieldValue = $this->acfAdapter($fieldData)->normalizeValue($fieldValue, $fieldData);
                    $fieldValues[$handle] = $fieldValue;
            }
        }

        return $fieldValues;
    }

    private function normalizeAcfFieldHandle(string $handle): string
    {
        $handle = Str::toHandle($handle);

        if (in_array($handle, [
            'author',
            'authorId',
            'authorIds',
            'authors',
            'group',
            'section',
            'sectionId',
            'type',
            ...Field::RESERVED_HANDLES,
        ])) {
            return "{$handle}_acf";
        }

        return $handle;
    }

    private function loadImporters(): void
    {
        $taxonomies = $postTypes = null;

        $this->do('Loading taxonomies', function () use (&$taxonomies) {
            $taxonomies = Collection::make($this->get("$this->apiUrl/wp/v2/taxonomies", [
                'context' => 'edit',
            ]))
                ->filter(fn ($data, $key) => (
                    ! in_array($key, ['post_tag', 'nav_menu']) &&
                    ! str_starts_with($key, 'wp_')
                ))
                ->all();
        });

        $this->do('Loading post types', function () use (&$postTypes) {
            $postTypes = Collection::make($this->get("$this->apiUrl/wp/v2/types", [
                'context' => 'edit',
            ]))
                ->filter(fn ($data, $key) => (
                    ! in_array($key, ['attachment', 'nav_menu_item']) &&
                    ! str_starts_with($key, 'wp_') &&
                    ! str_starts_with($key, 'acf-') &&
                    ! str_starts_with($key, 'gblocks_') &&
                    ! str_starts_with($key, 'jp_')
                ))
                ->all();
        });

        $this->importers = Collection::make([
            ...$this->loadComponents(
                'Importers',
                ImporterTypesResolving::class,
                fn (string $class) => ! is_subclass_of($class, BaseConfigurableImporter::class),
            ),
            ...array_map(fn (array $data) => new Taxonomy($data, $this), $taxonomies),
            ...array_map(fn (array $data) => new PostType($data, $this), $postTypes),
        ])
            ->keyBy(fn (BaseImporter $importer) => $importer->slug())
            ->all();
    }

    private function loadBlockTransformers(): void
    {
        $this->blockTransformers = Collection::make($this->loadComponents('BlockTransformers', BlockTransformerTypesResolving::class))
            ->keyBy(fn (BaseBlockTransformer $transformer) => $transformer::blockName())
            ->all();
    }

    private function loadAcfAdapters(): void
    {
        $this->acfAdapters = Collection::make($this->loadComponents('AcfAdapters', AcfAdapterTypesResolving::class))
            ->keyBy(fn (BaseAcfAdapter $adapter) => $adapter::type())
            ->all();
    }

    /**
     * @param  class-string<ImporterTypesResolving|BlockTransformerTypesResolving|AcfAdapterTypesResolving>  $eventClass
     */
    private function loadComponents(string $dir, string $eventClass, ?callable $filter = null): array
    {
        $types = Collection::make(File::files(dirname(__DIR__)."/$dir"))
            ->map(fn (\SplFileInfo $file) => sprintf('CraftCms\\WpImport\\%s\\%s', $dir, $file->getBasename('.php')))
            ->all();

        // Load any custom components from config/craft/wp-import/
        $customDir = Path::config(sprintf('wp-import/%s', Str::lower($dir)));
        if (is_dir($customDir)) {
            foreach (File::files($customDir) as $file) {
                $class = $this->classInFile($file->getPathname());
                if (! $class) {
                    continue;
                }
                // Laravel may have already loaded it along with the other config files
                if (! class_exists($class)) {
                    require_once $file->getPathname();
                }
                $types[] = $class;
            }
        }

        $event = new $eventClass(new Collection);
        $event->types->push(...$types);
        event($event);

        $components = [];
        foreach ($event->types->unique() as $class) {
            if ((new ReflectionClass($class))->isAbstract()) {
                continue;
            }
            if (! $filter || $filter($class)) {
                $components[] = new $class($this);
            }
        }

        return $components;
    }

    /**
     * Returns the fully-qualified name of the class defined in a file.
     */
    private function classInFile(string $path): ?string
    {
        $code = File::get($path);
        if (! preg_match('/^\s*(?:(?:abstract|final|readonly)\s+)*class\s+(\w+)/m', $code, $classMatch)) {
            return null;
        }
        $namespace = preg_match('/^\s*namespace\s+([^;\s]+)\s*;/m', $code, $namespaceMatch) ? $namespaceMatch[1] : null;

        return $namespace ? "$namespace\\$classMatch[1]" : $classMatch[1];
    }

    private function totalItems(string $slug): int
    {
        $importer = $this->importers[$slug];
        $this->get("$this->apiUrl/{$importer->apiUri()}", $this->importerQueryParams($slug), $response);

        return (int) $response->getHeaderLine('X-WP-Total');
    }

    private function systemCheck(): bool
    {
        if (! Cms::isInstalled()) {
            error('Craft isn’t installed yet.');

            return false;
        }

        if (! Plugins::isPluginInstalled('ckeditor')) {
            warning(<<<'TEXT'
Before we begin, the CKEditor plugin must be installed. Run the following commands to install it:

    composer require craftcms/ckeditor
    php artisan craft:plugin:install ckeditor
TEXT);

            return false;
        }

        if (! Plugins::isPluginEnabled('ckeditor')) {
            note('The CKEditor plugin must be enabled before we can continue.');
            if (! confirm('Enable it now?', true)) {
                return false;
            }
            Plugins::enablePlugin('ckeditor');
        }

        if (! is_array(config("filesystems.disks.$this->disk"))) {
            error("The `$this->disk` filesystem disk doesn’t exist. Add it to `config/filesystems.php`, or pass a different disk with `--disk`.");

            return false;
        }

        return true;
    }

    private function captureApiInfo(): void
    {
        if (isset($this->apiUrl)) {
            $this->apiUrl = $this->normalizeApiUrl($this->apiUrl);
        } else {
            $this->apiUrl = $this->normalizeApiUrl(text(
                label: 'WordPress site URL:',
                required: true,
                validate: function (string $value) {
                    $value = $this->normalizeApiUrl($value);
                    if (! filter_var($value, FILTER_VALIDATE_URL) || ! preg_match('/^https?:\/\//i', $value)) {
                        return 'Enter a valid URL.';
                    }

                    try {
                        $this->get("$value/craftcms/v1/ping");
                    } catch (Throwable $e) {
                        if ($e instanceof ClientException && $e->getResponse()->getStatusCode() === 404) {
                            return 'The wp-import Helper WordPress plugin doesn’t appear to be installed.';
                        }

                        return $e->getMessage();
                    }

                    return null;
                },
            ));
        }

        while (! isset($this->username) || ! isset($this->password)) {
            $this->username ??= text(label: 'Administrator username:', required: true);
            $this->password ??= password(label: "Application password for $this->username:", required: true);

            try {
                $this->do('Verifying credentials', function () {
                    $this->get("$this->apiUrl/wp/v2/posts", ['context' => 'edit'], $response);
                    if ($response->getStatusCode() !== 200) {
                        throw new RuntimeException('Invalid credentials.');
                    }
                });
            } catch (Throwable) {
                $this->username = $this->password = null;
            }
        }

        $this->wpInfo = $this->get("$this->apiUrl/craftcms/v1/info");
    }

    private function normalizeApiUrl(string $url): string
    {
        $url = Str::chopEnd($url, '/');
        $url = Str::chopEnd($url, '/v2');
        $url = Str::chopEnd($url, '/wp');
        $url = Str::chopEnd($url, '/wp-admin');

        return Str::finish($url, '/wp-json');
    }

    private function editionCheck(): void
    {
        if (! $this->input->isInteractive()) {
            return;
        }

        // We ignore Team's 5 user limit for the import
        // (not worth the effort/friction to map user accounts up front)
        if (Edition::get() !== Edition::Solo) {
            return;
        }

        $totalWpUsers = $this->totalItems(UserImporter::SLUG);
        if ($totalWpUsers === 1) {
            return;
        }

        warning(sprintf(
            'Craft Solo is limited to one user account, but your WordPress install has %s users. All WordPress posts will be assigned to that one account, unless you upgrade to the Team or Pro edition.',
            number_format($totalWpUsers),
        ));

        if (! confirm('Upgrade your Craft edition?', false)) {
            return;
        }

        $newEdition = Edition::fromHandle(select('New edition:', [
            Edition::Team->handle() => sprintf('Team (%s users)', Users::getMaxUsers(Edition::Team)),
            Edition::Pro->handle() => 'Pro (unlimited users)',
        ]));

        $this->do('Upgrading the Craft edition', function () use ($newEdition) {
            Edition::set($newEdition);
        });
    }

    public function import(string $slug, int|array $data, array $queryParams = []): int
    {
        $importer = $this->importers[$slug] ?? null;
        if (! $importer) {
            throw new InvalidArgumentException("Invalid content type: $slug");
        }

        $id = is_int($data) ? $data : $data['id'];

        // Did we already import this item in the same request?
        if (isset($this->idMap[$slug][$id])) {
            return $this->idMap[$slug][$id];
        }

        // If this is the first time we've imported an item of this type,
        // give the importer a chance to prep the system for it
        if (! isset($this->idMap[$slug])) {
            $importer->prep();
        }

        // Already exists?
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $importer->elementType();
        $element = $importer->existingElementQuery()
            ->{WpId::get()->handle}($id)
            ->status(null)
            ->limit(1)
            ->one();

        if ($element && ! $this->update) {
            return $element->id;
        }

        $importerLabel = Str::singular($slug);
        $name = trim(($data['name'] ?? null) ?: ($data['title']['raw'] ?? null) ?: ($data['slug'] ?? null) ?: '');
        $name = ($name !== '' && $name != $id) ? "`$name` (`$id`)" : "`$id`";

        try {
            $this->do("Importing $importerLabel $name", function () use (
                $data,
                $queryParams,
                $importer,
                $elementType,
                $id,
                &$element,
            ) {
                if (is_int($data)) {
                    $data = $this->item($importer->slug(), $data, $queryParams);
                }

                $element ??= $importer->find($data) ?? new $elementType;
                $importer->populate($element, $data);
                $element->setFieldValue(WpId::get()->handle, $id);

                if ($element->ruleset->getScenario() === ElementRules::SCENARIO_DEFAULT) {
                    $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
                }

                // We ignore the edition’s user limits for the import
                if ($element instanceof User && ! Edition::isAtLeast(Edition::Pro)) {
                    $edition = Edition::get();
                    Context::addHidden(Edition::class, Edition::Pro);
                }

                try {
                    if (! Elements::saveElement($element)) {
                        throw new RuntimeException(implode(', ', $element->getFirstErrors()));
                    }

                    $this->importTotal++;
                } finally {
                    if (isset($edition)) {
                        Context::addHidden(Edition::class, $edition);
                    }
                }
            });
        } catch (Throwable $e) {
            // UnknownBlockTypeException's have already been captured
            if (! $e instanceof UnknownBlockTypeException && ! $e instanceof UnknownAcfFieldTypeException) {
                $e = new ImportException($slug, $id, $e);
                $this->errors[] = $e;
            }
            throw $e;
        }

        return $this->idMap[$slug][$id] = $element->id;
    }

    /**
     * Imports a post of an unknown type
     */
    public function importPost(int|array $data, array $queryParams = []): int
    {
        if (is_int($data)) {
            // Did we already import this item in the same request?
            foreach ($this->importers as $importer) {
                if (isset($this->idMap[$importer->slug()][$data])) {
                    return $this->idMap[$importer->slug()][$data];
                }
            }

            $data = $this->get("$this->apiUrl/craftcms/v1/post/$data");
        }

        return $this->import($data['type'], $data['id'], $queryParams);
    }

    public function isSupported(string $slug, ?string &$reason = null): bool
    {
        return $this->importers[$slug]->supported($reason);
    }

    public function items(string $slug, array $queryParams = []): Generator
    {
        $page = $this->page ?? 1;
        $importer = $this->importers[$slug];
        do {
            $body = $this->get(
                "$this->apiUrl/{$importer->apiUri()}",
                array_merge($this->importerQueryParams($slug), [
                    'page' => $page,
                    'per_page' => $this->perPage,
                ], $queryParams),
                $response,
            );
            foreach ($body as $item) {
                yield $item;
            }
            if (empty($body) || isset($this->page)) {
                break;
            }
            $page++;
        } while (true);
    }

    public function item(string $slug, int $id, array $queryParams = []): array
    {
        $importer = $this->importers[$slug];

        return $this->get(
            "$this->apiUrl/{$importer->apiUri()}/$id",
            array_merge($this->importerQueryParams($slug), $queryParams),
        );
    }

    private function importerQueryParams(string $slug): array
    {
        return array_merge([
            'context' => 'edit',
        ], $this->importers[$slug]->queryParams());
    }

    public function get(string $uri, array $queryParams = [], ?ResponseInterface &$response = null): array
    {
        try {
            $response = $this->client->get($uri, [
                RequestOptions::AUTH => [$this->username, $this->password],
                RequestOptions::QUERY => $queryParams,
            ]);
        } catch (UrlValidationException $e) {
            throw new RuntimeException("{$e->getMessage()} If this is an internal host, run the command with `--allowed-private-hosts=<hostname>`.");
        } catch (RequestException $e) {
            if ($e->getResponse()?->getStatusCode() === 400) {
                try {
                    $body = $this->decodeBody((string) $e->getResponse()->getBody());
                } catch (InvalidArgumentException) {
                    throw $e;
                }
                switch ($body['code'] ?? '') {
                    case 'rest_post_invalid_page_number':
                        return [];
                    case 'rest_invalid_param':
                        // try again without the offending params
                        $filteredQueryParams = Arr::except($queryParams, array_keys($body['data']['params'] ?? []));
                        if ($filteredQueryParams !== $queryParams) {
                            return $this->get($uri, $filteredQueryParams, $response);
                        }
                        break;
                }
            }
            throw $e;
        }

        return $this->decodeBody((string) $response->getBody());
    }

    private function decodeBody(string $body): array
    {
        try {
            return Json::decode($body);
        } catch (InvalidArgumentException $e) {
            // Skip any PHP warnings at the top
            if (
                ! preg_match('/^[\[{]/m', $body, $matches, PREG_OFFSET_CAPTURE) ||
                $matches[0][1] === 0
            ) {
                throw $e;
            }

            return Json::decode(substr($body, $matches[0][1]));
        }
    }

    public function outputSummary(): void
    {
        if (! $this->showSummary) {
            return;
        }

        $dryRunLabel = $this->dryRun ? '[DRY RUN] ' : '';
        $importMessage = sprintf('Imported %s successfully', $this->importTotal === 1 ? '1 item' : "$this->importTotal items");

        if (empty($this->errors)) {
            outro($dryRunLabel.$importMessage);

            return;
        }

        $report = '';
        $hr = sprintf("%s\n", str_repeat('-', 80));

        if (! empty($this->unknownBlockTypes)) {
            $report .= "The following unknown block types were encountered:\n";
            foreach (array_keys($this->unknownBlockTypes) as $type) {
                $report .= " - $type\n";
            }
            $report .= "$hr";
        }

        if (! empty($this->unknownAcfFieldTypes)) {
            $report .= "The following unknown ACF field types were encountered:\n";
            foreach (array_keys($this->unknownAcfFieldTypes) as $type) {
                $report .= " - $type\n";
            }
            $report .= "$hr";
        }

        foreach ($this->errors as $i => $e) {
            if ($i !== 0) {
                $report .= $hr;
            }

            $report .= "Error: {$e->getMessage()}\n";
            if ($e instanceof ReportableExceptionInterface) {
                $extra = trim($e->getReport(), "\n");
                if ($extra !== '') {
                    $report .= "$extra\n";
                }
            } else {
                if ($e instanceof ImportException) {
                    $report .= "Content type: $e->slug\n";
                    $report .= "Item ID: $e->itemId\n";
                    $e = $e->getPrevious();
                }
                $report .= sprintf("Location: %s\n", implode(':', array_filter([$e->getFile(), $e->getLine()])));
                $report .= sprintf("Trace:\n%s\n", $e->getTraceAsString());
            }
        }

        $reportPath = base_path('wp-import-errors.txt');
        File::writeToFile($reportPath, $report);

        $totalErrors = count($this->errors);
        $errorMessage = sprintf('%s encountered.', $totalErrors === 1 ? '1 error was' : "$totalErrors errors were");
        if ($this->importTotal) {
            $errorMessage = "$importMessage, but $errorMessage";
        }
        $errorMessage .= " Error details have been logged to $reportPath.";
        error($dryRunLabel.$errorMessage);
    }

    public function normalizeColor(?string $color): ?string
    {
        if (! $color) {
            return null;
        }

        if (preg_match('/^(?:#[0-9a-f]{6}|transparent)$/', $color)) {
            return $color;
        }

        foreach ($this->wpInfo['color_palette'] as $palette) {
            foreach ($palette as $paletteColor) {
                if ($paletteColor['slug'] === $color) {
                    return $paletteColor['color'];
                }
            }
        }

        return null;
    }

    public function normalizeIcon(?string $icon): ?string
    {
        return match ($icon) {
            'dashicons-admin-appearance' => 'paintbrush',
            'dashicons-admin-collapse' => 'circle-caret-left',
            'dashicons-admin-comments' => 'comment',
            'dashicons-admin-customizer' => 'paintbrush-fine',
            'dashicons-admin-generic' => 'gear',
            'dashicons-admin-home' => 'house',
            'dashicons-admin-links' => 'link',
            'dashicons-admin-media' => 'photo-film-music',
            'dashicons-admin-multisite' => 'house',
            'dashicons-admin-network' => 'key',
            'dashicons-admin-page' => 'page',
            'dashicons-admin-plugins' => 'plug',
            'dashicons-admin-post' => 'thumbtack',
            'dashicons-admin-settings' => 'square-sliders-vertical',
            'dashicons-admin-site' => 'earth-americas',
            'dashicons-admin-site-alt' => 'earth-africa',
            'dashicons-admin-site-alt2' => 'earth-asia',
            'dashicons-admin-site-alt3' => 'globe',
            'dashicons-admin-tools' => 'wrench',
            'dashicons-admin-users' => 'user',
            'dashicons-airplane' => 'plane',
            'dashicons-album' => 'record-vinyl',
            'dashicons-align-center' => 'align-center',
            'dashicons-align-full-width' => 'align-justify',
            'dashicons-align-left' => 'align-left',
            'dashicons-align-none' => 'align-slash',
            'dashicons-align-pull-left' => 'align-left',
            'dashicons-align-pull-right' => 'align-right',
            'dashicons-align-right' => 'align-right',
            'dashicons-align-wide' => 'align-justify',
            'dashicons-amazon' => 'amazon',
            'dashicons-analytics' => 'chart-pie',
            'dashicons-archive' => 'box-archive',
            'dashicons-arrow-down' => 'caret-down',
            'dashicons-arrow-down-alt' => 'arrow-down',
            'dashicons-arrow-down-alt2' => 'chevron-down',
            'dashicons-arrow-left' => 'caret-left',
            'dashicons-arrow-left-alt' => 'arrow-left',
            'dashicons-arrow-left-alt2' => 'chevron-left',
            'dashicons-arrow-right' => 'caret-right',
            'dashicons-arrow-right-alt' => 'arrow-right',
            'dashicons-arrow-right-alt2' => 'chevron-right',
            'dashicons-arrow-up' => 'caret-up',
            'dashicons-arrow-up-alt' => 'arrow-up',
            'dashicons-arrow-up-alt2' => 'chevron-up',
            'dashicons-art' => 'palette',
            'dashicons-awards' => 'award',
            'dashicons-backup' => 'clock-rotate-left',
            'dashicons-bank' => 'building-columns',
            'dashicons-beer' => 'beer-mug',
            'dashicons-bell' => 'bell',
            'dashicons-block-default' => 'block-question',
            'dashicons-book' => 'book',
            'dashicons-book-alt' => 'notebook',
            'dashicons-buddicons-activity' => 'horse-saddle',
            'dashicons-buddicons-buddypress-logo' => 'people',
            'dashicons-buddicons-community' => 'cake-candles',
            'dashicons-buddicons-friends' => 'cake-candles',
            'dashicons-buddicons-groups' => 'balloons',
            'dashicons-buddicons-pm' => 'envelope-open-text',
            'dashicons-buddicons-replies' => 'bee',
            'dashicons-buddicons-topics' => 'honey-pot',
            'dashicons-building' => 'building',
            'dashicons-businessman' => 'user-tie',
            'dashicons-businessperson' => 'user-tie-hair',
            'dashicons-businesswoman' => 'user-tie-hair-long',
            'dashicons-calculator' => 'calculator',
            'dashicons-calendar' => 'calendar',
            'dashicons-calendar-alt' => 'calendar-days',
            'dashicons-camera' => 'camera-retro',
            'dashicons-camera-alt' => 'camera',
            'dashicons-car' => 'car-side',
            'dashicons-carrot' => 'carrot',
            'dashicons-cart' => 'cart-shopping',
            'dashicons-category' => 'folder',
            'dashicons-chart-area' => 'chart-area',
            'dashicons-chart-bar' => 'chart-simple',
            'dashicons-chart-line' => 'chart-line',
            'dashicons-chart-pie' => 'chart-pie-simple',
            'dashicons-clipboard' => 'clipboard',
            'dashicons-clock' => 'clock',
            'dashicons-cloud' => 'cloud',
            'dashicons-cloud-saved' => 'cloud-check',
            'dashicons-cloud-upload' => 'cloud-arrow-up',
            'dashicons-coffee' => 'mug-saucer',
            'dashicons-color-picker' => 'eye-dropper',
            'dashicons-columns' => 'columns-3',
            'dashicons-controls-back' => 'backward',
            'dashicons-controls-forward' => 'forward',
            'dashicons-controls-pause' => 'pause',
            'dashicons-controls-play' => 'play',
            'dashicons-controls-repeat' => 'repeat',
            'dashicons-controls-skipback' => 'backward-step',
            'dashicons-controls-skipforward' => 'forward-step',
            'dashicons-controls-volumeoff' => 'volume-off',
            'dashicons-controls-volumeon' => 'volume',
            'dashicons-dashboard' => 'gauge',
            'dashicons-database' => 'database',
            'dashicons-desktop' => 'desktop',
            'dashicons-dismiss' => 'circle-xmark',
            'dashicons-download' => 'download',
            'dashicons-drumstick' => 'drumstick-bite',
            'dashicons-edit' => 'pencil',
            'dashicons-edit-large' => 'pencil',
            'dashicons-edit-page' => 'file-pen',
            'dashicons-editor-aligncenter' => 'align-center',
            'dashicons-editor-alignleft' => 'align-left',
            'dashicons-editor-alignright' => 'align-right',
            'dashicons-editor-bold' => 'bold',
            'dashicons-editor-break' => 'arrow-turn-down-left',
            'dashicons-editor-code' => 'code-simple',
            'dashicons-editor-contract' => 'compress',
            'dashicons-editor-expand' => 'expand',
            'dashicons-editor-help' => 'circle-question',
            'dashicons-editor-indent' => 'indent',
            'dashicons-editor-italic' => 'italic',
            'dashicons-editor-justify' => 'align-justify',
            'dashicons-editor-ol' => 'list-ol',
            'dashicons-editor-ol-rtl' => 'list-ol',
            'dashicons-editor-outdent' => 'outdent',
            'dashicons-editor-paragraph' => 'paragraph',
            'dashicons-editor-paste-text' => 'clipboard',
            'dashicons-editor-paste-word' => 'clipboard',
            'dashicons-editor-quote' => 'quote-left',
            'dashicons-editor-removeformatting' => 'eraser',
            'dashicons-editor-spellcheck' => 'spell-check',
            'dashicons-editor-strikethrough' => 'strikethrough',
            'dashicons-editor-table' => 'table',
            'dashicons-editor-textcolor' => 'a',
            'dashicons-editor-ul' => 'list-ul',
            'dashicons-editor-underline' => 'underline',
            'dashicons-editor-unlink' => 'link-slash',
            'dashicons-editor-video' => 'film',
            'dashicons-ellipsis' => 'ellipsis',
            'dashicons-email' => 'envelope',
            'dashicons-email-alt' => 'envelope',
            'dashicons-email-alt2' => 'envelope',
            'dashicons-exit' => 'arrow-left-from-bracket',
            'dashicons-external' => 'up-right-from-square',
            'dashicons-facebook' => 'facebook',
            'dashicons-facebook-alt' => 'facebook',
            'dashicons-filter' => 'filter',
            'dashicons-flag' => 'flag',
            'dashicons-food' => 'utensils',
            'dashicons-format-audio' => 'music',
            'dashicons-format-chat' => 'comments',
            'dashicons-format-gallery' => 'image',
            'dashicons-format-image' => 'image',
            'dashicons-format-quote' => 'quote-left',
            'dashicons-format-status' => 'message-dots',
            'dashicons-format-video' => 'video',
            'dashicons-forms' => 'square-check',
            'dashicons-fullscreen-alt' => 'expand',
            'dashicons-fullscreen-exit-alt' => 'compress',
            'dashicons-games' => 'gamepad-modern',
            'dashicons-google' => 'google',
            'dashicons-grid-view' => 'grid-2',
            'dashicons-groups' => 'users',
            'dashicons-hammer' => 'hammer',
            'dashicons-heading' => 'heading',
            'dashicons-heart' => 'heart',
            'dashicons-hidden' => 'eye-slash',
            'dashicons-hourglass' => 'hourglass',
            'dashicons-html' => 'code',
            'dashicons-id' => 'id-badge',
            'dashicons-id-alt' => 'id-badge',
            'dashicons-image-crop' => 'crop',
            'dashicons-image-flip-horizontal' => 'reflect-horizontal',
            'dashicons-image-flip-vertical' => 'reflect-vertical',
            'dashicons-image-rotate' => 'rotate-left',
            'dashicons-image-rotate-left' => 'rotate-left',
            'dashicons-image-rotate-right' => 'rotate-right',
            'dashicons-images-alt' => 'images',
            'dashicons-images-alt2' => 'images',
            'dashicons-info' => 'circle-info',
            'dashicons-info-outline' => 'circle-info',
            'dashicons-insert' => 'circle-plus',
            'dashicons-instagram' => 'instagram',
            'dashicons-laptop' => 'laptop',
            'dashicons-layout' => 'objects-column',
            'dashicons-leftright' => 'left-right',
            'dashicons-lightbulb' => 'lightbulb',
            'dashicons-linkedin' => 'linkedin',
            'dashicons-list-view' => 'list',
            'dashicons-location' => 'location-dot',
            'dashicons-location-alt' => 'map-location',
            'dashicons-lock' => 'lock',
            'dashicons-media-archive' => 'file-zipper',
            'dashicons-media-audio' => 'file-music',
            'dashicons-media-code' => 'file-code',
            'dashicons-media-default' => 'file',
            'dashicons-media-document' => 'file',
            'dashicons-media-spreadsheet' => 'file-spreadsheet',
            'dashicons-media-text' => 'file-lines',
            'dashicons-media-video' => 'file-video',
            'dashicons-megaphone' => 'megaphone',
            'dashicons-menu' => 'bars',
            'dashicons-menu-alt' => 'bars',
            'dashicons-menu-alt2' => 'bars',
            'dashicons-menu-alt3' => 'bars',
            'dashicons-microphone' => 'microphone',
            'dashicons-migrate' => 'arrow-right-from-bracket',
            'dashicons-minus' => 'minus',
            'dashicons-money-alt' => 'circle-dollar',
            'dashicons-move' => 'up-down-left-right',
            'dashicons-nametag' => 'id-badge',
            'dashicons-networking' => 'sitemap',
            'dashicons-no' => 'xmark',
            'dashicons-no-alt' => 'xmark',
            'dashicons-open-folder' => 'folder-open',
            'dashicons-palmtree' => 'tree-palm',
            'dashicons-paperclip' => 'paperclip',
            'dashicons-pdf' => 'file-pdf',
            'dashicons-performance' => 'gauge-max',
            'dashicons-pets' => 'paw',
            'dashicons-phone' => 'phone',
            'dashicons-pinterest' => 'pinterest',
            'dashicons-playlist-audio' => 'list-music',
            'dashicons-plugins-checked' => 'plug-circle-check',
            'dashicons-plus' => 'plus',
            'dashicons-plus-alt' => 'circle-plus',
            'dashicons-plus-alt2' => 'plus',
            'dashicons-portfolio' => 'briefcase',
            'dashicons-post-status' => 'map-pin',
            'dashicons-printer' => 'print',
            'dashicons-privacy' => 'shield-halved',
            'dashicons-products' => 'bag-shopping',
            'dashicons-randomize' => 'shuffle',
            'dashicons-reddit' => 'reddit',
            'dashicons-redo' => 'arrow-rotate-right',
            'dashicons-remove' => 'circle-minus',
            'dashicons-rest-api' => 'webhook',
            'dashicons-rss' => 'rss',
            'dashicons-saved' => 'check',
            'dashicons-schedule' => 'calendar-days',
            'dashicons-screenoptions' => 'grid-2',
            'dashicons-search' => 'magnifying-glass',
            'dashicons-share' => 'share',
            'dashicons-share-alt' => 'share',
            'dashicons-share-alt2' => 'share',
            'dashicons-shield' => 'shield-halved',
            'dashicons-shield-alt' => 'shield-halved',
            'dashicons-shortcode' => 'brackets-square',
            'dashicons-smartphone' => 'mobile',
            'dashicons-smiley' => 'face-smile',
            'dashicons-sort' => 'sort',
            'dashicons-sos' => 'life-ring',
            'dashicons-spotify' => 'spotify',
            'dashicons-star-filled' => 'star',
            'dashicons-star-half' => 'star-half',
            'dashicons-sticky' => 'thumbtack',
            'dashicons-store' => 'store',
            'dashicons-superhero' => 'mushroom',
            'dashicons-superhero-alt' => 'mushroom',
            'dashicons-tablet' => 'tablet',
            'dashicons-tag' => 'tag',
            'dashicons-testimonial' => 'comment-lines',
            'dashicons-text-page' => 'file-lines',
            'dashicons-thumbs-down' => 'thumbs-down',
            'dashicons-thumbs-up' => 'thumbs-up',
            'dashicons-tickets' => 'tickets',
            'dashicons-tickets-alt' => 'tickets',
            'dashicons-tide' => 'water',
            'dashicons-translation' => 'language',
            'dashicons-trash' => 'trash',
            'dashicons-twitch' => 'twitch',
            'dashicons-twitter' => 'x-twitter',
            'dashicons-twitter-alt' => 'x-twitter',
            'dashicons-undo' => 'arrow-rotate-left',
            'dashicons-universal-access' => 'universal-access',
            'dashicons-universal-access-alt' => 'universal-access',
            'dashicons-unlock' => 'unlock',
            'dashicons-update' => 'arrows-rotate',
            'dashicons-update-alt' => 'arrows-rotate-reverse',
            'dashicons-upload' => 'upload',
            'dashicons-vault' => 'vault',
            'dashicons-video-alt' => 'video',
            'dashicons-video-alt2' => 'video',
            'dashicons-video-alt3' => 'play',
            'dashicons-visibility' => 'eye',
            'dashicons-warning' => 'circle-exclamation',
            'dashicons-welcome-add-page' => 'file-circle-plus',
            'dashicons-welcome-comments' => 'message-xmark',
            'dashicons-welcome-learn-more' => 'graduation-cap',
            'dashicons-welcome-write-blog' => 'file-pen',
            'dashicons-whatsapp' => 'whatsapp',
            'dashicons-wordpress' => 'wordpress',
            'dashicons-wordpress-alt' => 'wordpress',
            'dashicons-xing' => 'xing',
            'dashicons-yes' => 'check',
            'dashicons-yes-alt' => 'circle-check',
            'dashicons-youtube' => 'youtube',
            // 'dashicons-buddicons-forums' => '',
            // 'dashicons-buddicons-tracking' => '',
            // 'dashicons-button' => '',
            // 'dashicons-code-standards' => '',
            // 'dashicons-cover-image' => '',
            // 'dashicons-database-add' => '',
            // 'dashicons-database-export' => '',
            // 'dashicons-database-import' => '',
            // 'dashicons-database-remove' => '',
            // 'dashicons-database-view' => '',
            // 'dashicons-editor-customchar' => '',
            // 'dashicons-editor-insertmore' => '',
            // 'dashicons-editor-kitchensink' => '',
            // 'dashicons-editor-ltr' => '',
            // 'dashicons-editor-rtl' => '',
            // 'dashicons-embed-audio' => '',
            // 'dashicons-embed-generic' => '',
            // 'dashicons-embed-photo' => '',
            // 'dashicons-embed-post' => '',
            // 'dashicons-embed-video' => '',
            // 'dashicons-excerpt-view' => '',
            // 'dashicons-feedback' => '',
            // 'dashicons-format-aside' => '',
            // 'dashicons-image-filter' => '',
            // 'dashicons-index-card' => '',
            // 'dashicons-insert-after' => '',
            // 'dashicons-insert-before' => '',
            // 'dashicons-marker' => '',
            // 'dashicons-media-interactive' => '',
            // 'dashicons-money' => '',
            // 'dashicons-playlist-video' => '',
            // 'dashicons-podio' => '',
            // 'dashicons-pressthis' => '',
            // 'dashicons-slides' => '',
            // 'dashicons-star-empty' => '',
            // 'dashicons-table-col-after' => '',
            // 'dashicons-table-col-before' => '',
            // 'dashicons-table-col-delete' => '',
            // 'dashicons-table-row-after' => '',
            // 'dashicons-table-row-before' => '',
            // 'dashicons-table-row-delete' => '',
            // 'dashicons-tagcloud' => '',
            // 'dashicons-text' => '',
            // 'dashicons-welcome-view-site' => '',
            // 'dashicons-welcome-widgets-menus' => '',
            default => null,
        };
    }
}
