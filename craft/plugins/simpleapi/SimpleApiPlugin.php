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
    /**
     * Register Site Routes
     *
     * @return array Site Routes
     */
    public function registerSiteRoutes()
    {

        return [
            'api/Entry/(?P<id>[0-9]+)' => array('action' => 'simpleApi/handleEntry'),
            'api/Entry' => array('action' => 'simpleApi/handleEntry'),
        ];
    }
}