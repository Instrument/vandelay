<?php

namespace Craft;


class SimpleApiPlugin extends BasePlugin
{
    /**
     * Get Name
     *
     * @return string Name
     */
    public function getName()
    {
         return Craft::t('Simple Api');
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
        craft()->templates->hook('cp.entries.edit.right-pane', function(&$context) {
            /** @var EntryModel $entry **/
            $entry = $context['entry'];
            // Return the button HTML
            $url = UrlHelper::getUrl('some/path/'.$entry->id);
            $locale = $entry->locale;
            return '<a href="/simpleapi/Entry/'.$entry->id.'/'.$locale.'?download=1" target="download" class="btn">Export entry</a>';
        });
    }
    /**
     * Register Site Routes
     *
     * @return array Site Routes
     */
    public function registerSiteRoutes()
    {

        return [
            'simpleapi/Entry/(?P<id>[0-9]+)/(?P<locale>[a-z\_]+)' => array('action' => 'simpleApi/handleEntry'),
            'simpleapi/Entry' => array('action' => 'simpleApi/handleEntry'),
            'simpleapi/Singles' => array('action' => 'simpleApi/getSingles'),
        ];
    }
}