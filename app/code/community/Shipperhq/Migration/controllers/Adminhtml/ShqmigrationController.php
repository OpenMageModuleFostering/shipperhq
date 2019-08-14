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

class Shipperhq_Migration_Adminhtml_ShqmigrationController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Migration Manager'), Mage::helper('adminhtml')->__('Migration Manager'));
        return $this;
    }

    public function indexAction()
    {
        $store = $this->getRequest()->getParam('store');

        $this->loadLayout();

        //get config of required migratable attributes
        $requiredElements = Mage::helper('shipperhq_migration')->getAttributesToMigrate();
        $switcherBlock = $this->getLayout()
            ->createBlock('adminhtml/store_switcher')
            ->setId('store_switcher')
            ->setName('store_switcher')
            ->setDefaultStoreName($this->__('Default Values'));
        $this->_addContent($switcherBlock);

        foreach($requiredElements as $attributeToMigrate)
        {
            $moduleId = str_replace(' ','_',$attributeToMigrate['title']);
            $block = $this->getLayout()
               ->createBlock('shipperhq_migration/adminhtml_migration_migratable', $moduleId)
                ->setModuleId($moduleId)
                ->setModuleTitle($attributeToMigrate['title'])
                ->setExtension($attributeToMigrate)
                ->setStore($store);

            $this->_addContent($block);
        }

        $this->renderLayout();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/shipperhq');
    }

}
