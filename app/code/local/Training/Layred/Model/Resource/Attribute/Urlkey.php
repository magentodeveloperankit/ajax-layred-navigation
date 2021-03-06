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
 * @author     Training Core Team <ankits.jaiswal23@gmail.com>
 */
class Training_Layred_Model_Resource_Attribute_Urlkey extends Mage_Core_Model_Resource_Db_Abstract
{

    protected static $_cachedResults;

    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('training_layred/attribute_url_key', 'id');
    }

    /**
     * Retrieve urk_key for specific attribute code
     *
     * @param string $attributeCode
     * @param int $storeId
     * @return string
     */
    public function getUrlKey($attributeCode, $storeId = null)
    {
        foreach ($this->getAttributeData($storeId, $attributeCode, 'attribute_code') as $result) {
            if ($result['attribute_code'] == $attributeCode) {
                return $result['url_key'];
            }
        }

        return $attributeCode;
    }

    /**
     * Retrieve url_value for specific option
     *
     * @param int $attributeId
     * @param int $optionId
     * @param int $storeId
     * @return int|string
     */
    public function getUrlValue($attributeId, $optionId, $storeId = null)
    {
        foreach ($this->getAttributeData($storeId, $attributeId) as $result) {
            if ($result['option_id'] == $optionId) {
                return $result['url_value'];
            }
        }

        return $optionId;
    }

    /**
     * Retrieve option_id for specific url_value
     * 
     * @param int $attributeId
     * @param string $urlValue
     * @param int $storeId
     * @return int|string
     */
    public function getOptionId($attributeId, $urlValue, $storeId = null)
    {
        foreach ($this->getAttributeData($storeId, $attributeId) as $result) {
            if ($result['url_value'] == $urlValue) {
                return $result['option_id'];
            }
        }

        return $urlValue;
    }

    /**
     * Retrieve attribute data
     *
     * @param int $storeId
     * @param int|string $whereValue
     * @param string $whereField
     * @return array
     */
    protected function getAttributeData($storeId, $whereValue, $whereField = 'attribute_id')
    {
        if ($storeId === null) {
            $storeId = Mage::app()->getStore()->getId();
        }

        if (!isset(self::$_cachedResults[$whereValue][$storeId])) {
            $readAdapter = $this->_getReadAdapter();
            $select = $readAdapter->select()
                ->from($this->getMainTable())
                ->where('store_id = ?', $storeId)
                ->where("{$whereField} = ?", $whereValue);
            $data = $readAdapter->fetchAll($select);

            if (!empty($data)) {
                self::$_cachedResults[$data[0]['attribute_id']][$storeId] = $data;
                self::$_cachedResults[$data[0]['attribute_code']][$storeId] = $data;
            } else {
                self::$_cachedResults[$whereValue][$storeId] = $data;
            }
        }

        return self::$_cachedResults[$whereValue][$storeId];
    }

    /**
     * Load attributes options from the database
     * 
     * @param Mage_Catalog_Model_Resource_Product_Attribute_Collection $collection
     * @return Training_Layred_Model_Resource_Attribute_Urlkey
     */
    public function preloadAttributesOptions(Mage_Catalog_Model_Resource_Product_Attribute_Collection $collection, $storeId = null)
    {
        if ($storeId === null) {
            $storeId = Mage::app()->getStore()->getId();
        }

        $attributesIds = array();
        foreach ($collection as $attribute) {
            $attributesIds[] = $attribute->getId();
        }
        
        if (empty($attributesIds)) {
            return $this;
        }

        $readAdapter = $this->_getReadAdapter();
        $select = $readAdapter->select()
            ->from($this->getMainTable())
            ->where('store_id = ?', $storeId)
            ->where('attribute_id IN (?)', array('in' => $attributesIds));

        $data = $readAdapter->fetchAll($select);
        foreach ($data as $attr) {
            self::$_cachedResults[$attr['attribute_id']][$attr['store_id']][] = $attr;
            self::$_cachedResults[$attr['attribute_code']][$attr['store_id']][] = $attr;
        }

        // Fill with empty array for the attributes ids that have no values in database
        // Prevents from doing supplementary queries
        foreach ($collection as $attribute) {
            if (!isset(self::$_cachedResults[$attribute->getId()][$storeId])) {
                self::$_cachedResults[$attribute->getId()][$storeId] = array();
                self::$_cachedResults[$attribute->getAttributeCode()][$storeId] = array();
            }
        }

        return $this;
    }

}
