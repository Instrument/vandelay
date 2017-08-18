<?php

namespace Craft;


class SimpleApiPlugin extends BasePlugin
{
    static public function getEntryCacheKey($stringable) {
        return 'elementapi_projects_Entry_' . $stringable;
    }

    static public function getLookupCacheKey($stringable) {
        return 'elementapi_projects_lookup_' . $stringable;
    }

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

    public function init() {
        craft()->on('entries.saveEntry', function(Event $event) {
            $entry = $event->params['entry'];
            $key = SimpleApiPlugin::getEntryCacheKey($entry->id);
            craft()->cache->delete($key);

            $lookups = craft()->cache->get(SimpleApiPlugin::getLookupCacheKey($entry->id));

            if (!is_array($lookups)) {
                $lookups = [];
            }
            foreach ($lookups as $lookupId) {
                craft()->cache->delete($lookupId);
            }
        });
    }

    /**
     * Register Site Routes
     *
     * @return array Site Routes
     */
    public function registerSiteRoutes()
    {

        // WARNING: If you're adding a new endpoint, make sure you're considering authorization.
        // In the endpoints below, we're validating a JWT.
        return [
            'simpleapi/Entry/(?P<id>[0-9]+)' => array('action' => 'simpleApi/handleEntry'),
            'simpleapi/Entry' => array('action' => 'simpleApi/handleEntry')
        ];
    }
}
