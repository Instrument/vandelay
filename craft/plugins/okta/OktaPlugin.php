<?php
/**
 * Okta plugin for Craft CMS
 *
 * A saml connector for craft cms
 *
 * @author    Inger Kitkatz
 * @copyright Copyright (c) 2017 Inger Kitkatz
 * @link      instrument.com
 * @package   Okta
 * @since     0.0.1
 */

namespace Craft;

class OktaPlugin extends BasePlugin
{
    /**
     * @return mixed
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @return mixed
     */
    public function getName()
    {
         return Craft::t('Okta');
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return Craft::t('A saml connector for craft cms');
    }

    /**
     * @return string
     */
    public function getDocumentationUrl()
    {
        return '???';
    }

    /**
     * @return string
     */
    public function getReleaseFeedUrl()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return '0.0.1';
    }

    /**
     * @return string
     */
    public function getSchemaVersion()
    {
        return '0.0.1';
    }

    /**
     * @return string
     */
    public function getDeveloper()
    {
        return 'Inger Klekacz';
    }

    /**
     * @return string
     */
    public function getDeveloperUrl()
    {
        return 'instrument.com';
    }

    /**
     * @return bool
     */
    public function hasCpSection()
    {
        return false;
    }

    public function registerSiteRoutes()
    {

      return array(
          'test' => array('action' => 'okta/test'),
          'ssologin' => array('action' => 'okta/ssologin', "null"),
          'acs' => array('action' => 'okta/acs'),
          'contentpreview/(?P<originalUri>[-\w\/!]+)' => array('action' => 'okta/contentpreview'),
      );
    }
}
