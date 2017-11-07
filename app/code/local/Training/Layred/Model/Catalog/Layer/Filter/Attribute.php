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
class Training_Layred_Model_Catalog_Layer_Filter_Attribute extends Mage_Catalog_Model_Layer_Filter_Attribute
{

    protected $_values = array();

    public function getValues()
    {
        return $this->_values;
    }

    /**
     * Apply attribute filter to layer
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param object $filterBlock
     * @return Enterprise_Search_Model_Catalog_Layer_Filter_Attribute
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        if (!Mage::helper('training_layred')->isEnabled()) {
            return parent::apply($request, $filterBlock);
        }

        $filter = $request->getParam($this->_requestVar);
        if (is_array($filter)) {
            return $this;
        }

        if (empty($filter)) {
            return $this;
        }

        $this->_values = explode(Training_Layred_Helper_Data::MULTIPLE_FILTERS_DELIMITER, $filter);

        if (!empty($this->_values)) {
            $attrUrlKeyModel = Mage::getResourceModel('training_layred/attribute_urlkey');
            $this->_getResource()->applyFilterToCollection($this, $this->_values);
            foreach ($this->_values as $filter) {
                $optionId = $attrUrlKeyModel->getOptionId($this->getAttributeModel()->getId(), $filter);
                $text = $this->_getOptionText($optionId);
                $this->getLayer()->getState()->addFilter($this->_createItem($text, $filter));
                // process all items if multiple choice is enabled
                if (!Mage::helper('training_layred')->isMultipleChoiceFiltersEnabled()) {
                    $this->_items = array();
                }
            }
        }

        return $this;
    }

    /**
     * Get data array for building attribute filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        if (!Mage::helper('training_layred')->isEnabled()) {
            return parent::_getItemsData();
        }

        $attribute = $this->getAttributeModel();

        $key = $this->getLayer()->getStateKey() . '_' . $this->_requestVar;
        $data = $this->getLayer()->getAggregator()->getCacheData($key);

        if ($data === null) {
            $attrUrlKeyModel = Mage::getResourceModel('training_layred/attribute_urlkey');
            $options = $attribute->getFrontend()->getSelectOptions();
            $optionsCount = $this->_getResource()->getCount($this);
            $data = array();
            foreach ($options as $option) {
                if (is_array($option['value'])) {
                    continue;
                }
                if (Mage::helper('core/string')->strlen($option['value'])) {
                    // Check filter type
                    if ($this->_getIsFilterableAttribute($attribute) == self::OPTIONS_ONLY_WITH_RESULTS) {
                        if (!empty($optionsCount[$option['value']])) {
                            $data[] = array(
                                'label' => $option['label'],
                                'value' => $attrUrlKeyModel->getUrlValue($attribute->getId(), $option['value']),
                                'count' => $optionsCount[$option['value']],
                            );
                        }
                    } else {
                        $data[] = array(
                            'label' => $option['label'],
                            'value' => $attrUrlKeyModel->getUrlValue($attribute->getId(), $option['value']),
                            'count' => isset($optionsCount[$option['value']]) ? $optionsCount[$option['value']] : 0,
                        );
                    }
                }
            }

            $tags = array(
                Mage_Eav_Model_Entity_Attribute::CACHE_TAG . ':' . $attribute->getId()
            );

            $tags = $this->getLayer()->getStateTags($tags);
            $this->getLayer()->getAggregator()->saveCacheData($data, $key, $tags);
        }
        
        return $data;
    }

    /**
     * Set request variable name which is used for apply filter
     *
     * @param   string $varName
     * @return  Mage_Catalog_Model_Layer_Filter_Abstract
     */
    public function setRequestVar($varName)
    {
        if (Mage::helper('training_layred')->isEnabled()) {
            $attrUrlKeyModel = Mage::getResourceModel('training_layred/attribute_urlkey');
            $varName = $attrUrlKeyModel->getUrlKey($varName);
        }

        return parent::setRequestVar($varName);
    }

}
