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
use craft\wpimport\errors\ImportException;
use craft\wpimport\errors\UnknownBlockTypeException;
use craft\wpimport\generators\fields\WpId;
use craft\wpimport\importers\Category;
use craft\wpimport\importers\Comment as CommentImporter;
use craft\wpimport\importers\Media;
use craft\wpimport\importers\Page;
use craft\wpimport\importers\Post;
use craft\wpimport\importers\Tag;
use craft\wpimport\importers\User as UserImporter;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use yii\base\Action;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\console\Exception;
use yii\console\ExitCode;
use yii\helpers\Inflector;
use yii\validators\UrlValidator;

/**
 * Imports content from WordPress.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Command extends Controller
{
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
     * @inheritdoc
     */
    public $defaultAction = 'all';

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
    private array $importers;
    /**
     * @var BaseBlockTransformer[]
     */
    private array $blockTransformers;
    public bool $importComments;

    public Client $client;
    public array $wpSettings;
    private array $idMap = [];
    private int $importTotal = 0;

    /**
     * @var array<string,bool>
     */
    public array $unknownBlockTypes = [];
    /**
     * @var Throwable[]
     */
    public array $errors = [];

    public function init(): void
    {
        $this->loadImporters();
        $this->loadBlockTransformers();
        parent::init();
    }

    public function options($actionID): array
    {
        $options = array_merge(parent::options($actionID), [
            'apiUrl',
            'username',
            'password',
            'page',
            'perPage',
            'update',
            'dryRun',
            'failFast',
            'showSummary',
        ]);

        if ($actionID !== 'all') {
            $options[] = 'itemId';
        }

        return $options;
    }

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (!$this->systemCheck($action)) {
            return false;
        }

        $this->client = Craft::createGuzzleClient([
            RequestOptions::VERIFY => false,
        ]);

        $this->captureApiInfo();
        $this->editionCheck();

        return true;
    }

    /**
     * Imports all items from WordPress.
     *
     * @return int
     */
    public function actionAll(): int
    {
        $resources = [
            'Users' => UserImporter::resource(),
            'Media' => Media::resource(),
            'Categories' => Category::resource(),
            'Tags' => Tag::resource(),
            'Posts' => Post::resource(),
            'Pages' => Page::resource(),
            'Comments' => CommentImporter::resource(),
        ];
        $resources = array_filter($resources, fn(string $resource) => $this->isSupported($resource));
        $totals = [];

        if ($this->interactive) {
            $this->do('Fetching info', function() use ($resources, &$totals) {
                foreach ($resources as $label => $resource) {
                    $totals[] = [
                        $label,
                        [Craft::$app->formatter->asInteger($this->totalItems($resource)), 'align' => 'right'],
                    ];
                }
            });

            $this->stdout("\n");
            $this->table(['Resource', 'Total'], $totals);
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

        $dryRun = $this->dryRun;
        $showSummary = $this->showSummary;
        $interactive = $this->interactive;

        try {
            foreach ($resources as $resource) {
                $this->runAction($resource, [
                    'dryRun' => false,
                    'showSummary' => false,
                    'interactive' => false,
                ]);
            }
        } finally {
            if (isset($transaction)) {
                $transaction->rollBack();
            }

            $this->dryRun = $dryRun;
            $this->showSummary = $showSummary;
            $this->interactive = $interactive;
        }

        $this->outputSummary();
        return ExitCode::OK;
    }

    protected function defineActions(): array
    {
        $actions = parent::defineActions();

        foreach ($this->importers as $resource => $importer) {
            $actions[$resource] = [
                'helpSummary' => "Imports WordPress $resource.",
                'action' => [
                    'class' => ImportAction::class,
                    'resource' => $resource,
                ],
            ];
        }

        return $actions;
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

    private function loadImporters(): void
    {
        $this->importers = [];

        /** @var class-string<BaseImporter>[] $types */
        $types = array_map(
            fn(string $file) => sprintf('craft\\wpimport\\importers\\%s', pathinfo($file, PATHINFO_FILENAME)),
            FileHelper::findFiles(__DIR__ . '/importers')
        );

        foreach ($types as $class) {
            /** @var BaseImporter $importer */
            $importer = new $class($this);
            $this->importers[$importer::resource()] = $importer;
        }
    }

    private function loadBlockTransformers(): void
    {
        $this->blockTransformers = [];

        /** @var class-string<BaseBlockTransformer>[] $types */
        $types = array_map(
            fn(string $file) => sprintf('craft\\wpimport\\blocktransformers\\%s', pathinfo($file, PATHINFO_FILENAME)),
            FileHelper::findFiles(__DIR__ . '/blocktransformers')
        );

        // Load any custom transformers from config/blocktransformers/
        $dir = Craft::$app->path->getConfigPath() . '/blocktransformers';
        if (is_dir($dir)) {
            $files = FileHelper::findFiles($dir);
            foreach ($files as $file) {
                $class = sprintf('craft\\wpimport\\blocktransformers\\%s', pathinfo($file, PATHINFO_FILENAME));
                if (!class_exists($class, false)) {
                    require $file;
                }
                $types[] = $class;
            }
        }

        $event = new RegisterComponentTypesEvent([
            'types' => $types,
        ]);
        $this->trigger(self::EVENT_REGISTER_BLOCK_TRANSFORMERS, $event);

        foreach ($types as $class) {
            /** @var BaseBlockTransformer $transformer */
            $transformer = new $class($this);
            $this->blockTransformers[$transformer::blockName()] = $transformer;
        }
    }

    private function totalItems(string $resource): int
    {
        $response = $this->client->get("$this->apiUrl/wp/v2/$resource", [
            RequestOptions::AUTH => [$this->username, $this->password],
            RequestOptions::QUERY => $this->resourceQueryParams($resource),
        ]);
        return (int)$response->getHeaderLine('X-WP-Total');
    }

    private function systemCheck(Action $action): bool
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

        if ($this->interactive && in_array($action->id, ['all', 'comments'])) {
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
            $this->wpSettings = $this->get("$this->apiUrl/craftcms/v1/settings");
        } else {
            $this->apiUrl = $this->normalizeApiUrl($this->prompt('REST API URL:', [
                'required' => true,
                'validator' => function($value, &$error) {
                    $value = $this->normalizeApiUrl($value);
                    if (!(new UrlValidator())->validate($value, $error)) {
                        return false;
                    }

                    try {
                        $this->wpSettings = $this->get("$value/craftcms/v1/settings");
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
    }

    private function normalizeApiUrl(string $url): string
    {
        $url = StringHelper::removeRight($url, '/');
        $url = StringHelper::removeRight($url, '/v2');
        $url = StringHelper::removeRight($url, '/wp');
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

        $totalWpUsers = $this->totalItems(UserImporter::resource());
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

    private function fieldLayout(array $elements = []): FieldLayout
    {
        $fieldLayout = new FieldLayout();
        $fieldLayout->setTabs([
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Content',
                'elements' => $elements,
            ]),
        ]);
        return $fieldLayout;
    }

    private function assignFieldToEntryType(FieldInterface $field, EntryType $entryType): void
    {
        $fieldLayout = $entryType->getFieldLayout();
        if (!$fieldLayout->getFieldById($field->id)) {
            $tab = $fieldLayout->getTabs()[0] ?? null;
            if (!$tab) {
                $tab = new FieldLayoutTab([
                    'name' => 'Content',
                    'layout' => $fieldLayout,
                ]);
                $fieldLayout->setTabs([$tab]);
            }

            $elements = $tab->getElements();
            $elements[] = new CustomField($field);
            $tab->setElements($elements);
            Craft::$app->entries->saveEntryType($entryType);
        }
    }

    /**
     * @param string $resource
     * @param int|array $data
     * @param array $queryParams
     * @return int
     */
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
        $elementType = $importer::elementType();
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
                        $data = $this->item($importer::resource(), $data, $queryParams);
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
            if (!$e instanceof UnknownBlockTypeException) {
                $e = new ImportException($resource, $id, $e);
                $this->errors[] = $e;
            }
            throw $e;
        }

        return $this->idMap[$resource][$id] = $element->id;
    }

    public function isSupported(string $resource, ?string &$reason = null): bool
    {
        return $this->importers[$resource]->supported($reason);
    }

    public function items(string $resource, array $queryParams = []): Generator
    {
        $page = $this->page ?? 1;
        do {
            $body = $this->get(
                "$this->apiUrl/wp/v2/$resource",
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
        return $this->get(
            "$this->apiUrl/wp/v2/$resource/$id",
            array_merge($this->resourceQueryParams($resource), $queryParams),
        );
    }

    private function resourceQueryParams(string $resource): array
    {
        return array_merge([
            'context' => 'edit',
        ], $this->importers[$resource]::queryParams());
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
                throw $e;
            }
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

        foreach ($this->wpSettings['color_palette'] as $palette) {
            foreach ($palette as $paletteColor) {
                if ($paletteColor['slug'] === $color) {
                    return $paletteColor['color'];
                }
            }
        }

        return null;
    }
}
