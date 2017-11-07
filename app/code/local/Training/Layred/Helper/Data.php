<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Training
 * @package     Training_Layred
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 *
 *
 * @category   Training
 * @package    Training_Layred
 * @author     Training Core Team <ankit.jaiswal@perficient.com>
 */
class Training_Layred_Helper_Data extends Mage_Core_Helper_Data
{
    /**
     * Delimiter for multiple filters
     */

    const MULTIPLE_FILTERS_DELIMITER = ',';

    /**
     * Check if module is enabled or not
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return Mage::getStoreConfigFlag('training_layred/catalog/enabled');
    }

    /**
     * Check if ajax is enabled
     *
     * @return boolean
     */
    public function isAjaxEnabled()
    {
        if (!$this->isEnabled()) {
            return false;
        }
        return Mage::getStoreConfigFlag('training_layred/catalog/ajax_enabled');
    }

    /**
     * Check if multiple choice filters is enabled
     *
     * @return boolean
     */
    public function isMultipleChoiceFiltersEnabled()
    {
        if (!$this->isEnabled()) {
            return false;
        }
        return Mage::getStoreConfigFlag('training_layred/catalog/multiple_choice_filters');
    }

    /**
     * Retrieve routing suffix
     *
     * @return string
     */
    public function getRoutingSuffix()
    {
        return '/' . Mage::getStoreConfig('training_layred/catalog/routing_suffix');
    }

    /**
     * Getter for layered navigation params
     * If $params are provided then it overrides the ones from registry
     *
     * @param array $params
     * @return array|null
     */
    public function getCurrentLayerParams(array $params = null)
    {
        $layerParams = Mage::registry('layer_params');

        if (!is_array($layerParams)) {
            $layerParams = array();
        }

        if (!empty($params)) {
            foreach ($params as $key => $value) {
                if ($value === null) {
                    unset($layerParams[$key]);
                } else {
                    $layerParams[$key] = $value;
                }
            }
        }

        unset($layerParams['isLayerAjax']);

        // Sort by key - small LAYRED improvement
        ksort($layerParams);
        return $layerParams;
    }

    /**
     * Method to get url for layered navigation
     *
     * @param array $filters      array with new filter values
     * @param boolean $noFilters  to add filters to the url or not
     * @param array $q            array with values to add to query string
     * @return string
     */
    public function getFilterUrl(array $filters, $noFilters = false, array $q = array())
    {
        $query = array(
            'isLayerAjax' => null, // this needs to be removed because of ajax request
            Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
        );
        $query = array_merge($query, $q);

        $suffix = Mage::getStoreConfig('catalog/layred/category_url_suffix');
        $params = array(
            '_current' => true,
            '_use_rewrite' => true,
            '_query' => $query
        );

        $url = Mage::getUrl('*/*/*', $params);
        $urlPath = '';

        if (isset($filters['cat'])) {
            $url = $filters['cat'];
        }

        if (!$noFilters) {
            // Add filters
            $layerParams = $this->getCurrentLayerParams($filters);
            if (isset($layerParams['cat'])) {
                unset($layerParams['cat']);
            }
            foreach ($layerParams as $key => $value) {
                // Encode and replace escaped delimiter with the delimiter itself
                $value = str_replace(urlencode(self::MULTIPLE_FILTERS_DELIMITER), self::MULTIPLE_FILTERS_DELIMITER, urlencode($value));
                $urlPath .= "/{$key}/{$value}";
            }
        }

        // Skip adding routing suffix for links with no filters
        if (empty($urlPath)) {
            return $url;
        }

        $urlParts = explode('?', $url);

        $urlParts[0] = $this->getUrlBody($suffix, $urlParts[0]);

        // Add the suffix to the url - fixes when coming from non suffixed pages
        // It should always be the last bits in the URL
        $urlParts[0] .= $this->getRoutingSuffix();

        $url = $urlParts[0] . $urlPath;
        $url = $this->appendSuffix($url, $suffix);
        if (!empty($urlParts[1])) {
            $url .= '?' . $urlParts[1];
        }

        return $url;
    }

    /**
     * Get the url path, including the base url, minus the suffix.
     * Checks for Enterprise and if it is, checks for the dot
     * before returning
     * @param  string $suffix
     * @param  string $urlParts
     * @return string
     */
    public function getUrlBody($suffix, $urlParts) {
           return substr($urlParts, 0, strlen($urlParts) - strlen($suffix));

    }

    /**
     * Appends the suffix to the url, if applicable.
     * Checks for Enterprise and if it is, adds the dot
     * before returning
     *
     * @param  string $url
     * @param  string $suffix
     * @return string
     */
    public function appendSuffix($url, $suffix) {
        if (strlen($suffix) == 0) {
            return $url;
        }
        $ds="";
        return $url . $ds . $suffix;
    }

    /**
     * Get the url to clear all layered navigation filters
     *
     * @return string
     */
    public function getClearFiltersUrl()
    {
        return $this->getFilterUrl(array(), true);
    }

    /**
     * Get url for layered navigation pagination
     *
     * @param array $query
     * @return string
     */
    public function getPagerUrl(array $query)
    {
        return $this->getFilterUrl(array(), false, $query);
    }

    /**
     * Check if we are in the catalog search
     *
     * @return boolean
     */
    public function isCatalogSearch()
    {
        $pathInfo = $this->_getRequest()->getPathInfo();
        if (stripos($pathInfo, '/catalogsearch/result') !== false) {
            return true;
        }
        return false;
    }



    /**
     * Uses transliteration tables to convert any kind of utf8 character
     *
     * @param string $text
     * @param string $separator
     * @return string $text
     */
    public function transliterate($text, $separator = '-')
    {
        if (preg_match('/[\x80-\xff]/', $text) && $text) {
            $text;
        }
        return $this->postProcessText($text, $separator);
    }



    /**
     * Cleans up the text and adds separator
     *
     * @param string $text
     * @param string $separator
     * @return string
     */
    protected function postProcessText($text, $separator)
    {
        if (function_exists('mb_strtolower')) {
            $text = mb_strtolower($text);
        } else {
            $text = strtolower($text);
        }

        // Remove all none word characters
        $text = preg_replace('/\W/', ' ', $text);

        // More stripping. Replace spaces with dashes
        $text = strtolower(preg_replace('/[^A-Z^a-z^0-9^\/]+/', $separator, preg_replace('/([a-z\d])([A-Z])/', '\1_\2', preg_replace('/([A-Z]+)([A-Z][a-z])/', '\1_\2', preg_replace('/::/', '/', $text)))));

        return trim($text, $separator);
    }


    /**
     * @return bool
     */
    public function isAjaxRequest()
    {
        $request = Mage::app()->getRequest();
        return $request->isAjax();
    }

    /**
     * Get Checkbox Attrbute Value
     *
     * @return boolean
     */
    public function isAttributeCheckbox()
    {
        return trim(Mage::getStoreConfig('training_layred/catalog/attribute_checkbox'));
    }

    /**
     * Get Radio Attrbute Value
     *
     * @return boolean
     */
    public function isAttributeRadio()
    {
        return trim(Mage::getStoreConfig('training_layred/catalog/attribute_radio'));
    }
}
