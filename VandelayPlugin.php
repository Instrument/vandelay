<?php

namespace Craft;


class VandelayPlugin extends BasePlugin
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
            $newPath = craft()->path->getPluginsPath().'vandelay/templates';     
            craft()->path->setTemplatesPath($newPath);        
            $upload = craft()->templates->render('upload');     
            craft()->path->setTemplatesPath($oldPath);
            $entry = $context['entry'];
            $locale = $entry->locale;
            $html = '<a href="/vandelay/Entry/';
            $html .= $entry->id;
            $html .= '/'.$locale;
            $html .= '?download=1';
            if (isset($context['draftId'])) { 
                $html .= '&draftId='.$context['draftId']; 
            }
            $html .= '" target="download" class="btn">Export entry</a>';
            return $html . $upload;
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
            'vandelay\/home\/' => 'vandelay',
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
            'simpleapi/Entry/(?P<id>[0-9]+)/(?P<locale>[a-z\_]+)' => array('action' => 'vandelay/handleEntry'),
            'simpleapi/Entry' => array('action' => 'vandelay/handleEntry'),
            'simpleapi/Singles' => array('action' => 'vandelay/getSingles'),
            'simpleapi/Globals/(?P<locale>[a-z\_]+)' => array('action' => 'vandelay/getGlobals'),
            'simpleapi/uploadEntry' => array('action' => 'vandelay/uploadEntry'),
            'simpleapi/getSection/(?P<section>[a-zA-Z\_]+)' => array('action' => 'vandelay/getSectionEntries'),
        ];
    }
}