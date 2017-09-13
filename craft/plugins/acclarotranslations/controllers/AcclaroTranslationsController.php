<?php

namespace Craft;

use Craft\AcclaroTranslations\HasCraftTrait;
use Craft\AcclaroTranslations\Repository\OrderRepository;
use Craft\AcclaroTranslations\Repository\FileRepository;
use Craft\AcclaroTranslations\Repository\DraftRepository;
use Craft\AcclaroTranslations\Repository\EntryRepository;
use Craft\AcclaroTranslations\Repository\GlobalSetRepository;
use Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository;
use Craft\AcclaroTranslations\Repository\LanguageRepository;
use Craft\AcclaroTranslations\Repository\TranslatorRepository;
use Craft\AcclaroTranslations\TranslationService\Factory as TranslationServiceFactory;
use Craft\AcclaroTranslations\ElementToXmlConverter;
use Craft\AcclaroTranslations\ElementTranslator;
use Craft\AcclaroTranslations\TranslationService\AcclaroTranslationService;
use Craft\AcclaroTranslations\Job\Factory as JobFactory;
use Craft\AcclaroTranslations\Job\CreateOrderTranslationDrafts;
use Craft\AcclaroTranslations\Job\SendOrderToTranslationService;
use Craft\AcclaroTranslations\UrlGenerator;
use Craft\AcclaroTranslations\Translator;
use Craft\AcclaroTranslations\OrderSearchParams;
use Craft\AcclaroTranslations_GlobalSetDraftModel as GlobalSetDraftModel;
use Craft\EntryModel;
use DateTime;
use CApplication;

class AcclaroTranslationsController extends BaseController
{
    protected $allowAnonymous = array(
        'actionOrderCallback',
        'actionFileCallback',
    );

    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\Repository\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\FileRepository
     */
    protected $fileRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\LanguageRepository
     */
    protected $languageRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\DraftRepository
     */
    protected $draftRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\EntryRepository
     */
    protected $entryRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\TranslatorRepository
     */
    protected $translatorRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\GlobalSetRepository
     */
    protected $globalSetRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository
     */
    protected $globalSetDraftRepository;

    /**
     * @var \Craft\AcclaroTranslations\ElementTranslator
     */
    protected $elementTranslator;

    /**
     * @var \Craft\AcclaroTranslations\Job\Factory
     */
    protected $jobFactory;

    /**
     * @var \Craft\AcclaroTranslations\TranslationService\Factory
     */
    protected $translationServiceFactory;

    /**
     * @var \Craft\AcclaroTranslations\ElementToXmlConverter
     */
    protected $elementToXmlConverter;

    /**
     * @var \Craft\AcclaroTranslations\UrlGenerator
     */
    protected $urlGenerator;

    /**
     * @var \Craft\AcclaroTranslations\Translator
     */
    protected $translator;

    /**
     * @var \Craft\AcclaroTranslations\OrderSearchParams
     */
    protected $orderSearchParams;

    /**
     * @var array
     */
    protected $adminTabs;

    /**
     * @var int
     */
    protected $pluginVersion;

    public function __construct(
        $id,
        $module = null,
        CApplication $craft = null,
        OrderRepository $orderRepository = null,
        FileRepository $fileRepository = null,
        LanguageRepository $languageRepository = null,
        DraftRepository $draftRepository = null,
        EntryRepository $entryRepository = null,
        TranslatorRepository $translatorRepository = null,
        GlobalSetRepository $globalSetRepository = null,
        GlobalSetDraftRepository $globalSetDraftRepository = null,
        ElementTranslator $elementTranslator = null,
        JobFactory $jobFactory = null,
        TranslationServiceFactory $translationServiceFactory = null,
        ElementToXmlConverter $elementToXmlConverter = null,
        UrlGenerator $urlGenerator = null,
        Translator $translator = null,
        OrderSearchParams $orderSearchParams = null
    ) {
        parent::__construct($id, $module);

        $this->craft = $craft ?: craft();

        $this->orderRepository = $orderRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(OrderRepository::class);

        $this->fileRepository = $fileRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(FileRepository::class);

        $this->translatorRepository = $translatorRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(TranslatorRepository::class);

        $this->globalSetRepository = $globalSetRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(GlobalSetRepository::class);

        $this->globalSetDraftRepository = $globalSetDraftRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(GlobalSetDraftRepository::class);

        $this->elementTranslator = $elementTranslator ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(ElementTranslator::class);

        $this->languageRepository = $languageRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(LanguageRepository::class);

        $this->draftRepository = $draftRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(DraftRepository::class);

        $this->entryRepository = $entryRepository ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(EntryRepository::class);

        $this->jobFactory = $jobFactory ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(JobFactory::class);

        $this->translationServiceFactory = $translationServiceFactory ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(TranslationServiceFactory::class);

        $this->elementToXmlConverter = $elementToXmlConverter ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(ElementToXmlConverter::class);

        $this->urlGenerator = $urlGenerator ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(UrlGenerator::class);

        $this->translator = $translator ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(Translator::class);

        $this->orderSearchParams = $orderSearchParams ?: $this->craft->getComponent('plugins')->getPlugin('AcclaroTranslations')->make(OrderSearchParams::class);

        $this->adminTabs = array(
            'dashboard' => array(
                'label' => $this->translator->translate('Dashboard'),
                'url' => $this->urlGenerator->generateCpUrl('acclarotranslations'),
            ),
            'orders' => array(
                'label' => $this->translator->translate('Orders'),
                'url' => $this->urlGenerator->generateCpUrl('acclarotranslations/orders'),
            ),
            'translators' => array(
                'label' => $this->translator->translate('Translators'),
                'url' => $this->urlGenerator->generateCpUrl('acclarotranslations/translators'),
            ),
            'about' => array(
                'label' => $this->translator->translate('About'),
                'url' => $this->urlGenerator->generateCpUrl('acclarotranslations/about'),
            ),
        );

        $this->pluginVersion = $this->craft->getComponent('plugins')->getPlugin('acclarotranslations')->getVersion();
    }

    public function actionOrderCallback()
    {
        $this->logIncomingRequest('orderCallback');

        HeaderHelper::setContentTypeByExtension('txt');

        $key = sha1_file(CRAFT_CONFIG_PATH.'license.key');

        if ($this->craft->getComponent('request')->getRequiredQuery('key') !== $key) {
            $this->craft->end('Invalid key');
        }

        $orderId = $this->craft->getComponent('request')->getRequiredQuery('orderId');

        if (!$orderId) {
            $this->craft->end('Missing orderId');
        }

        $order = $this->orderRepository->getOrderById($orderId);

        if (!$order) {
            $this->craft->end('Invalid orderId');
        }

        // don't process published orders
        if ($order->status === 'published') {
            $this->craft->end('Order already published');
        }

        $translator = $order->getTranslator();

        $translationService = $this->translationServiceFactory->makeTranslationService($translator->service, $translator->getSettings());

        $translationService->updateOrder($this->jobFactory, $order);

        $this->orderRepository->saveOrder($order);

        $this->craft->end('OK');
    }

    public function logIncomingRequest($endpoint)
    {
        $headers = getallheaders();

        $request = sprintf(
            "%s %s %s\n",
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['SERVER_PROTOCOL']
        );

        foreach ($headers as $key => $value) {
            $request .= "{$key}: {$value}\n";
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $request .= "\n".http_build_query($_POST);
        }

        $tempPath = $this->craft->getComponent('path')->getTempPath().'/acclarotranslations';

        if (!is_dir($tempPath)) {
            mkdir($tempPath);
        }

        $filename = 'request-'.$endpoint.'-'.date('YmdHis').'.txt';

        $filePath = $tempPath.'/'.$filename;

        $handle = fopen($filePath, 'w+');

        fwrite($handle, $request);

        fclose($handle);
    }

    public function actionFileCallback()
    {
        $this->logIncomingRequest('fileCallback');

        HeaderHelper::setContentTypeByExtension('txt');

        $key = sha1_file(CRAFT_CONFIG_PATH.'license.key');

        if ($this->craft->getComponent('request')->getQuery('key') !== $key) {
            $this->craft->end('Invalid key');
        }

        $fileId = $this->craft->getComponent('request')->getRequiredQuery('fileId');

        $file = $this->fileRepository->getFileById($fileId);

        echo 'Found file'.PHP_EOL;

        // don't process published files
        if ($file->status === 'published') {
            $this->craft->end('File already published');
        }

        $order = $this->orderRepository->getOrderById($file->orderId);

        echo 'Found order'.PHP_EOL;

        $translator = $order->getTranslator();

        $translationService = $this->translationServiceFactory->makeTranslationService($translator->service, $translator->getSettings());

        echo 'Updating file'.PHP_EOL;

        $translationService->updateFile($this->jobFactory, $order, $file);

        echo 'Saving file'.PHP_EOL;

        $this->fileRepository->saveFile($file);

        $this->craft->end('OK');
    }

    public function actionAuthenticateTranslationService()
    {
        $this->requireLogin();
        $this->requireAdmin();
        $this->requirePostRequest();

        $service = $this->craft->getComponent('request')->getRequiredPost('service');
        $settings = $this->craft->getComponent('request')->getRequiredPost('settings');

        $translator = $this->translatorRepository->makeNewTranslator();
        $translator->service = $service;
        $translator->settings = json_encode($settings);

        $translationService = $this->translationServiceFactory->makeTranslationService($service, $settings);

        return $this->returnJson(array(
            'success' => $translationService->authenticate($settings),
        ));
    }

    public function actionAddElementsToOrder()
    {
        $this->requireLogin();
        $this->requireAdmin();
        $this->requirePostRequest();

        $orderId = $this->craft->getComponent('request')->getRequiredPost('id');

        $sourceLanguage = $this->craft->getComponent('request')->getRequiredPost('sourceLanguage');

        $order = $this->orderRepository->getOrderById($orderId);

        if (!$order) {
            throw new HttpException(400, $this->translator->translate('Invalid Order'));
        }

        if (!$this->languageRepository->isLanguageSupported($sourceLanguage)) {
            throw new HttpException(400, $this->translator->translate('Source language is not supported'));
        }

        if ($order->sourceLanguage !== $sourceLanguage) {
            throw new HttpException(400, $this->translator->translate('All entries within an order must have the same source language.'));
        }

        $elements = $order->getElements();

        $elementIds = array();

        foreach ($elements as $element) {
            $elementIds[] = $element->id;
        }

        if (is_array($this->craft->getComponent('request')->getPost('elements'))) {
            foreach ($this->craft->getComponent('request')->getPost('elements') as $elementId) {
                if (!in_array($elementId, $elementIds)) {
                    $elementIds[] = $elementId;

                    $element = $this->craft->getComponent('elements')->getElementById($elementId, null, $order->sourceLanguage);

                    if ($element instanceof EntryModel) {
                        $locales = array();

                        foreach ($element->section->locales as $locale) {
                            $locales[] = $locale->locale;
                        }

                        $hasTargetLanguages = !array_diff($order->targetLanguagesArray, $locales);

                        if (!$hasTargetLanguages) {
                            $message = sprintf(
                                $this->translator->translate("The entry “%s” does not target the languages specified in this order. Go to Settings > Sections > %s to change this entry's target languages."),
                                $element->title,
                                $element->section->name
                            );

                            throw new HttpException(400, $message);
                        }
                    }

                    $elements[] = $element;
                }
            }
        }

        $wordCount = 0;

        foreach ($elements as $element) {
            $wordCount += $this->elementTranslator->getWordCount($element);
        }

        $order->entriesCount = count($elements);
        $order->wordCount = $wordCount;
        $order->elementIds = json_encode($elementIds);

        $this->orderRepository->saveOrder($order);

        $this->redirect('acclarotranslations/orders/detail/'.$order->id, true, 302);
    }

    public function actionPublishEntries()
    {
        $orderId = $this->craft->getComponent('request')->getPost('orderId');

        $elementIds = $this->craft->getComponent('request')->getPost('elements');

        $order = $this->orderRepository->getOrderById($orderId);

        $files = $order->getFiles();

        $transaction = $this->craft->getComponent('db')->getCurrentTransaction() === null ? $this->craft->getComponent('db')->beginTransaction() : null;

        $filesCount = count($files);

        $publishedFilesCount = 0;

        foreach ($files as $file) {
            if (!in_array($file->elementId, $elementIds)) {
                continue;
            }

            $publishedFilesCount++;

            if ($file->status === 'published') {
                continue;
            }

            $element = $this->craft->getComponent('elements')->getElementById($file->elementId, null, $file->sourceLanguage);

            if ($element instanceof GlobalSetModel) {
                $draft = $this->globalSetDraftRepository->getDraftById($file->draftId);

                // keep original global set name
                $draft->name = $element->name;

                $success = $this->globalSetDraftRepository->publishDraft($draft);

                $uri = 'acclarotranslations/globals/'.$element->handle.'/drafts/'.$file->draftId;
            } else {
                $draft = $this->draftRepository->getDraftById($file->draftId);

                $success = $this->draftRepository->publishDraft($draft);

                $uri = 'entries/'.$element->section->handle.'/'.$element->id.'/drafts/'.$file->draftId;
            }

            if ($success) {
                $oldTokenRoute = json_encode(array(
                    'action' => 'entries/viewSharedEntry',
                    'params' => array(
                        'draftId' => $file->draftId,
                    ),
                ));

                $newTokenRoute = json_encode(array(
                    'action' => 'entries/viewSharedEntry',
                    'params' => array(
                        'entryId' => $draft->id,
                        'locale' => $file->targetLanguage,
                    ),
                ));

                $this->craft->getComponent('db')->createCommand()->update(
                    'tokens',
                    array('route' => $newTokenRoute),
                    'route = :oldTokenRoute',
                    array(':oldTokenRoute' => $oldTokenRoute)
                );
            } else {
                if ($transaction !== null) {
                    $transaction->rollback();
                }

                $this->craft->getComponent('userSession')->setError($this->translator->translate('Couldn’t publish draft.'));

                $this->redirect($uri, true, 302);

                return;
            }

            $file->setAttribute('draftId', null);
            $file->setAttribute('status', 'published');

            $this->fileRepository->saveFile($file);
        }

        if ($publishedFilesCount === $filesCount) {
            $order->setAttribute('status', 'published');

            $order->logActivity($this->translator->translate('Entries published'));

            $this->orderRepository->saveOrder($order);
        }

        if ($transaction !== null) {
            $transaction->commit();
        }

        $this->craft->getComponent('userSession')->setNotice($this->translator->translate('Entries published.'));

        $this->redirect('acclarotranslations/orders/entries/'.$orderId, true, 302);
    }

    public function actionDeleteOrder()
    {
        $this->requirePostRequest();

        $orderId = $this->craft->getComponent('request')->getPost('orderId');

        $order = $this->orderRepository->getOrderById($orderId);

        if (!$order) {
            return $this->returnJson(array(
                'success' => false,
                'error' => $this->translator->translate('No order exists with the ID “{id}”.', array('id' => $orderId))
            ));
        }

        if ($order->getAttribute('status') !== 'new') {
            return $this->returnJson(array(
                'success' => false,
                'error' => $this->translator->translate('You cannot delete a submitted order.')
            ));
        }

        $this->orderRepository->deleteOrder($order);

        $this->craft->getComponent('userSession')->setNotice($this->translator->translate('Order deleted.'));

        $this->returnJson(array(
            'success' => true,
            'error' => null
        ));
    }

    public function actionSaveOrder()
    {
        $this->requireLogin();
        $this->requireAdmin();
        $this->requirePostRequest();

        $orderId = $this->craft->getComponent('request')->getPost('id');

        if ($orderId) {
            $order = $this->orderRepository->getOrderById($orderId);

            if (!$order) {
                throw new HttpException(400, $this->translator->translate('Invalid Order'));
            }
        } else {
            $sourceLanguage = $this->craft->getComponent('request')->getPost('sourceLanguage');

            if ($sourceLanguage && !$this->languageRepository->isLanguageSupported($sourceLanguage)) {
                throw new HttpException(400, $this->translator->translate('Source language is not supported'));
            }

            $order = $this->orderRepository->makeNewOrder($sourceLanguage);

            $order->logActivity($this->translator->translate('Order created'));
        }

        $targetLanguages = $this->craft->getComponent('request')->getPost('targetLanguages');

        if ($targetLanguages === '*') {
            $targetLanguages = array_keys($this->languageRepository->getLanguages('', $order->sourceLanguage));
        }

        $requestedDueDate = $this->craft->getComponent('request')->getPost('requestedDueDate');

        $translatorId = $this->craft->getComponent('request')->getPost('translatorId');

        $title = $this->craft->getComponent('request')->getPost('title');

        if (!$title) {
            $title = sprintf(
                'Translation Order #%s',
                $this->orderRepository->getOrdersCount() + 1
            );
        }

        $order->ownerId = $this->craft->getComponent('request')->getPost('ownerId');
        $order->getContent()->title = $title;
        $order->targetLanguages = $targetLanguages ? json_encode($targetLanguages) : null;
        $order->requestedDueDate = $requestedDueDate ? DateTime::createFromFormat('n/j/Y', $requestedDueDate['date']) : null;
        $order->comments = $this->craft->getComponent('request')->getPost('comments');
        $order->translatorId = $translatorId;

        $elementIds = $this->craft->getComponent('request')->getPost('elements') ? $this->craft->getComponent('request')->getPost('elements') : array();

        $order->elementIds = json_encode($elementIds);

        $entriesCount = 0;
        $wordCounts = array();

        foreach ($order->getElements() as $element) {
            $entriesCount++;

            $wordCounts[$element->id] = $this->elementTranslator->getWordCount($element);

            if ($element instanceof EntryModel) {
                $locales = array();

                foreach ($element->section->locales as $locale) {
                    $locales[] = $locale->locale;
                }

                $hasTargetLanguages = !array_diff($targetLanguages, $locales);

                if (!$hasTargetLanguages) {
                    $message = sprintf(
                        $this->translator->translate("The entry “%s” does not target the languages specified in this order. Go to Settings > Sections > %s to change this entry's target languages."),
                        $element->title,
                        $element->section->name
                    );

                    throw new HttpException(400, $message);
                }
            }
        }

        $order->entriesCount = $entriesCount;
        $order->wordCount = array_sum($wordCounts);

        $this->orderRepository->saveOrder($order);

        if ($this->craft->getComponent('request')->getPost('submit')) {
            $order->logActivity(sprintf($this->translator->translate('Order submitted to %s'), $order->translator->getName()));

            $drafts = $this->jobFactory->dispatchJob(CreateOrderTranslationDrafts::class, $order->getTargetLanguagesArray(), $order->getElements(), $order->title);

            foreach ($drafts as $draft) {
                $file = $this->fileRepository->makeNewFile();

                $element = $this->craft->getComponent('elements')->getElementById($draft->id, null, $order->sourceLanguage);

                $file->orderId = $order->id;
                $file->elementId = $draft->id;
                $file->draftId = $draft->draftId;
                $file->sourceLanguage = $order->sourceLanguage;
                $file->targetLanguage = $draft->locale;
                $file->previewUrl = $this->urlGenerator->generateElementPreviewUrl($draft);
                $file->source = $this->elementToXmlConverter->toXml(
                    $element,
                    $draft->draftId,
                    $this->languageRepository->normalizeLanguage($order->sourceLanguage),
                    $this->languageRepository->normalizeLanguage($draft->locale),
                    $file->previewUrl
                );
                $file->wordCount = isset($wordCounts[$draft->id]) ? $wordCounts[$draft->id] : 0;

                $this->fileRepository->saveFile($file);
            }

            $this->jobFactory->dispatchJob(SendOrderToTranslationService::class, $order);

            $this->craft->getComponent('userSession')->setNotice($this->translator->translate('Order submitted.'));
        } else {
            $this->craft->getComponent('userSession')->setNotice($this->translator->translate('Order saved.'));
        }

        $this->redirect('acclarotranslations/orders', true, 302);
    }

    public function actionDeleteTranslator()
    {
        $this->requireLogin();
        $this->requireAdmin();
        $this->requirePostRequest();

        $translatorId = $this->craft->getComponent('request')->getRequiredPost('translatorId');

        $translator = $this->translatorRepository->getTranslatorById($translatorId);

        if (!$translator) {
            throw new Exception('Invalid Translator');
        }

        // check if translator has any pending orders
        $pendingOrders = $this->orderRepository->getInProgressOrdersByTranslatorId($translatorId);

        $pendingOrdersCount = count($pendingOrders);

        if ($pendingOrdersCount > 0) {
            $this->craft->getComponent('userSession')->setError($this->translator->translate('This translator cannot be deleted until all in-progress orders are completed.'));

            $this->redirect('acclarotranslations/translators', true, 302);
        }

        $this->translatorRepository->deleteTranslator($translator);

        $this->craft->getComponent('userSession')->setNotice($this->translator->translate('Translator deleted.'));

        $this->redirect('acclarotranslations/translators', true, 302);
    }

    public function actionSaveTranslator()
    {
        $this->requireLogin();
        $this->requireAdmin();
        $this->requirePostRequest();

        $translatorId = $this->craft->getComponent('request')->getPost('id');

        if ($translatorId) {
            $translator = $this->translatorRepository->getTranslatorById($translatorId);

            if (!$translator) {
                throw new HttpException(400, 'Invalid Translator');
            }
        } else {
            $translator = $this->translatorRepository->makeNewTranslator();
        }

        $languages = $this->craft->getComponent('request')->getPost('languages');

        if ($languages === '*') {
            $languages = array_keys($this->languageRepository->getLanguages());
        }

        $service = $this->craft->getComponent('request')->getPost('service');

        $allSettings = $this->craft->getComponent('request')->getPost('settings');

        $settings = isset($allSettings[$service]) ? $allSettings[$service] : array();

        $translator->label = $this->craft->getComponent('request')->getPost('label');
        $translator->service = $service;
        $translator->languages = $languages ? json_encode($languages) : null;
        $translator->settings = json_encode($settings);
        $translator->status = $this->craft->getComponent('request')->getPost('status');

        $this->translatorRepository->saveTranslator($translator);

        $this->craft->getComponent('userSession')->setNotice($this->translator->translate('Translator saved.'));

        $this->redirect('acclarotranslations/translators', true, 302);
    }

    public function actionEditGlobalSetDraft(array $variables = array())
    {
        if (empty($variables['globalSetHandle'])) {
            throw new HttpException(400, $this->translator->translate('Param “{name}” doesn’t exist.', array('name' => 'globalSetHandle')));
        }

        $variables['globalSets'] = array();

        $globalSets = $this->globalSetRepository->getAllSets();

        foreach ($globalSets as $globalSet) {
            if ($this->craft->getComponent('userSession')->checkPermission('editGlobalSet:'.$globalSet->id)) {
                $variables['globalSets'][$globalSet->handle] = $globalSet;
            }
        }

        if (!isset($variables['globalSets'][$variables['globalSetHandle']])) {
            throw new HttpException(400, $this->translator->translate('Invalid global set handle'));
        }

        $globalSet = $variables['globalSets'][$variables['globalSetHandle']];

        $variables['globalSetId'] = $globalSet->id;

        $variables['orders'] = array();

        foreach ($this->orderRepository->getDraftOrders() as $order) {
            if ($order->sourceLanguage === $globalSet->locale) {
                $variables['orders'][] = $order;
            }
        }

        $draft = $this->globalSetDraftRepository->getDraftById($variables['draftId']);

        $variables['drafts'] = $this->globalSetDraftRepository->getDraftsByGlobalSetId($globalSet->id, $draft->locale);

        $variables['draft'] = $draft;

        $variables['file'] = $this->fileRepository->getFileByDraftId($draft->draftId, $globalSet->id);

        $this->renderTemplate('acclarotranslations/globals/_editDraft', $variables);
    }

    public function actionIndex()
    {
        $variables = array();

        $variables['adminTabs'] = $this->adminTabs;

        $variables['pluginVersion'] = $this->pluginVersion;

        $variables['tool'] = new AcclaroTranslations_SyncOrdersTool();

        $this->renderTemplate('acclarotranslations/_index', $variables);
    }

    public function actionOrderDetail(array $variables = array())
    {
        $variables['adminTabs'] = $this->adminTabs;

        $variables['pluginVersion'] = $this->pluginVersion;

        $variables['orderId'] = isset($variables['orderId']) ? $variables['orderId'] : null;

        $variables['inputSourceLanguage'] = $this->craft->getComponent('request')->getQuery('sourceLanguage');

        if ($variables['inputSourceLanguage'] && !$this->languageRepository->isLanguageSupported($variables['inputSourceLanguage'])) {
            throw new HttpException(400, $this->translator->translate('Source language is not supported'));
        }

        if ($variables['orderId']) {
            $variables['order'] = $this->orderRepository->getOrderById($variables['orderId']);

            $variables['inputElements'] = [];

            if (!$variables['order']) {
                throw new HttpException(404);
            }

            $variables['tool'] = new AcclaroTranslations_SyncOrderTool($variables['orderId']);
        } else {
            $variables['order'] = $this->orderRepository->makeNewOrder($variables['inputSourceLanguage']);

            $variables['inputElements'] = $this->craft->getComponent('request')->getQuery('elements');
        }

        $variables['orientation'] = $this->craft->getComponent('i18n')->getLocaleData()->getOrientation();

        $variables['translatorOptions'] = $this->translatorRepository->getTranslatorOptions();

        $variables['elements'] = $variables['order']->getElements();

        if ($variables['inputElements']) {
            foreach ($variables['inputElements'] as $elementId) {
                $element = $this->craft->getComponent('elements')->getElementById($elementId, null, $variables['order']->getAttribute('sourceLanguage'));

                if ($element) {
                    $variables['elements'][] = $element;
                }
            }
        }

        $variables['orderEntriesCount'] = count($variables['elements']);

        $variables['orderWordCount'] = 0;

        $variables['elementWordCounts'] = array();

        $variables['entriesCountBySection'] = array();

        foreach ($variables['elements'] as $element) {
            $wordCount = $this->elementTranslator->getWordCount($element);

            $variables['elementWordCounts'][$element->id] = $wordCount;

            $variables['orderWordCount'] += $wordCount;

            if ($element instanceof GlobalSetModel) {
                $sectionName = 'Globals';
            } else {
                $sectionName = $element->section->name;
            }

            if (!isset($variables['entriesCountBySection'][$sectionName])) {
                $variables['entriesCountBySection'][$sectionName] = 0;
            }

            $variables['entriesCountBySection'][$sectionName]++;
        }

        if (!$variables['translatorOptions']) {
            $variables['translatorOptions'] = array('' => $this->translator->translate('No Translators'));
        }

        $user = $this->craft->getComponent('userSession')->getUser();

        //@TODO update this
        $variables['owners'] = array(
            $user->id => $user->username,
        );

        $variables['languages'] = array();

        $languages = $this->languageRepository->getLanguages();

        foreach ($languages as $language => $label) {
            if ($language !== $variables['order']->sourceLanguage) {
                $variables['languages'][$language] = $label;
            }
        }

        $variables['isSubmitted'] = $variables['order']->status !== 'new';

        $this->renderTemplate('acclarotranslations/orders/_detail', $variables);
    }

    public function actionOrderEntries(array $variables = array())
    {
        $variables['adminTabs'] = $this->adminTabs;

        $variables['pluginVersion'] = $this->pluginVersion;

        $variables['order'] = $this->orderRepository->getOrderById($variables['orderId']);

        if (!$variables['order']) {
            throw new HttpException(404);
        }

        $variables['elements'] = $variables['order']->getElements();

        $variables['files'] = array();

        $variables['fileUrls'] = array();

        $variables['webUrls'] = array();

        $variables['isElementPublished'] = array();

        foreach ($variables['elements'] as $element) {
            $variables['files'][$element->id] = $this->fileRepository->getFilesByOrderId($variables['orderId'], $element->id);

            $isElementPublished = true;

            foreach ($variables['files'][$element->id] as $file) {
                if ($file->status !== 'published') {
                    $isElementPublished = false;
                }

                if ($element instanceof EntryModel) {
                    if ($file->status === 'published') {
                        $translatedElement = $this->craft->elements->getElementById($element->id, null, $file->targetLanguage);

                        $variables['webUrls'][$file->id] = $translatedElement ? $translatedElement->url : $element->url;
                    } else {
                        $variables['webUrls'][$file->id] = $file->previewUrl;
                    }
                }

                $variables['fileUrls'][$file->id] = $this->urlGenerator->generateFileUrl($element, $file);
            }

            $variables['isElementPublished'][$element->id] = $isElementPublished;
        }

        $this->renderTemplate('acclarotranslations/orders/_entries', $variables);
    }

    public function actionOrderReporting()
    {
        $variables = array();

        $variables['adminTabs'] = $this->adminTabs;

        $variables['pluginVersion'] = $this->pluginVersion;

        $variables['sourceLanguage'] = $this->craft->getComponent('request')->getQuery('sourceLanguage');

        $variables['targetLanguage'] = $this->craft->getComponent('request')->getQuery('targetLanguage');

        $variables['startDate'] = $this->craft->getComponent('request')->getQuery('startDate') ? DateTime::createFromFormat('n/j/Y', $this->craft->getComponent('request')->getQuery('startDate')) : null;

        $variables['endDate'] = $this->craft->getComponent('request')->getQuery('endDate') ? DateTime::createFromFormat('n/j/Y', $this->craft->getComponent('request')->getQuery('endDate')) : null;

        $variables['status'] = $this->craft->getComponent('request')->getQuery('status');

        $variables['languages'] = $this->languageRepository->getLanguages();

        $variables['orderStatuses'] = array_map(array($this->translator, 'translate'), $this->orderRepository->getOrderStatuses());

        $this->renderTemplate('acclarotranslations/orders/_reporting', $variables);
    }

    public function actionOrderIndex()
    {
        $variables = array();

        $variables['adminTabs'] = $this->adminTabs;

        $variables['pluginVersion'] = $this->pluginVersion;

        $variables['searchParams'] = $this->orderSearchParams->getParams();

        $this->renderTemplate('acclarotranslations/orders/_index', $variables);
    }

    public function actionTranslatorDetail(array $variables = array())
    {
        $variables['adminTabs'] = $this->adminTabs;

        $variables['pluginVersion'] = $this->pluginVersion;

        $variables['translatorId'] = isset($variables['translatorId']) ? $variables['translatorId'] : null;

        if ($variables['translatorId']) {
            $variables['translator'] = $this->translatorRepository->getTranslatorById($variables['translatorId']);

            if (!$variables['translator']) {
                throw new HttpException(404);
            }
        } else {
            $variables['translator'] = $this->translatorRepository->makeNewTranslator();
        }

        $variables['orientation'] = $this->craft->getComponent('i18n')->getLocaleData()->getOrientation();

        $variables['languages'] = $this->languageRepository->getLanguages();

        $variables['translationServices'] = $this->translatorRepository->getTranslationServices();

        $this->renderTemplate('acclarotranslations/translators/_detail', $variables);
    }

    public function actionTranslatorIndex()
    {
        $variables = array();

        $variables['adminTabs'] = $this->adminTabs;

        $variables['pluginVersion'] = $this->pluginVersion;

        $variables['translators'] = $this->translatorRepository->getTranslators();

        $this->renderTemplate('acclarotranslations/translators/_index', $variables);
    }

    public function actionAboutIndex()
    {
        $variables = array();

        $variables['adminTabs'] = $this->adminTabs;

        $variables['pluginVersion'] = $this->pluginVersion;

        $this->renderTemplate('acclarotranslations/_about', $variables);
    }

    public function actionSaveGlobalSetDraft()
    {
        $this->requirePostRequest();

        $locale = $this->craft->getComponent('request')->getPost('locale', $this->craft->getComponent('i18n')->getPrimarySiteLocaleId());

        $globalSetId = $this->craft->getComponent('request')->getPost('globalSetId', $this->craft->getComponent('request')->getPost('setId'));

        $globalSet = $this->globalSetRepository->getSetById($globalSetId, $locale);

        if (!$globalSet) {
            throw new HttpException(400, $this->translator->translate('No global set exists with the ID “{id}”.', array('id' => $globalSetId)));
        }

        $draftId = $this->craft->getComponent('request')->getPost('draftId');

        if ($draftId) {
            $draft = $this->globalSetDraftRepository->getDraftById($draftId);

            if (!$draft) {
                throw new HttpException(400, $this->translator->translate('No draft exists with the ID “{id}”.', array('id' => $draftId)));
            }
        } else {
            $draft = $this->globalSetDraftRepository->makeNewDraft();
            $draft->id = $globalSetId;
            $draft->locale = $locale;
        }

        // @TODO Make sure they have permission to be editing this

        $fieldsLocation = $this->craft->getComponent('request')->getParam('fieldsLocation', 'fields');

        $draft->setContentFromPost($fieldsLocation);

        if ($this->globalSetDraftRepository->saveDraft($draft)) {
            $this->craft->getComponent('userSession')->setNotice($this->translator->translate('Draft saved.'));

            $this->redirect($draft->getCpEditUrl(), true, 302);
        } else {
            $this->craft->getComponent('userSession')->setError($this->translator->translate('Couldn’t save draft.'));

            $this->craft->getComponent('urlManager')->setRouteVariables(array(
                'globalSet' => $draft
            ));
        }
    }

    public function actionDeleteGlobalSetDraft()
    {
        $this->requirePostRequest();

        $draftId = $this->craft->getComponent('request')->getPost('draftId');

        $draft = $this->globalSetDraftRepository->getDraftById($draftId);

        if (!$draft) {
            throw new HttpException(400, $this->translator->translate('No draft exists with the ID “{id}”.', array('id' => $draftId)));
        }

        $globalSet = $draft->getGlobalSet();

        $this->globalSetDraftRepository->deleteDraft($draft);

        $this->craft->getComponent('userSession')->setNotice($this->translator->translate('Draft deleted.'));

        $this->redirect($globalSet->getCpEditUrl(), true, 302);
    }

    public function actionPublishGlobalSetDraft()
    {
        $this->requirePostRequest();

        $draftId = $this->craft->getComponent('request')->getPost('draftId');

        $draft = $this->globalSetDraftRepository->getDraftById($draftId);

        if (!$draft) {
            throw new HttpException(400, $this->translator->translate('No draft exists with the ID “{id}”.', array('id' => $draftId)));
        }

        $globalSet = $this->globalSetRepository->getSetById($draft->id, $draft->locale);

        if (!$globalSet) {
            throw new HttpException(400, $this->translator->translate('No global set exists with the ID “{id}”.', array('id' => $draft->id)));
        }

        //@TODO $this->enforceEditEntryPermissions($entry);

        $fieldsLocation = $this->craft->getComponent('request')->getParam('fieldsLocation', 'fields');

        $draft->setContentFromPost($fieldsLocation);

        // restore the original name
        $draft->name = $globalSet->name;

        $file = $this->fileRepository->getFileByDraftId($draftId, $globalSet->id);

        if ($file) {
            $order = $this->orderRepository->getOrderById($file->orderId);

            $file->setAttribute('status', 'published');

            $this->fileRepository->saveFile($file);

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

        if ($this->globalSetDraftRepository->publishDraft($draft)) {
            $this->craft->getComponent('userSession')->setNotice($this->translator->translate('Draft published.'));

            $this->redirect($globalSet->getCpEditUrl(), true, 302);
        } else {
            $this->craft->getComponent('userSession')->setError($this->translator->translate('Couldn’t publish draft.'));

            // Send the draft back to the template
            $this->craft->getComponent('urlManager')->setRouteVariables(array(
                'draft' => $draft
            ));
        }
    }
}
