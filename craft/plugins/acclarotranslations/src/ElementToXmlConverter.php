<?php

namespace Craft\AcclaroTranslations;

use Craft\BaseElementModel;
use Craft\EntryDraftModel;
use Craft\AcclaroTranslations\ElementTranslator;
use Craft\AcclaroTranslations\UrlGenerator;
use DOMDocument;
use DateTime;
use CApplication;

class ElementToXmlConverter
{
    /**
     * @var \CApplication
     */
    protected $craft;

    /**
     * @var \Craft\AcclaroTranslations\ElementTranslator
     */
    protected $elementTranslator;

    /**
     * @var \Craft\AcclaroTranslations\UrlGenerator
     */
    protected $urlGenerator;

    /**
     * @param \CApplication                                $craft
     * @param \Craft\AcclaroTranslations\ElementTranslator $elementTranslator
     * @param \Craft\AcclaroTranslations\UrlGenerator      $urlGenerator
     */
    public function __construct(
        CApplication $craft,
        ElementTranslator $elementTranslator,
        UrlGenerator $urlGenerator
    ) {
        $this->craft = $craft;

        $this->elementTranslator = $elementTranslator;

        $this->urlGenerator = $urlGenerator;
    }

    public function toXml(BaseElementModel $element, $draftId = 0, $sourceLanguage = null, $targetLanguage = null, $previewUrl = null)
    {
        $dom = new DOMDocument('1.0', 'utf-8');

        $dom->formatOutput = true;

        $xml = $dom->appendChild($dom->createElement('xml'));

        $head = $xml->appendChild($dom->createElement('head'));
        $original = $head->appendChild($dom->createElement('original'));
        $preview = $head->appendChild($dom->createElement('preview'));
        $langs = $head->appendChild($dom->createElement('langs'));
        $langs->setAttribute('source-language', $sourceLanguage);
        $langs->setAttribute('target-language', $targetLanguage);
        $original->setAttribute('url', $this->urlGenerator->generateElementPreviewUrl($element));
        $preview->setAttribute('url', $previewUrl);

        $elementIdMeta = $head->appendChild($dom->createElement('meta'));
        $elementIdMeta->setAttribute('name', 'elementId');
        $elementIdMeta->setAttribute('content', $element->id);

        $draftIdMeta = $head->appendChild($dom->createElement('meta'));
        $draftIdMeta->setAttribute('name', 'draftId');
        $draftIdMeta->setAttribute('content', $draftId);

        $body = $xml->appendChild($dom->createElement('body'));

        foreach ($this->elementTranslator->toTranslationSource($element) as $key => $value) {
            $translation = $dom->createElement('content');

            $translation->setAttribute('resname', $key);

            // Does the value contain characters requiring a CDATA section?
            $text = 1 === preg_match('/[&<>]/', $value) ? $dom->createCDATASection($value) : $dom->createTextNode($value);

            $translation->appendChild($text);

            $body->appendChild($translation);
        }

        return $dom->saveXML();
    }
}
