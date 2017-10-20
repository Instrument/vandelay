<?php

namespace Craft;


class DraftPreviewPlugin extends BasePlugin
{
    /**
     * Get Name
     *
     * @return string Name
     */
    public function getName()
    {
         return Craft::t('Draft Preview');
    }

    /**
     * Get Version
     *
     * @return string Version
     */
    public function getVersion()
    {
        return '1.0.0';
    }

    /**
     * Get Developer
     *
     * @return string Developer
     */
    public function getDeveloper()
    {
        return 'Instrument';
    }

    /**
     * Get Developer Url
     *
     * @return string Developer Url
     */
    public function getDeveloperUrl()
    {
        return 'http://instrument.com';
    }
    public function init()
    {
        craft()->templates->hook('cp.entries.edit.preview-hook', function(&$context) {
            $entry = $context['entry'];
            $locale = $entry->locale;
            // var_dump($_SERVER['REQUEST_URI']);
            $isBlog = strpos($_SERVER['REQUEST_URI'], 'insightsAndNewsEntries') !== false;//strpos($entry->uri, 'blog/') !== false;
            $isDraft = $entry->getClassHandle() === 'EntryDraft';
            if ($isDraft) {
                $previewButton = '<div style="display: block; width: 100%; position: relative; text-align: right;"><a href="/draftpreview'.($isBlog ? 'blog' : '').'/Entry/'.$entry->id.'/'.$locale.'/'.$entry->draftId.'" target="_blank" class="btn submit">Preview Draft</a></div>';
            }

            return $isDraft ? $previewButton : null; 
        });
    }
    public function hasCpSection()
    {
        return true;
    }
    /**
     * Register control panel routes
     */
    public function registerCpRoutes()
    {
        return array(
            'draftpreview\/home\/' => 'draftPreview',
        );
    }
    /**
     * Register Site Routes
     *
     * @return array Site Routes
     */
    public function registerSiteRoutes()
    {

        return [
            'draftpreview/Entry/(?P<id>[0-9]+)/(?P<locale>[a-z\_]+)/(?P<draft>[0-9]+)' => array('action' => 'draftPreview/handleEntry'),
            'draftpreview/Entry' => array('action' => 'draftPreview/handleEntry'),
            'draftpreviewblog/Entry/(?P<id>[0-9]+)/(?P<locale>[a-z\_]+)/(?P<draft>[0-9]+)' => array('action' => 'draftPreview/handleEntry'),
            'draftpreviewblog/Entry' => array('action' => 'draftPreview/handleEntry'),
        ];
    }
}