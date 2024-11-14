<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\console\Controller;
use craft\elements\Entry;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\events\RegisterComponentTypesEvent;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\Heading;
use craft\fieldlayoutelements\Markdown;
use craft\fields\PlainText;
use craft\helpers\ArrayHelper;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\CategoryGroup;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\TagGroup;
use craft\validators\ColorValidator;
use craft\validators\HandleValidator;
use craft\wpimport\errors\ImportException;
use craft\wpimport\errors\UnknownAcfFieldTypeException;
use craft\wpimport\errors\UnknownBlockTypeException;
use craft\wpimport\generators\fields\WpId;
use craft\wpimport\importers\Comment;
use craft\wpimport\importers\Comment as CommentImporter;
use craft\wpimport\importers\Media;
use craft\wpimport\importers\PostType;
use craft\wpimport\importers\Taxonomy;
use craft\wpimport\importers\User as UserImporter;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\console\Exception;
use yii\console\ExitCode;
use yii\helpers\Inflector;
use yii\validators\UrlValidator;
use yii\validators\Validator;

/**
 * Imports content from WordPress.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Command extends Controller
{
    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering importers.
     *
     * Transformers must extend [[BaseImporter]].
     * ---
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use craft\wpimport\Command;
     * use yii\base\Event;
     *
     * if (class_exists(Command::class)) {
     *     Event::on(
     *         Command::class,
     *         Command::EVENT_REGISTER_IMPORTERS,
     *         function(RegisterComponentTypesEvent $event) {
     *             $event->types[] = MyImporter::class;
     *         }
     *     );
     * }
     * ```
     */
    public const EVENT_REGISTER_IMPORTERS = 'registerImporters';

    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering block transformers.
     *
     * Transformers must extend [[BaseBlockTransformer]].
     * ---
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use craft\wpimport\Command;
     * use yii\base\Event;
     *
     * if (class_exists(Command::class)) {
     *     Event::on(
     *         Command::class,
     *         Command::EVENT_REGISTER_BLOCK_TRANSFORMERS,
     *         function(RegisterComponentTypesEvent $event) {
     *             $event->types[] = MyBlockTransformer::class;
     *         }
     *     );
     * }
     * ```
     */
    public const EVENT_REGISTER_BLOCK_TRANSFORMERS = 'registerBlockTransformers';

    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering ACF field adapters.
     *
     * Transformers must extend [[BaseAcfAdapter]].
     * ---
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use craft\wpimport\Command;
     * use yii\base\Event;
     *
     * if (class_exists(Command::class)) {
     *     Event::on(
     *         Command::class,
     *         Command::EVENT_REGISTER_ACF_ADAPTERS,
     *         function(RegisterComponentTypesEvent $event) {
     *             $event->types[] = MyAcfAdapter::class;
     *         }
     *     );
     * }
     * ```
     */
    public const EVENT_REGISTER_ACF_ADAPTERS = 'registerAcfAdapters';

    /**
     * @inheritdoc
     */
    public $defaultAction = 'import';

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
     * `category`, `tag`, `user`, `comment`, or a custom post type/taxonomy’s slug)
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
     * @var BaseImporter[]
     */
    public array $importers;
    /**
     * @var BaseBlockTransformer[]
     */
    private array $blockTransformers;
    /**
     * @var BaseAcfAdapter[]
     */
    private array $acfAdapters;

    public bool $importComments;
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

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), [
            'apiUrl',
            'username',
            'password',
            'type',
            'itemId',
            'page',
            'perPage',
            'update',
            'dryRun',
            'failFast',
            'showSummary',
        ]);
    }

    /**
     * Imports items from WordPress.
     *
     * @return int
     */
    public function actionImport(): int
    {
        if (!$this->systemCheck()) {
            return ExitCode::OK;
        }

        $this->client = Craft::createGuzzleClient([
            RequestOptions::VERIFY => false,
        ]);

        $this->captureApiInfo();
        $this->loadImporters();
        $this->editionCheck();
        $this->loadBlockTransformers();
        $this->loadAcfAdapters();

        if (!empty($this->type)) {
            $resources = $this->type;
        } else {
            // Use this specific order so we don't need to do as many one-off item imports
            $resources = [
                UserImporter::SLUG,
                Media::SLUG,
                CommentImporter::SLUG,
            ];
            // Add in any custom post types
            foreach ($this->importers as $importer) {
                $resource = $importer->slug();
                if (!in_array($resource, $resources)) {
                    $resources[] = $resource;
                }
            }
        }

        $resources = array_filter($resources, fn(string $resource) => $this->isSupported($resource));
        $totals = [];

        if ($this->interactive) {
            $this->do('Fetching info', function() use ($resources, &$totals) {
                foreach ($resources as $resource) {
                    $importer = $this->importers[$resource];
                    $label = $importer->label();
                    if ($importer instanceof BaseConfigurableImporter) {
                        $typeLabel = sprintf('(%s)', $importer->typeLabel());
                        // Console::table() didn't handle sub-value formatting until 5.5.1
                        if ($this->isColorEnabled() && version_compare(Craft::$app->getVersion(), '5.5.1', '>=')) {
                            $typeLabel = Console::ansiFormat($typeLabel, [Console::FG_YELLOW]);
                        }
                        $label = sprintf('%s %s', $label, $typeLabel);
                    }

                    $totals[] = [
                        $label,
                        [$importer->slug(), 'format' => [Console::FG_CYAN]],
                        [Craft::$app->formatter->asInteger($this->totalItems($resource)), 'align' => 'right'],
                    ];
                }
            });

            $totals = array_values(Arr::sort($totals, fn(array $item) => Console::stripAnsiFormat($item[0])));

            $this->stdout("\n");
            $this->table(['Content Type', 'Slug', 'Total Items'], $totals);
            $this->stdout("\n");
            if (!$this->confirm('Continue with the import?', true)) {
                $this->stdout("Aborting\n\n");
                return ExitCode::OK;
            }
            $this->stdout("\n");
        }

        if ($this->dryRun) {
            $transaction = Craft::$app->getDb()->beginTransaction();
        }

        try {
            foreach ($resources as $resource) {
                $label = mb_strtolower($this->importers[$resource]->label());
                $this->do("Importing $label", function() use ($resource) {
                    Console::indent();
                    try {
                        $items = $this->items($resource, [
                            'include' => implode(',', $this->itemId),
                        ]);
                        foreach ($items as $data) {
                            try {
                                $this->import($resource, $data);
                            } catch (Throwable $e) {
                                if ($this->failFast) {
                                    throw $e;
                                }
                            }
                        }
                    } finally {
                        Console::outdent();
                    }
                });
            }
        } finally {
            if (isset($transaction)) {
                $transaction->rollBack();
                // prevent project config changes from persisting
                Craft::$app->projectConfig->reset();
            }
        }

        $this->outputSummary();
        return ExitCode::OK;
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
        if ($firstError) {
            throw $firstError;
        }
        return $html;
    }

    public function renderBlock(array $block, Entry $entry): string
    {
        if (empty($block['blockName'])) {
            // this post uses the classic editor
            return $block['innerHTML'];
        }

        if (!isset($this->blockTransformers[$block['blockName']])) {
            throw new UnknownBlockTypeException($block['blockName'], $block);
        }

        $html = $this->blockTransformers[$block['blockName']]->render($block, $entry);
        return trim($html) . "\n";
    }

    public function acfLayoutTabsForEntity(string $type, string $name, FieldLayout $fieldLayout): array
    {
        $tabs = [];

        foreach ($this->fieldGroupsForEntity($type, $name) as $groupData) {
            $tabs[] = new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => $groupData['title'],
                'elements' => $this->acfFieldElements($groupData['fields']),
            ]);
        }

        return $tabs;
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
                return $rule['operator'] === '!=' ? !$result : $result;
            }
        }
        return false;
    }

    public function acfFieldElements(array $fields): array
    {
        return Collection::make($fields)
            ->map(fn(array $fieldData) => match($fieldData['type']) {
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
        $handle = sprintf('acf_%s_%s', StringHelper::toHandle($fieldData['name']), $fieldData['ID']);
        $field = Craft::$app->fields->getFieldByHandle($handle);

        if (!$field) {
            $adapter = $this->acfAdapter($fieldData);
            $field = $adapter->create($fieldData);
            $field->name = $fieldData['label'] ?: "Untitled ACF Field {$fieldData['ID']}";
            $field->handle = $handle;
            $field->instructions = $fieldData['instructions'];

            $this->do(sprintf('Creating `%s` %s field', $field->name, $field::displayName()), function() use ($field) {
                if (!Craft::$app->fields->saveField($field)) {
                    throw new Exception(implode(', ', $field->getFirstErrors()));
                }
            });
        }

        $element = new CustomField($field);
        $element->handle = $this->normalizeAcfFieldHandle($fieldData['name']);
        $element->required = $fieldData['required'];

        if ($fieldData['wrapper']['width']) {
            // get it to the closest 25% increment
            $element->width = (int)(round($fieldData['wrapper']['width'] / 25) * 25);
        }

        return $element;
    }

    private function acfAdapter(array $data): BaseAcfAdapter
    {
        if (!isset($this->acfAdapters[$data['type']])) {
            $this->unknownAcfFieldTypes[$data['type']] = true;
            throw $this->errors[] = new UnknownAcfFieldTypeException($data['type'], $data);
        }
        return $this->acfAdapters[$data['type']];
    }

    public function prepareAcfFieldValues(array $acfFields, array $acfValues): array
    {
        $fieldValues = [];

        foreach ($acfValues as $fieldName => $fieldValue) {
            if ($fieldValue === '' || $fieldValue === null) {
                continue;
            }

            $fieldData = ArrayHelper::firstWhere($acfFields, fn(array $fieldData) => $fieldData['name'] === $fieldName);
            if (!$fieldData) {
                continue;
            }

            if ($fieldData['type'] === 'group') {
                $fieldValues = array_merge(
                    $fieldValues,
                    $this->prepareAcfFieldValues($fieldData['sub_fields'], is_array($fieldValue) ? $fieldValue : []),
                );
            } else {
                $handle = $this->normalizeAcfFieldHandle($fieldName);
                $fieldValue = $this->acfAdapter($fieldData)->normalizeValue($fieldValue, $fieldData);
                $fieldValues[$handle] = $fieldValue;
            }
        }

        return $fieldValues;
    }

    private function normalizeAcfFieldHandle(string $handle): string
    {
        $handle = StringHelper::toHandle($handle);
        /** @var HandleValidator $validator */
        $validator = Collection::make((new PlainText())->getValidators())
            ->filter(fn(Validator $validator) => $validator instanceof HandleValidator)
            ->first();

        if (in_array($handle, [
            'author',
            'authorId',
            'authorIds',
            'authors',
            'group',
            'section',
            'sectionId',
            'type',
            ...$validator->reservedWords,
        ])) {
            return "{$handle}_acf";
        }

        return $handle;
    }

    private function loadImporters(): void
    {
        $taxonomies = $postTypes = null;

        $this->do('Loading taxonomies', function() use (&$taxonomies) {
            $taxonomies = Collection::make($this->get("$this->apiUrl/wp/v2/taxonomies", [
                'context' => 'edit',
            ]))
                ->filter(fn($data, $key) => (
                    !in_array($key, ['post_tag', 'nav_menu']) &&
                    !str_starts_with($key, 'wp_')
                ))
                ->all();
        });

        $this->do('Loading post types', function() use (&$postTypes) {
            $postTypes = Collection::make($this->get("$this->apiUrl/wp/v2/types", [
                'context' => 'edit',
            ]))
                ->filter(fn($data, $key) => (
                    !in_array($key, ['attachment', 'nav_menu_item']) &&
                    !str_starts_with($key, 'wp_') &&
                    !str_starts_with($key, 'jp_')
                ))
                ->all();
        });

        $this->importers = ArrayHelper::index([
            ...$this->loadComponents(
                'importers',
                self::EVENT_REGISTER_IMPORTERS,
                fn(string $class) => !is_subclass_of($class, BaseConfigurableImporter::class),
            ),
            ...array_map(fn(array $data) => new Taxonomy($data, $this), $taxonomies),
            ...array_map(fn(array $data) => new PostType($data, $this), $postTypes),
        ], fn(BaseImporter $importer) => $importer->slug());
    }

    private function loadBlockTransformers(): void
    {
        $this->blockTransformers = ArrayHelper::index(
            $this->loadComponents('blocktransformers', self::EVENT_REGISTER_BLOCK_TRANSFORMERS),
            fn(BaseBlockTransformer $transformer) => $transformer::blockName(),
        );
    }

    private function loadAcfAdapters(): void
    {
        $this->acfAdapters = ArrayHelper::index(
            $this->loadComponents('acfadapters', self::EVENT_REGISTER_ACF_ADAPTERS),
            fn(BaseAcfAdapter $adapter) => $adapter::type(),
        );
    }

    private function loadComponents(string $dir, ?string $eventName = null, ?callable $filter = null): array
    {
        $types = array_map(
            fn(string $file) => sprintf('craft\\wpimport\\%s\\%s', $dir, pathinfo($file, PATHINFO_FILENAME)),
            FileHelper::findFiles(__DIR__ . "/$dir")
        );

        // Load any custom components from config/wp-import/
        $dir = Craft::$app->path->getConfigPath() . "/wp-import/$dir";
        if (is_dir($dir)) {
            $files = FileHelper::findFiles($dir);
            foreach ($files as $file) {
                $class = sprintf('craft\\wpimport\\%s\\%s', $dir, pathinfo($file, PATHINFO_FILENAME));
                if (!class_exists($class, false)) {
                    require $file;
                }
                $types[] = $class;
            }
        }

        if ($eventName && $this->hasEventHandlers($eventName)) {
            $event = new RegisterComponentTypesEvent([
                'types' => $types,
            ]);
            $this->trigger($eventName, $event);
            $types = $event->types;
        }

        $components = [];
        foreach ($types as $class) {
            if (!$filter || $filter($class)) {
                $components[] = new $class($this);
            }
        }
        return $components;
    }

    private function totalItems(string $resource): int
    {
        $importer = $this->importers[$resource];
        $response = $this->client->get("$this->apiUrl/{$importer->apiUri()}", [
            RequestOptions::AUTH => [$this->username, $this->password],
            RequestOptions::QUERY => $this->resourceQueryParams($resource),
        ]);
        return (int)$response->getHeaderLine('X-WP-Total');
    }

    private function systemCheck(): bool
    {
        if (!Craft::$app->getIsInstalled()) {
            $this->output('Craft isn’t installed yet.', Console::FG_RED);
            return false;
        }

        if (!Craft::$app->plugins->isPluginInstalled('ckeditor')) {
            $this->warning($this->markdownToAnsi(<<<MD
Before we begin, the CKEditor plugin must be installed.
Run the following command to install it:

    composer require craftcms/ckeditor
MD) . "\n\n");
            return false;
        }

        if (!Craft::$app->plugins->isPluginEnabled('ckeditor')) {
            $this->note("The CKEditor plugin must be enabled before we can continue.\n\n");
            if (!$this->confirm('Enable it now?', true)) {
                return false;
            }
            Craft::$app->plugins->enablePlugin('ckeditor');
            $this->stdout("\n");
        }

        if ($this->interactive && (empty($this->type) || in_array(Comment::SLUG, $this->type))) {
            if (!Craft::$app->plugins->isPluginInstalled('comments')) {
                $this->note($this->markdownToAnsi(<<<MD
The Comments plugin (by Verbb) must be installed if you wish to import comments.
Run the following command to install it:

    composer require verbb/comments
MD) . "\n\n");

                if (!$this->confirm('Continue without comments?', true)) {
                    $this->stdout("Aborting\n\n");
                    return false;
                }
            } elseif (!Craft::$app->plugins->isPluginEnabled('comments')) {
                $this->note("The Comments plugin (by Verbb) must be enabled if you wish to import comments.\n\n");
                if ($this->confirm('Enable it now?', true)) {
                    Craft::$app->plugins->enablePlugin('comments');
                }
                $this->stdout("\n");
            }
        }

        $this->importComments = Craft::$app->plugins->isPluginEnabled('comments');
        return true;
    }

    private function captureApiInfo(): void
    {
        if (isset($this->apiUrl)) {
            $this->apiUrl = $this->normalizeApiUrl($this->apiUrl);
        } else {
            $this->apiUrl = $this->normalizeApiUrl($this->prompt('WordPress site URL:', [
                'required' => true,
                'validator' => function($value, &$error) {
                    $value = $this->normalizeApiUrl($value);
                    if (!(new UrlValidator())->validate($value, $error)) {
                        return false;
                    }

                    try {
                        $this->get("$value/craftcms/v1/ping");
                    } catch (Throwable $e) {
                        if ($e instanceof ClientException && $e->getResponse()->getStatusCode() === 404) {
                            $error = $this->markdownToAnsi('The `wp-import Helper` WordPress plugin doesn’t appear to be installed.');
                        } else {
                            $error = $e->getMessage();
                        }
                        return false;
                    }

                    return true;
                },
            ]));
        }

        if (!isset($this->username) || !isset($this->password)) {
            getCredentials:
            if (!isset($this->username)) {
                $this->username = $this->prompt('Administrator username:', [
                    'required' => true,
                ]);
            }
            if (!isset($this->password)) {
                $this->password = $this->passwordPrompt([
                    'label' => "Application password for $this->username:",
                    'confirm' => false,
                ]);
            }
            $this->stdout("\n");

            try {
                $this->do('Verifying credentials', function() {
                    $response = $this->client->get("$this->apiUrl/wp/v2/posts", [
                        RequestOptions::AUTH => [$this->username, $this->password],
                        RequestOptions::QUERY => ['context' => 'edit'],
                    ]);
                    if ($response->getStatusCode() !== 200) {
                        throw new Exception('Invalid credentials.');
                    }
                });
            } catch (Throwable) {
                goto getCredentials;
            }
        }

        $this->wpInfo = $this->get("$this->apiUrl/craftcms/v1/info");
    }

    private function normalizeApiUrl(string $url): string
    {
        $url = StringHelper::removeRight($url, '/');
        $url = StringHelper::removeRight($url, '/v2');
        $url = StringHelper::removeRight($url, '/wp');
        $url = StringHelper::removeRight($url, '/wp-admin');
        $url = StringHelper::ensureRight($url, '/wp-json');
        return $url;
    }

    private function editionCheck(): void
    {
        if (!$this->interactive) {
            return;
        }

        // We ignore Team's 5 user limit for the import
        // (not worth the effort/friction to map user accounts up front)
        if (Craft::$app->edition !== CmsEdition::Solo) {
            return;
        }

        $totalWpUsers = $this->totalItems(UserImporter::SLUG);
        if ($totalWpUsers === 1) {
            return;
        }

        $this->warning(sprintf(<<<MD
Craft Solo is limited to one user account, but your WordPress install has %s users.
All WordPress posts will be assigned to that one account, unless you upgrade to the
Team or Pro edition.
MD, Craft::$app->formatter->asInteger($totalWpUsers)));

        if (!$this->confirm('Upgrade your Craft edition?')) {
            return;
        }

        $newEdition = CmsEdition::fromHandle($this->select('New edition:', [
            CmsEdition::Team->handle() => sprintf('Team (%s users)', Craft::$app->users->getMaxUsers(CmsEdition::Team)),
            CmsEdition::Pro->handle() => 'Pro (unlimited users)',
        ]));

        $this->do('Upgrading the Craft edition', function() use ($newEdition) {
            Craft::$app->setEdition($newEdition);
        });
    }

    /**
     * @template T of FieldInterface
     * @param string $uid
     * @param string $name
     * @param string $handle
     * @param string $type
     * @phpstan-param class-string<T> $type
     * @param callable|null $populate
     * @return T
     */
    private function field(
        string $uid,
        string $name,
        string $handle,
        string $type,
        ?callable $populate = null,
    ): FieldInterface {
        $field = null;

        /** @var string|FieldInterface $type */
        $this->do(
            sprintf('Creating `%s` %s field', $name, $type::displayName()),
            function() use ($uid, $name, $handle, $type, $populate, &$field) {
                $field = Craft::$app->fields->getFieldByUid($uid);
                if ($field) {
                    return;
                }

                $handleTaken = Craft::$app->fields->getFieldByHandle($handle) !== null;
                $field = new $type();
                $field->uid = $uid;
                $field->name = $name;
                $field->handle = $handle . ($handleTaken ? '_' . StringHelper::randomString(5) : '');
                if ($populate) {
                    $populate($field);
                }

                if (!Craft::$app->fields->saveField($field)) {
                    throw new Exception(implode(', ', $field->getFirstErrors()));
                }
            },
        );

        return $field;
    }

    private function entryType(
        string $uid,
        string $name,
        string $handle,
        callable $populate,
    ): EntryType {
        $entryType = null;

        $this->do(
            "Creating `$name` entry type",
            function() use ($uid, $name, $handle, $populate, &$entryType) {
                $entryType = Craft::$app->entries->getEntryTypeByUid($uid);
                if ($entryType) {
                    return;
                }

                $handleTaken = Craft::$app->entries->getEntryTypeByHandle($handle) !== null;
                $entryType = new EntryType();
                $entryType->uid = $uid;
                $entryType->name = $name;
                $entryType->handle = $handle . ($handleTaken ? '_' . StringHelper::randomString(5) : '');
                $populate($entryType);

                if (!Craft::$app->entries->saveEntryType($entryType)) {
                    throw new Exception(implode(', ', $entryType->getFirstErrors()));
                }
            },
        );

        return $entryType;
    }

    private function section(
        string $uid,
        string $name,
        string $handle,
        callable $populate,
    ): Section {
        $section = null;

        $this->do(
            "Creating `$name` section",
            function() use ($uid, $name, $handle, $populate, &$section) {
                $section = Craft::$app->entries->getSectionByUid($uid);
                if ($section) {
                    return;
                }

                $handleTaken = Craft::$app->entries->getSectionByHandle($handle) !== null;
                $section = new Section();
                $section->uid = $uid;
                $section->name = $name;
                $section->handle = $handle . ($handleTaken ? '_' . StringHelper::randomString(5) : '');
                $populate($section);

                if (!Craft::$app->entries->saveSection($section)) {
                    throw new Exception(implode(', ', $section->getFirstErrors()));
                }
            },
        );

        return $section;
    }

    private function categoryGroup(
        string $uid,
        string $name,
        string $handle,
        callable $populate,
    ): CategoryGroup {
        $categoryGroup = null;

        $this->do(
            "Creating `$name` category group",
            function() use ($uid, $name, $handle, $populate, &$categoryGroup) {
                $categoryGroup = Craft::$app->categories->getGroupByUid($uid);
                if ($categoryGroup) {
                    return;
                }

                $handleTaken = Craft::$app->categories->getGroupByHandle($handle) !== null;
                $categoryGroup = new CategoryGroup();
                $categoryGroup->uid = $uid;
                $categoryGroup->name = $name;
                $categoryGroup->handle = $handle . ($handleTaken ? '_' . StringHelper::randomString(5) : '');
                $populate($categoryGroup);

                if (!Craft::$app->categories->saveGroup($categoryGroup)) {
                    throw new Exception(implode(', ', $categoryGroup->getFirstErrors()));
                }
            },
        );

        return $categoryGroup;
    }

    private function tagGroup(
        string $uid,
        string $name,
        string $handle,
        ?callable $populate = null,
    ): TagGroup {
        $tagGroup = null;

        $this->do(
            "Creating `$name` tag group",
            function() use ($uid, $name, $handle, $populate, &$tagGroup) {
                $tagGroup = Craft::$app->tags->getTagGroupByUid($uid);
                if ($tagGroup) {
                    return;
                }

                $handleTaken = Craft::$app->tags->getTagGroupByHandle($handle) !== null;
                $tagGroup = new TagGroup();
                $tagGroup->uid = $uid;
                $tagGroup->name = $name;
                $tagGroup->handle = $handle . ($handleTaken ? '_' . StringHelper::randomString(5) : '');
                if ($populate) {
                    $populate($tagGroup);
                }

                if (!Craft::$app->tags->saveTagGroup($tagGroup)) {
                    throw new Exception(implode(', ', $tagGroup->getFirstErrors()));
                }
            },
        );

        return $tagGroup;
    }

    public function import(string $resource, int|array $data, array $queryParams = []): int
    {
        $importer = $this->importers[$resource] ?? null;
        if (!$importer) {
            throw new InvalidArgumentException("Invalid resource: $resource");
        }

        $id = is_int($data) ? $data : $data['id'];

        // Did we already import this item in the same request?
        if (isset($this->idMap[$resource][$id])) {
            return $this->idMap[$resource][$id];
        }

        // If this is the first time we've imported an item of this type,
        // give the importer a chance to prep the system for it
        if (!isset($this->idMap[$resource])) {
            $importer->prep();
        }

        // Already exists?
        /** @var string|ElementInterface $elementType */
        $elementType = $importer->elementType();
        $element = $elementType::find()
            ->{WpId::get()->handle}($id)
            ->status(null)
            ->limit(1)
            ->one();

        if ($element && !$this->update) {
            return $element->id;
        }

        $resourceLabel = Inflector::singularize($resource);
        $name = trim(($data['name'] ?? null) ?: ($data['title']['raw'] ?? null) ?: ($data['slug'] ?? null) ?: '');
        $name = ($name !== '' && $name != $id) ? "`$name` (`$id`)" : "`$id`";

        try {
            $this->do("Importing $resourceLabel $name", function() use (
                $data,
                $id,
                $queryParams,
                $importer,
                $elementType,
                &$element,
            ) {
                Console::indent();
                try {
                    if (is_int($data)) {
                        $data = $this->item($importer->slug(), $data, $queryParams);
                    }

                    $element ??= $importer->find($data) ?? new $elementType();
                    $importer->populate($element, $data);
                    $element->{WpId::get()->handle} = $id;

                    if ($element->getScenario() === Model::SCENARIO_DEFAULT) {
                        $element->setScenario(Element::SCENARIO_ESSENTIALS);
                    }

                    if ($element instanceof User && Craft::$app->edition->value < CmsEdition::Pro->value) {
                        $edition = Craft::$app->edition;
                        Craft::$app->edition = CmsEdition::Pro;
                    }

                    try {
                        if (!Craft::$app->elements->saveElement($element)) {
                            throw new Exception(implode(', ', $element->getFirstErrors()));
                        }

                        $this->importTotal++;
                    } finally {
                        if (isset($edition)) {
                            Craft::$app->edition = $edition;
                        }
                    }
                } finally {
                    Console::outdent();
                }
            });
        } catch (Throwable $e) {
            // UnknownBlockTypeException's have already been captured
            if (!$e instanceof UnknownBlockTypeException && !$e instanceof UnknownAcfFieldTypeException) {
                $e = new ImportException($resource, $id, $e);
                $this->errors[] = $e;
            }
            throw $e;
        }

        return $this->idMap[$resource][$id] = $element->id;
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

    public function isSupported(string $resource, ?string &$reason = null): bool
    {
        return $this->importers[$resource]->supported($reason);
    }

    public function items(string $resource, array $queryParams = []): Generator
    {
        $page = $this->page ?? 1;
        $importer = $this->importers[$resource];
        do {
            $body = $this->get(
                "$this->apiUrl/{$importer->apiUri()}",
                array_merge($this->resourceQueryParams($resource), [
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

    public function item(string $resource, int $id, array $queryParams = []): array
    {
        $importer = $this->importers[$resource];
        return $this->get(
            "$this->apiUrl/{$importer->apiUri()}/$id",
            array_merge($this->resourceQueryParams($resource), $queryParams),
        );
    }

    private function resourceQueryParams(string $resource): array
    {
        return array_merge([
            'context' => 'edit',
        ], $this->importers[$resource]->queryParams());
    }

    public function get(string $uri, array $queryParams = [], ?ResponseInterface &$response = null): array
    {
        try {
            $response = $this->client->get($uri, [
                RequestOptions::AUTH => [$this->username, $this->password],
                RequestOptions::QUERY => $queryParams,
            ]);
        } catch (RequestException $e) {
            if ($e->getResponse()->getStatusCode() === 400) {
                try {
                    $body = $this->decodeBody((string)$e->getResponse()->getBody());
                } catch (InvalidArgumentException) {
                    throw $e;
                }
                if (($body['code'] ?? null) === 'rest_post_invalid_page_number') {
                    return [];
                }
            }
            throw $e;
        }

        return $this->decodeBody((string)$response->getBody());
    }

    private function decodeBody(string $body): array
    {
        try {
            return Json::decode($body);
        } catch (InvalidArgumentException $e) {
            // Skip any PHP warnings at the top
            if (
                !preg_match('/^[\[{]/m', $body, $matches, PREG_OFFSET_CAPTURE) ||
                $matches[0][1] === 0
            ) {
                throw $e;
            }
            return Json::decode(substr($body, $matches[0][1]));
        }
    }

    public function outputSummary(): void
    {
        if (!$this->showSummary) {
            return;
        }

        $dryRunLabel = $this->dryRun ? '**[DRY RUN]** ' : '';
        $importMessage = sprintf('Imported %s successfully', $this->importTotal === 1 ? '1 item' : "$this->importTotal items");

        if (empty($this->errors)) {
            $this->success($dryRunLabel . $importMessage);
        } else {
            $report = '';
            $hr = sprintf("%s\n", str_repeat('-', 80));

            if (!empty($this->unknownBlockTypes)) {
                $report .= "The following unknown block types were encountered:\n";
                foreach (array_keys($this->unknownBlockTypes) as $type) {
                    $report .= " - $type\n";
                }
                $report .= "$hr";
            }

            if (!empty($this->unknownAcfFieldTypes)) {
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
                if ($e instanceof UnknownBlockTypeException) {
                    $report .= sprintf(
                        "Block data:\n%s\n",
                        Json::encode($e->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    );
                } elseif ($e instanceof UnknownAcfFieldTypeException) {
                    $report .= sprintf(
                        "Field data:\n%s\n",
                        Json::encode($e->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    );
                } else {
                    if ($e instanceof ImportException) {
                        $report .= "Resource: $e->resource\n";
                        $report .= "Item ID: $e->itemId\n";
                        $e = $e->getPrevious();
                    }
                    $report .= sprintf("Location: %s\n", implode(':', array_filter([$e->getFile(), $e->getLine()])));
                    $report .= sprintf("Trace:\n%s\n", $e->getTraceAsString());
                }
            }

            $reportPath = Craft::getAlias('@root/wp-import-errors.txt');
            FileHelper::writeToFile($reportPath, $report);

            $totalErrors = count($this->errors);
            $errorMessage = sprintf('%s encountered.', $totalErrors === 1 ? '1 error was' : "$totalErrors errors were");
            if ($this->importTotal) {
                $errorMessage = "$importMessage, but $errorMessage";
            }
            $errorMessage .= "\nError details have been logged to `$reportPath`.";
            $this->failure($dryRunLabel . $errorMessage);
        }
    }

    public function normalizeColor(?string $color): ?string
    {
        if (!$color) {
            return null;
        }

        if ((new ColorValidator())->validate($color)) {
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
