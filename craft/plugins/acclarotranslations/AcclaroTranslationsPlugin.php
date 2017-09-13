<?php

namespace Craft;

use CApplication;
use Craft\AcclaroTranslations\Container;
use Craft\AcclaroTranslations\Translator;
use Craft\AcclaroTranslations\Repository\TranslationRepository;
use Craft\AcclaroTranslations\Repository\OrderRepository;
use Craft\AcclaroTranslations\Repository\FileRepository;
use Craft\AcclaroTranslations\Repository\GlobalSetRepository;
use Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository;

class AcclaroTranslationsPlugin extends BasePlugin
{
    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\Container
     */
    protected $container;

    /**
     * @var \Craft\AcclaroTranslations\Translator
     */
    protected $translator;

    /**
     * @var \Craft\AcclaroTranslations\TranslationRepository
     */
    protected $translationRepository;

    /**
     * @var \Craft\AcclaroTranslations\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Craft\AcclaroTranslations\FileRepository
     */
    protected $fileRepository;

    /**
     * @var \Craft\AcclaroTranslations\GlobalSetRepository
     */
    protected $globalSetRepository;

    /**
     * @var \Craft\AcclaroTranslations\GlobalSetDraftRepository
     */
    protected $globalSetDraftRepository;

    /**
     * @param \CApplication|null                                                  $craft
     * @param \Craft\AcclaroTranslations\Container|null                           $container
     * @param \Craft\AcclaroTranslations\Translator|null                          $translator
     * @param \Craft\AcclaroTranslations\Repository\TranslationRepository|null    $translationRepository
     * @param \Craft\AcclaroTranslations\Repository\OrderRepository|null          $orderRepository
     * @param \Craft\AcclaroTranslations\Repository\FileRepository|null           $fileRepository
     * @param \Craft\AcclaroTranslations\Repository\GlobalSetRepository|null      $globalSetRepository
     * @param \Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository|null $globalSetDraftRepository
     */
    public function __construct(
        CApplication $craft = null,
        Container $container = null,
        Translator $translator = null,
        TranslationRepository $translationRepository = null,
        OrderRepository $orderRepository = null,
        FileRepository $fileRepository = null,
        GlobalSetRepository $globalSetRepository = null,
        GlobalSetDraftRepository $globalSetDraftRepository = null
    ) {
        require_once __DIR__.'/vendor/autoload.php';

        $this->craft = $craft ?: craft();

        $this->container = $container ?: new Container($this->craft);

        $this->translator = $translator ?: $this->container[Translator::class];

        $this->translationRepository = $translationRepository ?: $this->container[TranslationRepository::class];

        $this->orderRepository = $orderRepository ?: $this->container[OrderRepository::class];

        $this->fileRepository = $fileRepository ?: $this->container[FileRepository::class];

        $this->globalSetRepository = $globalSetRepository ?: $this->container[GlobalSetRepository::class];

        $this->globalSetDraftRepository = $globalSetDraftRepository ?: $this->container[GlobalSetDraftRepository::class];
    }

    public function make($class)
    {
        return $this->container[$class];
    }

    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Called after the plugin class is instantiated; do any one-time initialization here such as hooks and events:
     *
     * craft()->on('entries.saveEntry', function(Event $event) {
     *    // ...
     * });
     *
     * or loading any third party Composer packages via:
     *
     * require_once __DIR__ . '/vendor/autoload.php';
     *
     * @return mixed
     */
    public function init()
    {
        parent::init();

        $this->translationRepository->loadTranslations();

        $this->craft->on('entries.onSaveEntry', array($this, 'onSaveEntry'));

        $this->craft->on('entryRevisions.onPublishDraft', array($this, 'onPublishDraft'));

        if ($this->craft->getComponent('request')->isCpRequest()) {
            $this->includeResources($this->craft->getComponent('request')->getPath());
        }
    }

    public function includeResources($path)
    {
        $this->includeUniversalResources();

        if (preg_match('#^entries/[^/]+/[^/]+/drafts/(\d+)$#', $path, $match)) {
            $this->includeEditDraftResources($match[1]);
        }

        if (preg_match('#^entries(/|$)#', $path)) {
            $this->includeEntriesResources();
        }

        if (preg_match('#^globals/([^/]+)$#', $path, $match)) {
            $this->includeGlobalSetResources($match[1]);
        }

        if (preg_match('#^globals/([^/]+)/([^/]+)$#', $path, $match)) {
            $this->includeGlobalSetResources($match[2], $match[1]);
        }
    }

    public function includeUniversalResources()
    {
        $this->craft->getComponent('templates')->includeJsResource('acclarotranslations/js/ShowCompleteOrdersIndicator.js');

        $this->craft->getComponent('templates')->includeCssResource('acclarotranslations/css/AcclaroTranslations.css');

        $numberOfCompleteOrders = count($this->orderRepository->getCompleteOrders());

        $this->craft->getComponent('templates')->includeJs("Craft.AcclaroTranslations.ShowCompleteOrdersIndicator.init({$numberOfCompleteOrders});");
    }

    public function includeEditDraftResources($draftId)
    {
        $translationOrderId = $this->craft->getComponent('db')->createCommand()
            ->select('orderId')
            ->from('acclarotranslations_files')
            ->where('draftId = :draftId AND status != :statusA AND status != :statusB', array(':draftId' => $draftId, ':statusA' => 'complete', ':statusB' => 'published'))
            ->queryScalar();

        // disable editing of this draft
        if ($translationOrderId) {
            //$this->craft->getComponent('templates')->includeCss(".previewbtns:after, .grid .pane:after { display: block; content: '\\00a0'; position: absolute; top: 0; right: 0; left: 0; bottom: 0; background: rgba(255,255,255,0.5); z-index: 1; }");
            $this->craft->getComponent('templates')->includeCss("#extra-headers .btngroup { display: none; }");

            $this->craft->getComponent('templates')->includeJsResource('acclarotranslations/js/DisableFields.js');
        }
    }

    public function includeEntriesResources()
    {
        $orders = array();

        foreach ($this->orderRepository->getDraftOrders() as $order) {
            $orders[] = array(
                'id' => $order->id,
                'title' => $order->title,
            );
        }

        $orders = json_encode($orders);

        $this->craft->getComponent('templates')->includeJsResource('acclarotranslations/js/AddEntriesToTranslationOrder.js');

        $this->craft->getComponent('templates')->includeJs("$(function(){ Craft.AcclaroTranslations.AddEntriesToTranslationOrder.init({$orders}); });");
    }

    public function includeGlobalSetResources($globalSetHandle, $locale = null)
    {
        $globalSet = $this->globalSetRepository->getSetByHandle($globalSetHandle, $locale);

        if (!$globalSet) {
            return;
        }

        $orders = array();

        foreach ($this->orderRepository->getDraftOrders() as $order) {
            if ($order->sourceLanguage === $globalSet->locale) {
                $orders[] = array(
                    'id' => $order->id,
                    'title' => $order->title,
                );
            }
        }

        $drafts = array();

        foreach ($this->globalSetDraftRepository->getDraftsByGlobalSetId($globalSet->id, $locale) as $draft) {
            $drafts[] = array(
                'url' => $draft->getCpEditUrl(),
                'name' => $draft->name,
            );
        }

        $orders = json_encode($orders);

        $globalSetId = json_encode($globalSet->getAttribute('id'));

        $drafts = json_encode($drafts);

        $this->craft->getComponent('templates')->includeJsResource('acclarotranslations/js/GlobalSetEdit.js');

        $this->craft->getComponent('templates')->includeJs("$(function(){ Craft.AcclaroTranslations.GlobalSetEdit.init({$orders}, {$globalSetId}, {$drafts}); });");
    }

    /**
     * Returns the user-facing name.
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->translator->translate('Translations');
    }

    /**
     * Plugins can have descriptions of themselves displayed on the Plugins page by adding a getDescription() method
     * on the primary plugin class:
     *
     * @return mixed
     */
    public function getDescription()
    {
        return $this->translator->translate('The Craft Translations Plugin enables website content to be sent for professional translation. When completed, the translated content will appear automatically back into Craft CMS for in-context review and publishing.');
    }

    /**
     * Returns the version number.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.0.1';
    }

    /**
     * As of Craft 2.5, Craft no longer takes the whole site down every time a plugin’s version number changes, in
     * case there are any new migrations that need to be run. Instead plugins must explicitly tell Craft that they
     * have new migrations by returning a new (higher) schema version number with a getSchemaVersion() method on
     * their primary plugin class:
     *
     * @return string
     */
    public function getSchemaVersion()
    {
        return '1.0.0';
    }

    /**
     * Returns the developer’s name.
     *
     * @return string
     */
    public function getDeveloper()
    {
        return 'Acclaro';
    }

    /**
     * Returns the developer’s website URL.
     *
     * @return string
     */
    public function getDeveloperUrl()
    {
        return 'http://www.acclaro.com/';
    }

    /**
     * Returns the documentation URL.
     *
     * @return string
     */
    public function getDocumentationUrl()
    {
        return 'http://info.acclaro.com/hubfs/Product/CraftTranslationPlugin/CraftTranslationPluginQuickStartGuide.pdf';
    }

    /**
     * Returns whether the plugin should get its own tab in the CP header.
     *
     * @return bool
     */
    public function hasCpSection()
    {
        return true;
    }

    public function registerCpRoutes()
    {
        return array(
            'acclarotranslations' => array('action' => 'acclaroTranslations/index'),
            'acclarotranslations/orders' => array('action' => 'acclaroTranslations/orderIndex'),
            'acclarotranslations/orders/new' => array('action' => 'acclaroTranslations/orderDetail'),
            'acclarotranslations/orders/detail/(?P<orderId>\d+)' => array('action' => 'acclaroTranslations/orderDetail'),
            'acclarotranslations/orders/entries/(?P<orderId>\d+)' => array('action' => 'acclaroTranslations/orderEntries'),
            'acclarotranslations/orders/reporting' => array('action' => 'acclaroTranslations/orderReporting'),
            'acclarotranslations/translators' => array('action' => 'acclaroTranslations/translatorIndex'),
            'acclarotranslations/translators/new' => array('action' => 'acclaroTranslations/translatorDetail'),
            'acclarotranslations/translators/detail/(?P<translatorId>\d+)' => array('action' => 'acclaroTranslations/translatorDetail'),
            'acclarotranslations/about' => array('action' => 'acclaroTranslations/aboutIndex'),
            'acclarotranslations/globals/(?P<globalSetHandle>{handle})/drafts/(?P<draftId>\d+)' => array('action' => 'acclaroTranslations/editGlobalSetDraft'),
        );
    }

    public function onSaveEntry(Event $event)
    {
        // @TODO check if entry is part of an in-progress translation order
        // and send notification to acclaro
    }

    public function onPublishDraft(Event $event)
    {
        // update acclaro order and files
        $draft = $event->params['draft'];

        $currentFile = $this->fileRepository->getFileByDraftId($draft->draftId, $draft->id);

        if (!$currentFile) {
            return;
        }

        $order = $this->orderRepository->getOrderById($currentFile->orderId);

        $currentFile->setAttribute('status', 'published');

        $this->fileRepository->saveFile($currentFile);

        $areAllFilesPublished = true;

        foreach ($order->files as $file) {
            if ($file->status !== 'published') {
                $areAllFilesPublished = false;
                break;
            }
        }

        if ($areAllFilesPublished) {
            $order->setAttribute('status', 'published');

            $this->orderRepository->saveOrder($order);
        }
    }

    /**
     * Called right before your plugin’s row gets stored in the plugins database table, and tables have been created
     * for it based on its records.
     */
    public function onBeforeInstall()
    {
    }



    /**
     * Called right after your plugin’s row has been stored in the plugins database table, and tables have been
     * created for it based on its records.
     */
    public function onAfterInstall()
    {
    }

    /**
     * Called right before your plugin’s record-based tables have been deleted, and its row in the plugins table
     * has been deleted.
     */
    public function onBeforeUninstall()
    {
    }
}
