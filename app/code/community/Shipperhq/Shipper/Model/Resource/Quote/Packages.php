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

class Shipperhq_Shipper_Model_Resource_Quote_Packages extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('shipperhq_shipper/quote_packages', 'package_id');
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object) {
        parent::_afterLoad($object);

        $itemSelect = $this->_getReadAdapter()->select()
            ->from($this->getTable('quote_package_items'))
            ->where('package_id=?', $object->getId());

        $items = $this->_getReadAdapter()->fetchAll($itemSelect);

        $object->setItems($items);
        return $this;
    }


    protected function _afterSave(Mage_Core_Model_Abstract $object) {
        parent::_afterSave($object);

        // now save the package items
        try {
            $condition = $this->_getWriteAdapter()->quoteInto('package_id = ?', $object->getId());
            $this->_getWriteAdapter()->delete($this->getTable('quote_package_items'), $condition);
            foreach ($object->getData('items') as $item) {
                $itemInsert = new Varien_Object();
                $itemInsert->setData('package_id',$object->getId());
                $itemInsert->setSku($item->sku);
                $itemInsert->setWeightPacked($item->weightPacked);
                $itemInsert->setQtyPacked($item->qtyPacked);
                $this->_getWriteAdapter()->insert($this->getTable('quote_package_items'), $itemInsert->getData());
            }
            $this->_getWriteAdapter()->commit();

        }
        catch (Exception  $e) {
            Mage::throwException($e);
            $this->_getWriteAdapter()->rollBack();
        }
        return $this;
    }
}