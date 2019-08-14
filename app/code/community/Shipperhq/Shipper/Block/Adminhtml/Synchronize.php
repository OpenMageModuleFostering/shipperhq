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

class Shipperhq_Shipper_Block_Adminhtml_Synchronize extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_synchronize';
        $this->_blockGroup = 'shipperhq_shipper';
        $this->_headerText = Mage::helper('shipperhq_shipper')->__('Synchronize with ShipperHQ');

        parent::__construct();
        $this->_removeButton('add');
        $this->_addButton('refresh', array(
            'label'     => 'Refresh Comparison',
            'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/refresh') .'\')',
            'class'     => 'add',
        ));

        // 'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/synchronize') .'\')',
        $this->_addButton('synchronize', array(
            'label'     => 'Synchronize with ShipperHQ',
            'onclick' => 'confirmSetLocation(\''
            . Mage::helper('shipperhq_shipper')->__('Are you sure?').'\', \''.$this->getUrl('*/*/synchronize').'\')',
            'class'     => 'add',
        ));


    }
}