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
class Training_Layred_Model_Resource_Indexer_Attribute extends Mage_Index_Model_Resource_Abstract
{

    /**
     * @var array
     */
    protected $storesIds;

    /**
     * @var Training_Layred_Helper_Data
     */
    protected $helper;

    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('training_layred/attribute_url_key', 'id');
    }

    /**
     * Reindex all
     *
     * @return Training_Layred_Model_Resource_Indexer_Attribute
     */
    public function reindexAll()
    {
        $this->reindexLayredUrlKeys();
        return $this;
    }

    /**
     * Generate LAYRED values for catalog product attributes options
     *
     * @param int|null $attributeId - transmit this to limit processing to one specific attribute
     * @return Training_Layred_Model_Resource_Indexer_Attribute
     */
    public function reindexLayredUrlKeys($attributeId = null)
    {
        $attributes = $this->getAttributes($attributeId);

        foreach ($attributes as $attribute) {
            if ($attribute->usesSource()) {
                $this->reindexAttribute($attribute);
            }
        }

        return $this;
    }

    /**
     * Retrieve filterable product attributes with frontend input type 'select' and 'multiselect'
     *
     * @param int|null $attributeId
     * @return Mage_Eav_Model_Resource_Entity_Attribute_Collection
     */
    protected function getAttributes($attributeId = null)
    {
        $collection = Mage::getSingleton('eav/config')
            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)
            ->getAttributeCollection()
            ->addFieldToFilter('main_table.frontend_input', array('in' => array('select', 'multiselect')))
            ->addFieldToFilter(array('is_filterable', 'is_filterable_in_search'), array(array('eq' => 1), array('eq' => 1)));
        //->addSetInfo();
        if (!empty($attributeId)) {
            $collection->addFieldToFilter('main_table.attribute_id', $attributeId);
        }

        return $collection;
    }

    /**
     * Reindex attribute
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @throws Exception
     */
    protected function reindexAttribute($attribute)
    {
        $this->beginTransaction();
        try {
            $writeAdapter = $this->_getWriteAdapter();

            $writeAdapter->delete($this->getMainTable(), array("attribute_id = ?" => $attribute->getId()));
            $writeAdapter->insertMultiple($this->getMainTable(), $this->getInsertValues($attribute));

            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Retrieve data to be inserted
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @return array
     */
    protected function getInsertValues($attribute)
    {
        $data = array();
        foreach ($this->getAllStoresIds() as $storeId) {
            $attribute->setStoreId($storeId);
            if ($attribute->getSourceModel()) {
                $options = $attribute->getSource()->getAllOptions(false);
            } else {
                $collection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                    ->setStoreFilter($storeId)
                    ->setPositionOrder('asc')
                    ->setAttributeFilter($attribute->getId())
                    ->load();
                $options = $collection->toOptionArray();
            }

            foreach ($options as $option) {
                // Generate url value
                $urlValue = $this->getHelper()->transliterate($option['label']);
                $urlKey = $this->getHelper()->transliterate($attribute->getStoreLabel($storeId));

                // Check if this url value is taken and add -{count}
                $countValue = 0;
                $origUrlValue = $urlValue;
                do {
                    $found = false;
                    foreach ($data as $line) {
                        if ($line['store_id'] == $storeId && $line['url_value'] == $urlValue) {
                            $urlValue = $origUrlValue . '-' . ++$countValue;
                            $found = true;
                        }
                    }
                } while ($found);

                // Check if this url key is taken and add -{count}
                $countKey = 0;
                $origUrlKey = $urlKey;
                do {
                    $found = false;
                    if ($this->urlKeyExists($attribute->getId(), $urlKey)) {
                        $urlKey = $origUrlKey . '-' . ++$countKey;
                        $found = true;
                    }
                } while ($found);

                $data[] = array(
                    'attribute_code' => $attribute->getAttributeCode(),
                    'attribute_id' => $attribute->getId(),
                    'store_id' => $storeId,
                    'option_id' => $option['value'],
                    'url_key' => $urlKey,
                    'url_value' => $urlValue,
                );
            }
        }

        return $data;
    }

    /**
     * @param int $attributeId
     * @param string $urlKey
     * @return bool
     */
    protected function urlKeyExists($attributeId, $urlKey)
    {
        $readAdapter = $this->_getReadAdapter();
        $select = $readAdapter->select()
            ->from($this->getMainTable(), array('attribute_id'))
            ->where('attribute_id != ?', $attributeId)
            ->where('url_key = ?', $urlKey)
            ->limit(1);

        return (bool) $readAdapter->fetchOne($select);
    }

    /**
     * Retrieve all stores ids
     *
     * @return array
     */
    protected function getAllStoresIds()
    {
        if ($this->storesIds === null) {
            $this->storesIds = array();
            $stores = Mage::app()->getStores();
            foreach ($stores as $storeId => $store) {
                $this->storesIds[] = $storeId;
            }
        }

        return $this->storesIds;
    }

    /**
     * Retrieve helper object
     *
     * @return Training_Layred_Helper_Data
     */
    protected function getHelper()
    {
        if ($this->helper === null) {
            $this->helper = Mage::helper('training_layred');
        }

        return $this->helper;
    }

    /**
     * Reindex attribute options on attribute save event
     *
     * @param Mage_Index_Model_Event $event
     * @return Training_Layred_Model_Resource_Indexer_Attribute
     */
    public function catalogEavAttributeSave(Mage_Index_Model_Event $event)
    {
        $attribute = $event->getDataObject();
        $this->reindexLayredUrlKeys($attribute->getId());

        return $this;
    }

}
