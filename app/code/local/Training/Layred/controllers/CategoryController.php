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
require_once 'Mage/Catalog/controllers/CategoryController.php';

class Training_Layred_CategoryController extends Mage_Catalog_CategoryController
{

    public function viewAction()
    {
        if (($category = $this->_initCatagory())) {
            $design = Mage::getSingleton('catalog/design');
            $settings = $design->getDesignSettings($category);

            // apply custom design
            if ($settings->getCustomDesign()) {
                $design->applyCustomDesign($settings->getCustomDesign());
            }

            Mage::getSingleton('catalog/session')->setLastViewedCategoryId($category->getId());

            $update = $this->getLayout()->getUpdate();
            $update->addHandle('default');

            if (!$category->hasChildren()) {
                $update->addHandle('catalog_category_layered_nochildren');
            }

            $this->addActionLayoutHandles();
            $update->addHandle($category->getLayoutUpdateHandle());
            $update->addHandle('CATEGORY_' . $category->getId());
            // apply custom ajax layout
            if (Mage::helper('training_layred')->isAjaxEnabled() && Mage::helper('training_layred')->isAjaxRequest()) {
                $update->addHandle('catalog_category_layered_ajax_layer');
            }
            $this->loadLayoutUpdates();

            // apply custom layout update once layout is loaded
            if (($layoutUpdates = $settings->getLayoutUpdates())) {
                if (is_array($layoutUpdates)) {
                    foreach ($layoutUpdates as $layoutUpdate) {
                        $update->addUpdate($layoutUpdate);
                    }
                }
            }

            $this->generateLayoutXml()->generateLayoutBlocks();
            // apply custom layout (page) template once the blocks are generated
            if ($settings->getPageLayout()) {
                $this->getLayout()->helper('page/layout')->applyTemplate($settings->getPageLayout());
            }

            if (($root = $this->getLayout()->getBlock('root'))) {
                $root->addBodyClass('categorypath-' . $category->getUrlPath())
                    ->addBodyClass('category-' . $category->getUrlKey());
            }

            $this->_initLayoutMessages('catalog/session');
            $this->_initLayoutMessages('checkout/session');

            // return json formatted response for ajax
            if (Mage::helper('training_layred')->isAjaxEnabled() && Mage::helper('training_layred')->isAjaxRequest()) {
                

                    $block = $this->getLayout()->getBlock('catalog.leftnav');

                
                $listing = $this->getLayout()->getBlock('product_list')->toHtml();
                $layer = $block->toHtml();
                
                // Fix urls that contain '___SID=U'
                $urlModel = Mage::getSingleton('core/url');
                $listing = $urlModel->sessionUrlVar($listing);
                $layer = $urlModel->sessionUrlVar($layer);

                $response = array(
                    'listing' => $listing,
                    'layer' => $layer
                );

                $this->getResponse()->setHeader('Content-Type', 'application/json', true);
                $this->getResponse()->setBody(json_encode($response));
            } else {
                $this->renderLayout();
            }
        } elseif (!$this->getResponse()->isRedirect()) {
            $this->_forward('noRoute');
        }
    }

}
