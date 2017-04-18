<?php
/**
 * SEOmatic plugin for Craft CMS 3.x
 *
 * A turnkey SEO implementation for Craft CMS that is comprehensive, powerful,
 * and flexible
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2017 nystudio107
 */

namespace nystudio107\seomatic\services;

use nystudio107\seomatic\models\MetaBundle;

use Craft;
use craft\base\Component;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\services\Categories;

/**
 * @author    nystudio107
 * @package   Seomatic
 * @since     3.0.0
 */
class Helper extends Component
{
    // Protected Properties
    // =========================================================================

    /**
     * @var MetaBundle[]
     */
    protected $_metaBundles;

    // Public Methods
    // =========================================================================

    /**
     * @param string $handle
     * @param int    $siteId
     *
     * @return MetaBundle
     */
    public function metaBundleByHandle(string $handle, int $siteId = 1): MetaBundle
    {
        // @todo this should look in the seomatic_meta_bundles db table
        $metaBundles = $this->metaBundles();
        /** @var  $metaBundle MetaBundle */
        foreach ($metaBundles as $metaBundle) {
            if ($handle === $metaBundle->sourceHandle && $siteId == $metaBundle->sourceSiteId) {
                return $metaBundle;
            }
        }

        return null;
    }

    /**
     * Return all of the Meta Bundles
     *
     * @return array
     */
    public function metaBundles(): array
    {
        if ($this->_metaBundles) {
            return $this->_metaBundles;
        }
        // @todo this should look in the seomatic_meta_bundles db table
        $metaBundles = [];

        // Get all of the sections with URLs
        $sections = Craft::$app->getSections()->getAllSections();
        foreach ($sections as $section) {
            // Get the site settings and turn them into arrays
            $siteSettings = $section->getSiteSettings();
            $siteSettingsArray = [];
            /** @var  $siteSetting Section_SiteSettings */
            foreach ($siteSettings as $siteSetting) {
                if ($siteSetting->hasUrls) {
                    $siteSettingArray = $siteSetting->toArray();
                    // Get the site language
                    $site = Craft::$app->getSites()->getSiteById($siteSetting->siteId);
                    $language = $site->language;
                    $language = strtolower($language);
                    $language = str_replace('_', '-', $language);
                    $siteSettingArray['language'] = $language;
                    $siteSettingsArray[] = $siteSettingArray;
                }
            }
            $siteSettingsArray = ArrayHelper::index($siteSettingsArray, 'siteId');
            // Get a MetaBundle for each site
            foreach ($siteSettings as $siteSetting) {
                if ($siteSetting->hasUrls) {
                    $metaBundle = new MetaBundle([
                        'sourceElementType'  => Entry::class,
                        'sourceId'           => $section->id,
                        'sourceName'         => $section->name,
                        'sourceHandle'       => $section->handle,
                        'sourceType'         => $section->type,
                        'sourceTemplate'     => $siteSetting->template,
                        'sourceSiteId'       => $siteSetting->siteId,
                        'sourceAltSiteSettings' => $siteSettingsArray,
                    ]);
                    $metaBundles[] = $metaBundle;
                }
            }
        }

        // @todo Get all of the categories with URLs

        // @todo Get all of the Commerce Products with URLs

        $this->_metaBundles = $metaBundles;

        return $metaBundles;
    }
}