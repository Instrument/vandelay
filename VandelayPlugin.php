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
            $html = '<div class="ui-wrapper"><div class="field"><a href="/vandelay/Entry/';
            $html .= $entry->id;
            $html .= '/'.$locale;
            $html .= '?download=1';
            if (isset($context['draftId'])) { 
                $html .= '&draftId='.$context['draftId']; 
            }
            $html .= '" target="download" class="btn big">Export entry</a></div>';
            $copy = '<div class="field"><a class="btn submit big" id="copy-trigger" data-entry-id="'.$entry->id.'">';
            $copy .= 'Copy English to All</a></div>';
            return $html . $copy . $upload . '</div>';
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
            'vandelay/Entry/(?P<id>[0-9]+)/(?P<locale>[a-z\_]+)' => array('action' => 'vandelay/handleEntry'),
            'simpleapi/Entry' => array('action' => 'vandelay/handleEntry'),
            'vandelay/Entry' => array('action' => 'vandelay/handleEntry'),
            'actions/simpleapi/getLocales' => array('action' => 'vandelay/getLocales'),
            'simpleapi/Singles' => array('action' => 'vandelay/getSingles'),
            'vandelay/Singles' => array('action' => 'vandelay/getSingles'),
            'vandelay/Globals/(?P<locale>[a-z\_]+)' => array('action' => 'vandelay/getGlobals'),
            'vandelay/uploadEntry' => array('action' => 'vandelay/uploadEntry'),
            'vandelay/getSection/(?P<section>[a-zA-Z\_]+)' => array('action' => 'vandelay/getSectionEntries'),
        ];
    }
}