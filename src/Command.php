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
use craft\ckeditor\CkeConfig;
use craft\ckeditor\Field as CkeditorField;
use craft\ckeditor\Plugin as Ckeditor;
use craft\console\Controller;
use craft\elements\Entry;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\enums\Color as ColorEnum;
use craft\events\RegisterComponentTypesEvent;
use craft\fieldlayoutelements\assets\AltField;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fieldlayoutelements\HorizontalRule;
use craft\fields\Assets as AssetsField;
use craft\fields\Categories;
use craft\fields\Color;
use craft\fields\Dropdown;
use craft\fields\Lightswitch;
use craft\fields\Link;
use craft\fields\linktypes\Url;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\Tags;
use craft\fs\Local;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Site;
use craft\models\TagGroup;
use craft\models\Volume;
use craft\validators\ColorValidator;
use craft\wpimport\importers\Category;
use craft\wpimport\importers\Comment as CommentImporter;
use craft\wpimport\importers\Media;
use craft\wpimport\importers\Page;
use craft\wpimport\importers\Post;
use craft\wpimport\importers\Tag;
use craft\wpimport\importers\User as UserImporter;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use verbb\comments\elements\Comment;
use verbb\comments\fields\CommentsField;
use verbb\comments\services\Comments as CommentsService;
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
    private const WP_ID_FIELD_UUID = '4d06012a-2d8a-4e92-bf6b-de74bdd1be0b';
    private const CAPTION_FIELD_UUID = '4c8edf71-ef0c-4ba3-ba4e-cf0ef5ac36ea';
    private const DESCRIPTION_FIELD_UUID = 'b636e420-0741-4bd3-b8b2-89a8d50806f9';
    private const MEDIA_VOLUME_UUID = 'ba83893c-c85d-4d01-81a2-0dfc8723fb5d';
    private const MEDIA_FIELD_UUID = 'a553e2eb-f7c3-4f3e-a07e-27f4c94cec86';
    private const MEDIA_ENTRY_TYPE_UUID = 'd2356f61-430b-423e-a52e-a167fc52ba47';
    private const LINK_URL_FIELD_UUID = '22e94a09-0f7c-4a1f-9981-7fc0605d83fa';
    private const BUTTON_ENTRY_TYPE_UUID = 'fc8630bf-cc94-4830-8e52-40246afab5e5';
    private const CODEPEN_ENTRY_TYPE_UUID = 'ff0c2e4b-8cfb-4e47-9216-751983be5496';
    private const VIDEO_ENTRY_TYPE_UUID = 'c34c148c-8e45-4867-b6c2-1ad69ba00f5e';
    private const SUMMARY_FIELD_UUID = 'dee725db-134a-40f3-bd54-199353365c57';
    private const DETAILS_ENTRY_TYPE_UUID = '354a145a-dc30-40c6-abe4-35c0fb93abeb';
    private const COLOR_FIELD_UUID = 'ec2af108-def7-419e-b9fd-53f0d4289b0d';
    private const GROUP_ENTRY_TYPE_UUID = '36ca6464-18ad-4f2a-9ae0-e8da4e00c94a';
    private const CKE_CONFIG_UUID = '7261a233-a194-4374-8a55-91770cef7528';
    private const POST_CONTENT_FIELD_UUID = 'e314b2cd-4ad3-4d8c-94d8-347ddb16245d';
    private const CATEGORY_GROUP_UUID = '89d90c8f-05eb-4b35-970f-a1568dd6713b';
    private const CATEGORIES_FIELD_UUID = '087a4919-1550-46cc-95e8-e492b43334ee';
    private const TAG_GROUP_UUID = '677c2b2b-14a7-4692-8bb8-b4dc667e1a12';
    private const TAGS_FIELD_UUID = '5a524179-b073-4ec2-a219-674143dbc455';
    private const TEMPLATE_FIELD_UUID = '7e2aabf9-48f2-4671-b232-546499a18d43';
    private const COMMENTS_FIELD_UUID = 'c23bcc94-9f9b-42ae-aca6-b5afb8be5e86';
    private const FORMAT_FIELD_UUID = 'd958a069-fc64-431a-91bc-3141612304be';
    private const STICKY_FIELD_UUID = '79559275-ab81-4420-abf1-1c848a0a68d6';
    private const POST_ENTRY_TYPE_UUID = 'b701559d-352a-4a55-80af-30d65b624292';
    private const POSTS_SECTION_UUID = '3358a830-9ada-487d-a170-01751e921710';
    private const PAGE_ENTRY_TYPE_UUID = '9ca3795a-ec88-4bec-a4e0-9839113a6c48';
    private const PAGES_SECTION_UUID = 'b5e87e4c-f83a-40a6-bf98-9db86b6383fb';

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
     * @event Event The event that is triggered when preparing for an import.
     */
    public const EVENT_PREP = 'prep';

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
     * @var int[]|null The item ID(s) to import
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
     * @var bool Whether to reimport items that have already been imported
     */
    public bool $update = false;

    /**
     * @var bool Whether to prep the system for import.
     */
    public bool $prep = true;

    /**
     * @var bool Whether to abort the import on the first error encountered
     */
    public bool $failFast = false;

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
    private array $idMap = [];

    public Number $wpIdField;
    public PlainText $captionField;
    public PlainText $descriptionField;
    public Local $mediaFs;
    public Volume $mediaVolume;
    public AssetsField $mediaField;
    public EntryType $mediaEntryType;
    public Link $linkUrlField;
    public EntryType $buttonEntryType;
    public EntryType $codepenEntryType;
    public EntryType $videoEntryType;
    public PlainText $summaryField;
    public EntryType $detailsEntryType;
    public Color $colorField;
    public EntryType $groupEntryType;
    public CkeditorField $postContentField;
    public CategoryGroup $categoryGroup;
    public Categories $categoriesField;
    public TagGroup $tagGroup;
    public Tags $tagsField;
    public PlainText $templateField;
    public CommentsField $commentsField;
    public Dropdown $formatField;
    public Lightswitch $stickyField;
    public EntryType $postEntryType;
    public Section $postsSection;
    public EntryType $pageEntryType;
    public Section $pagesSection;

    public array $userIds = [];

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
            'prep',
            'failFast',
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

        $this->prep();

        foreach ($resources as $resource) {
            $this->runAction($resource, [
                'apiUrl' => $this->apiUrl,
                'username' => $this->username,
                'password' => $this->password,
                'page' => $this->page,
                'perPage' => $this->perPage,
                'update' => $this->update,
                'failFast' => $this->failFast,
                'prep' => false,
                'interactive' => false,
            ]);
        }

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

    public function prep(): void
    {
        if (!$this->prep) {
            return;
        }

        $this->do('Preparing content model', function() {
            Console::indent();
            try {
                $this->createWpIdField();
                $this->createCaptionField();
                $this->createDescriptionField();
                $this->createMediaFs();
                $this->createMediaVolume();
                $this->createMediaField();
                $this->createMediaEntryType();
                $this->createLinkUrlField();
                $this->createButtonEntryType();
                $this->createCodepenEntryType();
                $this->createVideoEntryType();
                $this->createSummaryField();
                $this->createDetailsEntryType();
                $this->createColorField();
                $this->createGroupEntryType();
                $this->createCkeConfig();
                $this->createPostContentField();
                $this->createCategoryGroup();
                $this->createCategoriesField();
                $this->createTagGroup();
                $this->createTagsField();
                $this->createTemplateField();
                $this->createCommentsField();
                $this->createFormatField();
                $this->createStickyField();
                $this->createPostEntryType();
                $this->createPostsSection();
                $this->createPageEntryType();
                $this->createPagesSection();
                $this->updateUserLayout();
                $this->updateCommentLayout();

                // assign the Content field to the Group and Details entry types, now that they all exist
                $this->assignFieldToEntryType($this->postContentField, $this->groupEntryType);
                $this->assignFieldToEntryType($this->postContentField, $this->detailsEntryType);

                $this->trigger(self::EVENT_PREP);
            } finally {
                Console::outdent();
            }
        });
    }

    public function renderBlocks(array $blocks, Entry $entry): string
    {
        $html = '';
        foreach ($blocks as $block) {
            $html .= $this->renderBlock($block, $entry);
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
            throw new Exception("Unknown block type: $block[blockName]");
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
        $response = $this->client->get("$this->apiUrl/$resource", [
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
        if (!isset($this->apiUrl)) {
            $this->apiUrl = $this->prompt('REST API URL:', [
                'required' => true,
                'validator' => function($value, &$error) {
                    $value = $this->normalizeApiUrl($value);
                    if (!(new UrlValidator())->validate($value, $error)) {
                        return false;
                    }

                    try {
                        $body = $this->get($value);
                    } catch (Throwable $e) {
                        $error = $e->getMessage();
                        return false;
                    }

                    if (!isset($body['routes']['/wp/v2/posts']['endpoints'][1]['args']['content_parsed'])) {
                        $error = 'The “Parse Blocks” WordPress plugin doesn’t appear to be installed.';
                        return false;
                    }

                    return true;
                },
            ]);
        }

        $this->apiUrl = $this->normalizeApiUrl($this->apiUrl);

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
                    $response = $this->client->get("$this->apiUrl/posts", [
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
        $url = StringHelper::ensureRight($url, '/v2');
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

    private function createWpIdField(): void
    {
        $this->wpIdField = $this->field(
            self::WP_ID_FIELD_UUID,
            'WordPress ID',
            'wpId',
            Number::class, function(Number $field) {
                $field->min = 1;
                $field->searchable = true;
            },
        );
    }

    private function createCaptionField(): void
    {
        $this->captionField = $this->field(
            self::CAPTION_FIELD_UUID,
            'Caption',
            'caption',
            PlainText::class,
            function(PlainText $field) {
                $field->multiline = true;
                $field->searchable = true;
            }
        );
    }

    private function createDescriptionField(): void
    {
        $this->descriptionField = $this->field(
            self::DESCRIPTION_FIELD_UUID,
            'Description',
            'description',
            PlainText::class,
            function(PlainText $field) {
                $field->code = true;
                $field->multiline = true;
                $field->searchable = true;
            },
        );
    }

    private function createMediaFs(): void
    {
        $this->do('Creating `Uploads` filesystem', function() {
            $fs = Craft::$app->fs->getFilesystemByHandle('uploads');
            if ($fs) {
                if (!$fs instanceof Local) {
                    throw new Exception(sprintf('Filesystem “%s” is no longer a Local filesystem.', $fs->name));
                }
                $this->mediaFs = $fs;
                return;
            }

            $this->mediaFs = new Local();
            $this->mediaFs->name = 'Uploads';
            $this->mediaFs->handle = 'uploads';
            $this->mediaFs->path = '@webroot/uploads';
            $this->mediaFs->hasUrls = true;
            $this->mediaFs->url = '/uploads';

            if (!Craft::$app->fs->saveFilesystem($this->mediaFs)) {
                throw new Exception(implode(', ', $this->mediaFs->getFirstErrors()));
            }
        });
    }

    private function createMediaVolume(): void
    {
        $this->do('Creating `Uploads` volume', function() {
            $volume = Craft::$app->volumes->getVolumeByUid(self::MEDIA_VOLUME_UUID);
            if ($volume) {
                $this->mediaVolume = $volume;
                return;
            }

            $fieldLayout = new FieldLayout();
            $fieldLayout->setTabs([
                new FieldLayoutTab([
                    'layout' => $fieldLayout,
                    'name' => 'Content',
                    'elements' => [
                        new AltField(),
                        new CustomField($this->captionField),
                        new CustomField($this->descriptionField),
                    ],
                ]),
                new FieldLayoutTab([
                    'layout' => $fieldLayout,
                    'name' => 'WordPress',
                    'elements' => [
                        new CustomField($this->wpIdField),
                    ],
                ]),
            ]);

            $this->mediaVolume = new Volume();
            $this->mediaVolume->uid = self::MEDIA_VOLUME_UUID;
            $this->mediaVolume->name = 'Uploads';
            $this->mediaVolume->handle = 'uploads';
            $this->mediaVolume->setFsHandle('uploads');
            $this->mediaVolume->setFieldLayout($fieldLayout);

            if (!Craft::$app->volumes->saveVolume($this->mediaVolume)) {
                throw new Exception(implode(', ', $this->mediaVolume->getFirstErrors()));
            }
        });
    }

    private function createMediaField(): void
    {
        $this->mediaField = $this->field(
            self::MEDIA_FIELD_UUID,
            'Media',
            'media',
            AssetsField::class,
            function(AssetsField $field) {
                $field->sources = ["volume:{$this->mediaVolume->uid}"];
                $field->viewMode = 'large';
                $field->defaultUploadLocationSource = "volume:{$this->mediaVolume->uid}";
            },
        );
    }

    private function createMediaEntryType(): void
    {
        $this->mediaEntryType = $this->entryType(
            self::MEDIA_ENTRY_TYPE_UUID,
            'Media',
            'media',
            function(EntryType $entryType) {
                $entryType->icon = 'photo-film-music';
                $entryType->color = ColorEnum::Amber;
                $entryType->hasTitleField = false;
                $entryType->showStatusField = false;
                $entryType->showSlugField = false;
                $entryType->setFieldLayout($this->fieldLayout([
                    new CustomField($this->mediaField, [
                        'providesThumbs' => true,
                        'includeInCards' => true,
                    ]),
                ]));
            },
        );
    }

    private function createLinkUrlField(): void
    {
        $this->linkUrlField = $this->field(
            self::LINK_URL_FIELD_UUID,
            'Link URL',
            'linkUrl',
            Link::class,
            function(Link $link) {
                $link->types = [Url::id()];
            },
        );
    }

    private function createButtonEntryType(): void
    {
        $this->buttonEntryType = $this->entryType(
            self::BUTTON_ENTRY_TYPE_UUID,
            'Button',
            'button',
            function(EntryType $entryType) {
                $entryType->icon = 'link';
                $entryType->color = ColorEnum::Cyan;
                $entryType->showStatusField = false;
                $entryType->showSlugField = false;
                $entryType->setFieldLayout($this->fieldLayout([
                    new CustomField($this->linkUrlField, [
                        'handle' => 'buttonUrl',
                        'includeInCards' => true,
                    ]),
                ]));
            },
        );
    }

    private function createCodepenEntryType(): void
    {
        $this->codepenEntryType = $this->entryType(
            self::CODEPEN_ENTRY_TYPE_UUID,
            'CodePen Embed',
            'codepenEmbed',
            function(EntryType $entryType) {
                $entryType->icon = 'codepen';
                $entryType->color = ColorEnum::Emerald;
                $entryType->hasTitleField = false;
                $entryType->showStatusField = false;
                $entryType->showSlugField = false;
                $entryType->setFieldLayout($this->fieldLayout([
                    new CustomField($this->linkUrlField, [
                        'label' => 'Pen URL',
                        'handle' => 'penUrl',
                        'includeInCards' => true,
                    ]),
                ]));
            },
        );
    }

    private function createVideoEntryType(): void
    {
        $this->videoEntryType = $this->entryType(
            self::VIDEO_ENTRY_TYPE_UUID,
            'Video',
            'video',
            function(EntryType $entryType) {
                $entryType->icon = 'youtube';
                $entryType->color = ColorEnum::Red;
                $entryType->hasTitleField = false;
                $entryType->showStatusField = false;
                $entryType->showSlugField = false;
                $entryType->setFieldLayout($this->fieldLayout([
                    new CustomField($this->linkUrlField, [
                        'handle' => 'videoUrl',
                        'includeInCards' => true,
                    ]),
                ]));
            },
        );
    }

    private function createSummaryField(): void
    {
        $this->summaryField = $this->field(
            self::SUMMARY_FIELD_UUID,
            'Summary',
            'summary',
            PlainText::class,
            function(PlainText $field) {
                $field->searchable = true;
            },
        );
    }

    private function createDetailsEntryType(): void
    {
        $this->detailsEntryType = $this->entryType(
            self::DETAILS_ENTRY_TYPE_UUID,
            'Details',
            'details',
            function(EntryType $entryType) {
                $entryType->icon = 'chevron-down';
                $entryType->color = ColorEnum::Fuchsia;
                $entryType->hasTitleField = false;
                $entryType->showStatusField = false;
                $entryType->showSlugField = false;
                $entryType->setFieldLayout($this->fieldLayout([
                    new CustomField($this->summaryField, [
                        'includeInCards' => true,
                    ]),
                    // add the CKE field once it's created...
                ]));
            },
        );
    }

    private function createColorField(): void
    {
        $this->colorField = $this->field(self::COLOR_FIELD_UUID, 'Color', 'color', Color::class);
    }

    private function createGroupEntryType(): void
    {
        $this->groupEntryType = $this->entryType(
            self::GROUP_ENTRY_TYPE_UUID,
            'Group',
            'group',
            function(EntryType $entryType) {
                $entryType->icon = 'layer-group';
                $entryType->color = ColorEnum::Indigo;
                $entryType->hasTitleField = false;
                $entryType->showStatusField = false;
                $entryType->showSlugField = false;
                $entryType->setFieldLayout($this->fieldLayout([
                    new CustomField($this->colorField, [
                        'label' => 'Background Color',
                        'handle' => 'backgroundColor',
                    ]),
                    new CustomField($this->colorField, [
                        'label' => 'Text Color',
                        'handle' => 'textColor',
                    ]),
                    // add the CKE field once it's created...
                ]));
            },
        );
    }

    private function createCkeConfig(): void
    {
        $this->do('Creating `Content` CKEditor config', function() {
            $configService = Ckeditor::getInstance()->getCkeConfigs();
            try {
                $config = $configService->getByUid(self::CKE_CONFIG_UUID);
            } catch (InvalidArgumentException) {
                $config = null;
            }

            if ($config) {
                return;
            }

            $config = new CkeConfig();
            $config->name = 'Post Content';
            $config->uid = self::CKE_CONFIG_UUID;
            $config->toolbar = [
                'heading', '|',
                'bold', 'italic', 'link', '|',
                'blockQuote', 'bulletedList', 'numberedList', 'codeBlock', '|',
                'insertTable', 'mediaEmbed', 'htmlEmbed', 'pageBreak', '|',
                'createEntry', 'sourceEditing',
            ];

            if (!$configService->save($config)) {
                throw new Exception(implode(', ', $config->getFirstErrors()));
            }
        });
    }

    private function createPostContentField(): void
    {
        $this->postContentField = $this->field(
            self::POST_CONTENT_FIELD_UUID,
            'Post Content',
            'postContent',
            CkeditorField::class,
            function(CkeditorField $field) {
                $field->ckeConfig = self::CKE_CONFIG_UUID;
                $field->purifyHtml = false; // todo: allow video embeds in purifier config
                $field->setEntryTypes([
                    $this->mediaEntryType,
                    $this->buttonEntryType,
                    $this->codepenEntryType,
                    $this->videoEntryType,
                    $this->detailsEntryType,
                    $this->groupEntryType,
                ]);
                $field->searchable = true;
            },
        );
    }

    private function createCategoryGroup(): void
    {
        $this->categoryGroup = $this->categoryGroup(
            self::CATEGORY_GROUP_UUID,
            'Categories',
            'categories',
            function(CategoryGroup $categoryGroup) {
                $fieldLayout = new FieldLayout();
                $fieldLayout->setTabs([
                    new FieldLayoutTab([
                        'layout' => $fieldLayout,
                        'name' => 'WordPress',
                        'elements' => [
                            new CustomField($this->wpIdField),
                        ],
                    ]),
                ]);

                $categoryGroup->setSiteSettings(array_map(fn(Site $site) => new CategoryGroup_SiteSettings([
                    'siteId' => $site->id,
                ]), Craft::$app->sites->getAllSites(true)));
                $categoryGroup->setFieldLayout($fieldLayout);
            },
        );
    }

    private function createCategoriesField(): void
    {
        $this->categoriesField = $this->field(
            self::CATEGORIES_FIELD_UUID,
            'Categories',
            'categories',
            Categories::class,
            function(Categories $field) {
                $field->source = "group:{$this->categoryGroup->uid}";
            },
        );
    }

    private function createTagGroup(): void
    {
        $this->tagGroup = $this->tagGroup(
            self::TAG_GROUP_UUID,
            'Tags',
            'tags',
            function(TagGroup $tagGroup) {
                $fieldLayout = new FieldLayout();
                $fieldLayout->setTabs([
                    new FieldLayoutTab([
                        'layout' => $fieldLayout,
                        'name' => 'WordPress',
                        'elements' => [
                            new CustomField($this->wpIdField),
                        ],
                    ]),
                ]);
                $tagGroup->setFieldLayout($fieldLayout);
            },
        );
    }

    private function createTagsField(): void
    {
        $this->tagsField = $this->field(
            self::TAGS_FIELD_UUID,
            'Tags',
            'tags',
            Tags::class,
            function(Tags $field) {
                $field->source = "taggroup:{$this->tagGroup->uid}";
            },
        );
    }

    private function createTemplateField(): void
    {
        $this->templateField = $this->field(
            self::TEMPLATE_FIELD_UUID,
            'Template',
            'template',
            PlainText::class,
            function(PlainText $field) {
                $field->code = true;
            },
        );
    }

    private function createCommentsField(): void
    {
        if (!$this->importComments) {
            return;
        }

        $this->commentsField = $this->field(
            self::COMMENTS_FIELD_UUID,
            'Comment Options',
            'commentOptions',
            CommentsField::class,
        );
    }

    private function createFormatField(): void
    {
        $this->formatField = $this->field(
            self::FORMAT_FIELD_UUID,
            'Format',
            'format',
            Dropdown::class,
            function(Dropdown $field) {
                $field->options = [
                    ['label' => 'Standard', 'value' => 'standard', 'default' => true],
                    ['label' => 'Aside', 'value' => 'aside'],
                    ['label' => 'Audio', 'value' => 'audio'],
                    ['label' => 'Chat', 'value' => 'chat'],
                    ['label' => 'Gallery', 'value' => 'gallery'],
                    ['label' => 'Image', 'value' => 'image'],
                    ['label' => 'Link', 'value' => 'link'],
                    ['label' => 'Quote', 'value' => 'quote'],
                    ['label' => 'Status', 'value' => 'status'],
                    ['label' => 'Video', 'value' => 'video'],
                ];
            },
        );
    }

    private function createStickyField(): void
    {
        $this->stickyField = $this->field(
            self::STICKY_FIELD_UUID,
            'Sticky',
            'sticky',
            Lightswitch::class,
        );
    }

    private function createPostEntryType(): void
    {
        $this->postEntryType = $this->entryType(
            self::POST_ENTRY_TYPE_UUID,
            'Post',
            'post',
            function(EntryType $entryType) {
                $entryType->icon = 'pen-nib';
                $entryType->color = ColorEnum::Blue;
                $fieldLayout = new FieldLayout();
                $fieldLayout->setTabs([
                    new FieldLayoutTab([
                        'layout' => $fieldLayout,
                        'name' => 'Content',
                        'elements' => [
                            new EntryTitleField([
                                'required' => false,
                            ]),
                            new CustomField($this->postContentField),
                        ],
                    ]),
                    new FieldLayoutTab([
                        'layout' => $fieldLayout,
                        'name' => 'Cover Photo',
                        'elements' => [
                            new CustomField($this->descriptionField, [
                                'label' => 'Cover Text',
                                'handle' => 'coverText',
                            ]),
                            new CustomField($this->mediaField, [
                                'label' => 'Cover Photo',
                                'handle' => 'coverPhoto',
                            ]),
                            new CustomField($this->colorField, [
                                'label' => 'Cover Overlay Color',
                                'handle' => 'coverOverlayColor',
                            ]),
                        ],
                    ]),
                    new FieldLayoutTab([
                        'layout' => $fieldLayout,
                        'name' => 'Meta',
                        'elements' => array_filter([
                            new CustomField($this->mediaField, [
                                'label' => 'Featured Image',
                                'handle' => 'featuredImage',
                            ]),
                            new Customfield($this->captionField, [
                                'label' => 'Excerpt',
                                'handle' => 'excerpt',
                            ]),
                            new HorizontalRule(),
                            $this->importComments ? new CustomField($this->commentsField) : null,
                            new CustomField($this->formatField),
                            new CustomField($this->stickyField),
                            new HorizontalRule(),
                            new CustomField($this->categoriesField),
                            new CustomField($this->tagsField),
                        ]),
                    ]),
                    new FieldLayoutTab([
                        'layout' => $fieldLayout,
                        'name' => 'WordPress',
                        'elements' => [
                            new CustomField($this->wpIdField),
                        ],
                    ]),
                ]);
                $entryType->setFieldLayout($fieldLayout);
            },
        );
    }

    private function createPostsSection(): void
    {
        $this->postsSection = $this->section(
            self::POSTS_SECTION_UUID,
            'Posts',
            'posts',
            function(Section $section) {
                $section->type = Section::TYPE_CHANNEL;
                $section->setEntryTypes([$this->postEntryType]);
                $section->setSiteSettings([
                    new Section_SiteSettings([
                        'siteId' => Craft::$app->sites->getPrimarySite()->id,
                        'uriFormat' => '{slug}',
                    ]),
                ]);
                $section->previewTargets = [
                    [
                        'label' => Craft::t('app', 'Primary {type} page', [
                            'type' => Entry::lowerDisplayName(),
                        ]),
                        'urlFormat' => '{url}',
                    ],
                ];
            },
        );
    }

    private function createPageEntryType(): void
    {
        $this->pageEntryType = $this->entryType(
            self::PAGE_ENTRY_TYPE_UUID,
            'Page',
            'page',
            function(EntryType $entryType) {
                $entryType->icon = 'page';
                $entryType->color = ColorEnum::Blue;
                $fieldLayout = new FieldLayout();
                $fieldLayout->setTabs([
                    new FieldLayoutTab([
                        'layout' => $fieldLayout,
                        'name' => 'Content',
                        'elements' => [
                            new EntryTitleField([
                                'required' => false,
                            ]),
                            new CustomField($this->postContentField),
                        ],
                    ]),
                    new FieldLayoutTab([
                        'layout' => $fieldLayout,
                        'name' => 'Cover Photo',
                        'elements' => [
                            new CustomField($this->descriptionField, [
                                'label' => 'Cover Text',
                                'handle' => 'coverText',
                            ]),
                            new CustomField($this->mediaField, [
                                'label' => 'Cover Photo',
                                'handle' => 'coverPhoto',
                            ]),
                            new CustomField($this->colorField, [
                                'label' => 'Cover Overlay Color',
                                'handle' => 'coverOverlayColor',
                            ]),
                        ],
                    ]),
                    new FieldLayoutTab([
                        'layout' => $fieldLayout,
                        'name' => 'Meta',
                        'elements' => array_filter([
                            new CustomField($this->mediaField, [
                                'label' => 'Featured Image',
                                'handle' => 'featuredImage',
                            ]),
                            new HorizontalRule(),
                            $this->importComments ? new CustomField($this->commentsField) : null,
                        ]),
                    ]),
                    new FieldLayoutTab([
                        'layout' => $fieldLayout,
                        'name' => 'WordPress',
                        'elements' => [
                            new CustomField($this->wpIdField),
                            new CustomField($this->templateField),
                        ],
                    ]),
                ]);
                $entryType->setFieldLayout($fieldLayout);
            },
        );
    }

    private function createPagesSection(): void
    {
        $this->pagesSection = $this->section(
            self::PAGES_SECTION_UUID,
            'Pages',
            'pages',
            function(Section $section) {
                $section->type = Section::TYPE_STRUCTURE;
                $section->setEntryTypes([$this->pageEntryType]);
                $section->setSiteSettings([
                    new Section_SiteSettings([
                        'siteId' => Craft::$app->sites->getPrimarySite()->id,
                        'uriFormat' => '{slug}',
                    ]),
                ]);
                $section->previewTargets = [
                    [
                        'label' => Craft::t('app', 'Primary {type} page', [
                            'type' => Entry::lowerDisplayName(),
                        ]),
                        'urlFormat' => '{url}',
                    ],
                ];
            },
        );
    }

    private function updateUserLayout(): void
    {
        $fieldLayout = Craft::$app->fields->getLayoutByType(User::class);
        if (!$fieldLayout->getFieldById($this->wpIdField->id)) {
            $this->do('Updating the user field layout', function() use ($fieldLayout) {
                $tabs = $fieldLayout->getTabs();
                $tabs[] = new FieldLayoutTab([
                    'name' => 'WordPress',
                    'layout' => $fieldLayout,
                    'elements' => [
                        new CustomField($this->wpIdField),
                    ],
                ]);
                $fieldLayout->setTabs($tabs);
                Craft::$app->users->saveLayout($fieldLayout);
            });
        }
    }

    private function updateCommentLayout(): void
    {
        if (!$this->importComments) {
            return;
        }

        $fieldLayout = Craft::$app->fields->getLayoutByType(Comment::class);
        if (!$fieldLayout->getFieldById($this->wpIdField->id)) {
            $this->do('Updating the comment field layout', function() use ($fieldLayout) {
                $tabs = $fieldLayout->getTabs();
                $tabs[] = new FieldLayoutTab([
                    'name' => 'WordPress',
                    'layout' => $fieldLayout,
                    'elements' => [
                        new CustomField($this->wpIdField),
                    ],
                ]);
                $fieldLayout->setTabs($tabs);
                $configData = [$fieldLayout->uid => $fieldLayout->getConfig()];
                Craft::$app->projectConfig->set(CommentsService::CONFIG_FIELDLAYOUT_KEY, $configData);
            });
        }
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

        // Already exists?
        /** @var string|ElementInterface $elementType */
        $elementType = $importer::elementType();
        $element = $elementType::find()
            ->{$this->wpIdField->handle}($id)
            ->status(null)
            ->limit(1)
            ->one();

        if ($element && !$this->update) {
            return $element->id;
        }

        $resourceLabel = Inflector::singularize($resource);
        $name = trim(($data['name'] ?? null) ?: ($data['title']['raw'] ?? null) ?: ($data['slug'] ?? null) ?: '');
        $name = ($name !== '' && $name != $id) ? "`$name` (`$id`)" : "`$id`";

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
                $element->{$this->wpIdField->handle} = $id;

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
                } finally {
                    if (isset($edition)) {
                        Craft::$app->edition = $edition;
                    }
                }
            } finally {
                Console::outdent();
            }
        });

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
                "$this->apiUrl/$resource",
                array_merge($this->resourceQueryParams($resource), [
                    'page' => $page,
                    'per_page' => $this->perPage,
                ], $queryParams),
                $response,
            );
            foreach ($body as $item) {
                yield $item;
            }
            $page++;
        } while (!isset($this->page) && $page <= $response->getHeaderLine('X-WP-TotalPages'));
    }

    public function item(string $resource, int $id, array $queryParams = []): array
    {
        return $this->get(
            "$this->apiUrl/$resource/$id",
            array_merge($this->resourceQueryParams($resource), $queryParams),
        );
    }

    private function resourceQueryParams(string $resource): array
    {
        return array_merge([
            'context' => 'edit',
        ], $this->importers[$resource]::queryParams());
    }

    private function get(string $uri, array $queryParams = [], ?ResponseInterface &$response = null): array
    {
        $response = $this->client->get($uri, [
            RequestOptions::AUTH => [$this->username, $this->password],
            RequestOptions::QUERY => $queryParams,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception(sprintf("%s gave a %s response.", $uri, $response->getStatusCode()));
        }

        $body = (string)$response->getBody();

        try {
            return Json::decode($body);
        } catch (InvalidArgumentException) {
            // Skip any PHP warnings at the top
            $dPos = Collection::make(['[', '{'])
                ->map(fn(string $d) => strpos($body, $d))
                ->filter(fn($pos) => $pos !== false)
                ->min();
            if ($dPos) {
                $body = substr($body, $dPos);
            }
            return Json::decode($body);
        }
    }

    public function normalizeColor(?string $color): ?string
    {
        // todo: these values should be configurable
        // or ideally, auto-discovered via the API somehow?? (theme.json)
        return match ($color) {
            'black' => '#000000',
            'cyan-bluish-gray' => '#abb8c3',
            'white' => '#ffffff',
            'pale-pink' => '#f78da7',
            'vivid-red' => '#cf2e2e',
            'luminous-vivid-orange' => '#ff6900',
            'luminous-vivid-amber' => '#fcb900',
            'light-green-cyan' => '#7bdcb5',
            'vivid-green-cyan' => '#00d084',
            'pale-cyan-blue' => '#8ed1fc',
            'vivid-cyan-blue' => '#0693e3',
            'vivid-purple' => '#9b51e0',
            default => (new ColorValidator())->validate($color) ? $color : null,
        };
    }
}
