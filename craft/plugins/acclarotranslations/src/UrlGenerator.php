<?php

namespace Craft\AcclaroTranslations;

use Craft\BaseElementModel;
use Craft\EntryDraftModel;
use Craft\GlobalSetModel;
use Craft\AcclaroTranslations\UrlHelper;
use Craft\AcclaroTranslations_FileModel;
use Craft\AcclaroTranslations_OrderModel;
use Craft\AcclaroTranslations_GlobalSetDraftModel;
use DOMDocument;
use DateTime;
use CApplication;

class UrlGenerator
{
    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\UrlHelper
     */
    protected $urlHelper;

    /**
     * @param \CApplication                        $craft
     * @param \Craft\AcclaroTranslations\UrlHelper $urlHelper
     */
    public function __construct(
        CApplication $craft,
        UrlHelper $urlHelper
    ) {
        $this->craft = $craft;

        $this->urlHelper = $urlHelper;
    }

    public function generateFileCallbackUrl(AcclaroTranslations_FileModel $file)
    {
        $key = sha1_file(CRAFT_CONFIG_PATH.'license.key');

        $cpTrigger = '/'.$this->craft->getComponent('config')->get('cpTrigger');

        $url = $this->urlHelper->getActionUrl('acclaroTranslations/fileCallback', array(
            'key' => $key,
            'fileId' => $file->id,
        ));

        return preg_replace('/'.preg_quote($cpTrigger, '/').'/', '', $url, 1);
    }

    public function generateOrderCallbackUrl(AcclaroTranslations_OrderModel $order)
    {
        $key = sha1_file(CRAFT_CONFIG_PATH.'license.key');

        $cpTrigger = '/'.$this->craft->getComponent('config')->get('cpTrigger');

        $url = $this->urlHelper->getActionUrl('acclaroTranslations/orderCallback', array(
            'key' => $key,
            'orderId' => $order->id,
        ));

        return preg_replace('/'.preg_quote($cpTrigger, '/').'/', '', $url, 1);
    }

    public function generateFileUrl(BaseElementModel $element, AcclaroTranslations_FileModel $file)
    {
        if ($file->status === 'published') {
            if ($element instanceof GlobalSetModel) {
                return preg_replace(
                    '/(\/'.$element->handle.')$/',
                    '/'.$file->targetLanguage.'$1',
                    $element->getCpEditUrl()
                );
            }

            return $element->getCpEditUrl().'/'.$file->targetLanguage;
        }

        if ($element instanceof GlobalSetModel) {
            return $this->urlHelper->getCpUrl('acclarotranslations/globals/'.$element->handle.'/drafts/'.$file->draftId);
        }

        return $this->urlHelper->getCpUrl('entries/'.$element->section->handle.'/'.$element->id.'/drafts/'.$file->draftId);
    }

    public function generateFileWebUrl(BaseElementModel $element, AcclaroTranslations_FileModel $file)
    {
        if ($file->status === 'published') {
            if ($element instanceof GlobalSetModel) {
                return '';
            }

            return $element->url;
        }

        return $this->generateElementPreviewUrl($element, $file);
    }

    public function generateCpUrl($path)
    {
        return $this->urlHelper->getCpUrl($path);
    }

    public function generateElementPreviewUrl(BaseElementModel $element)
    {
        if ($element instanceof GlobalSetModel) {
            return '';
        }

        if ($element instanceof EntryDraftModel) {
            $params = array('draftId' => $element->draftId);
        } else {
            $params = array('entryId' => $element->id, 'locale' => $element->locale);
        }

        // Create the token and redirect to the entry URL with the token in place
        $token = $this->craft->getComponent('tokens')->createToken(
            array(
                'action' => 'entries/viewSharedEntry',
                'params' => $params,
            ),
            null,
            new DateTime('+3 months')
        );

        return $this->urlHelper->getUrlWithToken($element->getUrl(), $token);
    }
}
