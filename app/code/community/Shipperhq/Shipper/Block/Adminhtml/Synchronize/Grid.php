<?php
/**
 *
 * Webshopapps Shipping Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * Shipper HQ Shipping
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipping_Carrier
 * @copyright Copyright (c) 2014 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */


class Shipperhq_Shipper_Block_Adminhtml_Synchronize_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function _construct()
    {
        parent::_construct();
        $this->setId('synchronizeGrid');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->_filterVisibility = false;
        $this->_pagerVisibility  = false;
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('shipperhq_shipper/attributeupdate')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('attribute_code', array(
            'header'    => Mage::helper('shipperhq_shipper')->__('Attribute'),
            'align'     =>'left',
            'width'     => '400px',
            'index'     => 'attribute_code',
        ));

        $this->addColumn('value', array(
            'header'    => Mage::helper('shipperhq_shipper')->__('Value'),
            'align'     =>'left',
            'index'     => 'value',
        ));

        $this->addColumn('status', array(
            'header'    => Mage::helper('shipperhq_shipper')->__('Status'),
            'align'     =>'left',
            'index'     => 'status',
            'width'     => '150',
            'frame_callback' => array($this, 'decorateStatus')

        ));
        $this->setPagerVisibility(true);

        return parent::_prepareColumns();
    }


    /**
     * Decorate status column values
     *
     * @return string
     */
    public function decorateStatus($value, $row, $column, $isExport)
    {
        $class = '';
        if ($value == Shipperhq_Shipper_Model_Synchronize::REMOVE_ATTRIBUTE_OPTION) {
            $cell = '<span class="grid-severity-critical"><span>'.$this->__($value).'</span></span>';
        } else {
           $cell = $this->__($value);

        }
        return $cell;
    }
}