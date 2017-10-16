<?php
/**
 * Prewind plugin for Craft CMS
 *
 * Prewind Service
 *
 * @author    Mort
 * @copyright Copyright (c) 2017 Mort
 * @link      github.com/mortscode
 * @package   Prewind
 * @since     1.0.0
 */

namespace Craft;

class PrewindService extends BaseApplicationComponent
{
    public function transformAsset (AssetFileModel $asset)
    {
        if ($asset->kind !== 'image') {
            return true;
        }
        
        $imageExtensions = array("jpg", "jpeg", "png");

        if (in_array(strtolower($asset->extension), $imageExtensions)) {
            $fullWidth = $asset->getUrl(['width' => $asset->width]);
            $halfWidth = $asset->getUrl(['width' => $asset->width / 2]);
        } else {
            return true;
        }
    }
}