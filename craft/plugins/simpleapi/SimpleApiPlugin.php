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
         return Craft::t('Vandelay');
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
            $oldPath = craft()->path->getTemplatesPath();      
            $newPath = craft()->path->getPluginsPath().'simpleapi/templates';     
            craft()->path->setTemplatesPath($newPath);        
            $upload = craft()->templates->render('upload');     
            craft()->path->setTemplatesPath($oldPath);
            $entry = $context['entry'];
            $locale = $entry->locale;
            $html = '<a href="/simpleapi/Entry/';
            $html .= $entry->id;
            $html .= '/'.$locale;
            $html .= '?download=1';
            if (isset($context['draftId'])) { 
                $html .= '&draftId='.$context['draftId']; 
            }
            $html .= '" target="download" class="btn">Export entry</a>';
            $copy = '<a class="btn submit" id="copy-trigger" data-entry-id="'.$entry->id.'">';
            $copy .= 'Copy English to All</a>';
            return $html . $upload . $copy;
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
            'simpleapi\/home\/' => 'simpleApi',
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
            'simpleapi/Entry/(?P<id>[0-9]+)/(?P<locale>[a-z\_]+)' => array('action' => 'simpleApi/handleEntry'),
            'simpleapi/Entry' => array('action' => 'simpleApi/handleEntry'),
            'simpleapi/Singles' => array('action' => 'simpleApi/getSingles'),
            'simpleapi/Globals/(?P<locale>[a-z\_]+)' => array('action' => 'simpleApi/getGlobals'),
            'simpleapi/uploadEntry' => array('action' => 'simpleApi/uploadEntry'),
            'simpleapi/getSection/(?P<section>[a-zA-Z\_]+)' => array('action' => 'simpleApi/getSectionEntries'),
            'simpleapi/copyToAll/(?P<id>[0-9]+)' => array('action' => 'simpleApi/copyEnglishToAll'),
        ];
    }
}