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
class Training_Layred_Block_CatalogSearch_Layer_Filter_Attribute extends Training_Layred_Block_Catalog_Layer_Filter_Attribute
{

    /**
     * Set filter model name
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->_filterModelName = 'catalogsearch/layer_filter_attribute';
    }

}
