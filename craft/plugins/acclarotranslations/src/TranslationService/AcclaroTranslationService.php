<?php

namespace Craft\AcclaroTranslations\TranslationService;

use Craft\AcclaroTranslations\ApiClient\AcclaroApiClient;
use Craft\AcclaroTranslations_FileModel;
use Craft\AcclaroTranslations_OrderModel;
use Craft\AcclaroTranslations\ElementTranslator;
use Craft\AcclaroTranslations\UrlGenerator;
use Craft\AcclaroTranslations\Translator;
use Craft\AcclaroTranslations\Repository\DraftRepository;
use Craft\AcclaroTranslations\Repository\EntryRepository;
use Craft\AcclaroTranslations\Repository\GlobalSetRepository;
use Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository;
use Craft\AcclaroTranslations\Repository\LanguageRepository;
use Craft\AcclaroTranslations\Job\UpdateDraftFromXml;
use Craft\AcclaroTranslations\Job\Factory as JobFactory;
use Craft\GlobalSetModel;
use Craft\ElementHelper;
use CApplication;
use DateTime;
use Exception;

class AcclaroTranslationService implements TranslationServiceInterface
{
    /**
     * @var CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\ElementTranslator
     */
    protected $elementTranslator;

    /**
     * @var \Craft\AcclaroTranslations\Repository\DraftRepository
     */
    protected $draftRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\EntryRepository
     */
    protected $entryRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\GlobalSetRepository
     */
    protected $globalSetRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository
     */
    protected $globalSetDraftRepository;

    /**
     * @var \Craft\AcclaroTranslations\Repository\LanguageRepository
     */
    protected $languageRepository;

    /**
     * @var \Craft\AcclaroTranslations\UrlGenerator
     */
    protected $urlGenerator;

    /**
     * @var \Craft\AcclaroTranslations\Translator
     */
    protected $translator;

    /**
     * @var \Craft\AcclaroTranslations\ApiClient\AcclaroApiClient
     */
    protected $apiClient;

    /**
     * @var boolean
     */
    protected $sandboxMode = false;

    /**
     * @param array                                                          $settings
     * @param \CApplication                                                  $craft
     * @param \Craft\AcclaroTranslations\ElementTranslator                   $elementTranslator
     * @param \Craft\AcclaroTranslations\Repository\DraftRepository          $draftRepository
     * @param \Craft\AcclaroTranslations\Repository\EntryRepository          $entryRepository
     * @param \Craft\AcclaroTranslations\Repository\GlobalSetRepository      $globalSetRepository
     * @param \Craft\AcclaroTranslations\Repository\GlobalSetDraftRepository $globalSetDraftRepository
     * @param \Craft\AcclaroTranslations\Repository\LanguageRepository       $languageRepository
     * @param \Craft\AcclaroTranslations\UrlGenerator                        $urlGenerator
     * @param \Craft\AcclaroTranslations\Translator                          $translator
     * @param \Craft\AcclaroTranslations\ApiClient\AcclaroApiClient          $apiClient
     */
    public function __construct(
        array $settings,
        CApplication $craft,
        ElementTranslator $elementTranslator,
        DraftRepository $draftRepository,
        EntryRepository $entryRepository,
        GlobalSetRepository $globalSetRepository,
        GlobalSetDraftRepository $globalSetDraftRepository,
        LanguageRepository $languageRepository,
        UrlGenerator $urlGenerator,
        Translator $translator,
        AcclaroApiClient $apiClient
    ) {
        if (!isset($settings['apiToken'])) {
            throw new Exception('Missing apiToken');
        }

        $this->craft = $craft;

        $this->elementTranslator = $elementTranslator;

        $this->draftRepository = $draftRepository;

        $this->entryRepository = $entryRepository;

        $this->globalSetRepository = $globalSetRepository;

        $this->globalSetDraftRepository = $globalSetDraftRepository;

        $this->languageRepository = $languageRepository;

        $this->urlGenerator = $urlGenerator;

        $this->translator = $translator;

        $this->sandboxMode = !empty($settings['sandboxMode']);

        $this->apiClient = $apiClient ?: new AcclaroApiClient(
            $settings['apiToken'],
            !empty($settings['sandboxMode'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        $response = $this->apiClient->getAccount();

        return !empty($response->plunetid);
    }

    /**
     * {@inheritdoc}
     */
    public function updateOrder(JobFactory $jobFactory, AcclaroTranslations_OrderModel $order)
    {
        $orderResponse = $this->apiClient->getOrder($order->serviceOrderId);

        if ($order->status !== $orderResponse->status) {
            $order->logActivity(
                sprintf($this->translator->translate('Order stautus changed to %s'), $orderResponse->status)
            );
        }

        $order->status = $orderResponse->status;
    }

    /**
     * {@inheritdoc}
     */
    public function updateFile(JobFactory $jobFactory, AcclaroTranslations_OrderModel $order, AcclaroTranslations_FileModel $file)
    {
        $fileInfoResponse = $this->apiClient->getFileInfo($order->serviceOrderId);

        // find the matching file
        foreach ($fileInfoResponse as $fileInfo) {
            if ($fileInfo->fileid == $file->serviceFileId) {
                break;
            }

            $fileInfo = null;
        }

        if (empty($fileInfo->targetfile)) {
            return;
        }

        $targetFileId = $fileInfo->targetfile;

        $fileStatusResponse = $this->apiClient->getFileStatus($order->serviceOrderId, $targetFileId);

        $file->status = $fileStatusResponse->status;

        // download the file
        $target = $this->apiClient->getFile($order->serviceOrderId, $targetFileId);

        if ($target) {
            $file->target = $target;

            $element = $this->craft->getComponent('elements')->getElementById($file->elementId, null, $file->sourceLanguage);

            if ($element instanceof GlobalSetModel) {
                $draft = $this->globalSetDraftRepository->getDraftById($file->draftId, $file->targetLanguage);
            } else {
                $draft = $this->draftRepository->getDraftById($file->draftId, $file->targetLanguage);
            }

            $jobFactory->dispatchJob(UpdateDraftFromXml::class, $element, $draft, $target, $file->sourceLanguage, $file->targetLanguage);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendOrder(AcclaroTranslations_OrderModel $order)
    {
        $orderResponse = $this->apiClient->createOrder(
            $order->title,
            $order->comments,
            $order->requestedDueDate ? $order->requestedDueDate->format(DateTime::ISO8601) : '',
            $order->id,
            $order->wordCount
        );

        $order->serviceOrderId = $orderResponse->orderid;
        $order->status = $orderResponse->status;

        $orderCallbackResponse = $this->apiClient->requestOrderCallback(
            $order->serviceOrderId,
            $this->urlGenerator->generateOrderCallbackUrl($order)
        );

        $tempPath = $this->craft->getComponent('path')->getTempPath();

        foreach ($order->files as $file) {
            $element = $this->craft->getComponent('elements')->getElementById($file->elementId);

            $targetLanguage = $this->languageRepository->normalizeLanguage($file->targetLanguage);

            if ($element instanceof GlobalSetModel) {
                $filename = ElementHelper::createSlug($element->name).'-'.$targetLanguage.'.xml';
            } else {
                $filename = $element->slug.'-'.$targetLanguage.'.xml';
            }

            $path = $tempPath.$filename;

            $stream = fopen($path, 'w+');

            fwrite($stream, $file->source);

            $fileResponse = $this->apiClient->sendSourceFile(
                $order->serviceOrderId,
                $this->languageRepository->normalizeLanguage($file->sourceLanguage),
                $targetLanguage,
                $file->id,
                $path
            );

            $file->serviceFileId = $fileResponse->fileid;
            $file->status = $fileResponse->status;

            $fileCallbackResponse = $this->apiClient->requestFileCallback(
                $order->serviceOrderId,
                $file->serviceFileId,
                $this->urlGenerator->generateFileCallbackUrl($file)
            );

            $this->apiClient->addReviewUrl(
                $order->serviceOrderId,
                $file->serviceFileId,
                $file->previewUrl
            );

            fclose($stream);

            unlink($path);
        }

        $submitOrderResponse = $this->apiClient->submitOrder($order->serviceOrderId);

        $order->status = $submitOrderResponse->status;
    }

    public function getOrderUrl(AcclaroTranslations_OrderModel $order)
    {
        $subdomain = $this->sandboxMode ? 'apisandbox' : 'my';

        return sprintf('https://%s.acclaro.com/portal/vieworder.php?id=%s', $subdomain, $order->serviceOrderId);
    }
}
