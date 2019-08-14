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

class Shipperhq_Shipper_Model_Resource_Storage
    extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Storage is going to be fully attached to quote
     * 
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    /**
     * Sets some basic model data
     * 
     */
    protected function _construct()
    {
        $this->_init('shipperhq_shipper/storage', 'quote_id');
    }

    /**
     * Validate quote identifier
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return string
     */
    public function validateQuoteId($quote)
    {
        $select = $this->_getReadAdapter()->select();
        $select->from($this->getTable('sales/quote'), 'entity_id')
            ->where('entity_id = ?', $quote->getId());

        return $this->_getReadAdapter()->fetchOne($select);
    }

    /**
     * Decodes storage data after object has been saved
     * 
     * @param Mage_Core_Model_Abstract $object
     * @return $this
     */
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        parent::_afterLoad($object);
        $this->_decodeJsonData($object);
        return $this;
    }

    /**
     * Encodes storage data before save operation takes place
     * 
     * @param Mage_Core_Model_Abstract $object
     * @return $this
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        parent::_beforeSave($object);
        $this->_encodeJsonData($object);
        return $this;
    }

    /**
     * Decodes storage data after save operation takes place
     *
     * @param Mage_Core_Model_Abstract $object
     * @return $this
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        parent::_afterSave($object);
        $this->_decodeJsonData($object);
        return $this;
    }

    /**
     * Decodes JSON data and sets into object
     * 
     * @param Mage_Core_Model_Abstract $object
     * @return $this
     */
    protected function _decodeJsonData(Mage_Core_Model_Abstract $object)
    {
        if ($object->hasData('data')) {
            $data = @json_decode($object->getData('data'), true);
            if (is_array($data)) {
                $data = $this->_filterJsonData($data);
                $object->addData($data);
            }
            
        }
        
        return $this;
    }

    /**
     * Encodes JSON data from object and sets required database property
     *
     * @param Mage_Core_Model_Abstract $object
     * @return $this
     */
    protected function _encodeJsonData(Mage_Core_Model_Abstract $object)
    {
        $data = $object->getData();
        $data = $this->_filterJsonData($data);
        
        foreach (array_keys($data) as $key) {
            $object->unsetData($key);
        }
        
        $object->setData('data', json_encode($data));
        return $this;
    }

    /**
     * Filters JSON data from unexpected field values
     * 
     * @param array $data
     * @return array
     */
    protected function _filterJsonData(array $data)
    {
        if (isset($data[$this->getIdFieldName()])) {
            unset($data[$this->getIdFieldName()]);
        }
        
        if (isset($data['data'])) {
            unset($data['data']);
        }
        
        return $data;
    }
}
