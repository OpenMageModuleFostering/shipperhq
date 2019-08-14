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
 *
 * Shipper Storage
 */
class Shipperhq_Shipper_Model_Storage
    extends Mage_Core_Model_Abstract
{
    /**
     * Quote object to which storage is attached
     * 
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote;

    /**
     * Stored addresses details
     * 
     * @var Mage_Sales_Model_Quote_Address[]
     */
    protected $_storedAddresses = array();

    /**
     * Flag to indicate if storage has been loaded for quote
     * 
     * @var bool
     */
    protected $_isLoaded = false;

    /**
     * Storage option
     * 
     */
    protected function _construct()
    {
        $this->_init('shipperhq_shipper/storage');
    }

    /**
     * Loads data by quote object
     * 
     * @param Mage_Sales_Model_Quote $quote
     * @return $this
     */
    public function loadByQuote(Mage_Sales_Model_Quote $quote)
    {
        $this->_quote = $quote;
        $this->_storedAddresses = array();
        
        if ($quote->getId()) {
            $this->load($quote->getId());
            $this->_isLoaded = !$this->isEmpty();
            $this->setId($quote->getId());
        }
        
        return $this;
    }

    /**
     * Returns true if object does not store any data except quote identifier
     * 
     * @return bool
     */
    public function isEmpty()
    {
        return count(array_diff(array_keys($this->_data), array($this->getIdFieldName(), 'id'))) === 0;
    }

    /**
     * Returns if storage object is still valid
     * and has quote as not deleted
     *
     * @return bool
     */
    public function isValid($validateDatabase = false)
    {
        return $this->_quote !== null
               && $this->_quote->isDeleted() === false
               && $this->_quote->getId()
               && (!$validateDatabase
                    || $this->_getResource()->validateQuoteId($this->_quote));
    }

    /**
     * Returns is loaded flag
     * 
     * @return bool
     */
    public function isLoaded()
    {
        return $this->_isLoaded;
    }

    /**
     * Updates address list within a storage
     * 
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        if ($this->_quote) {
            $this->setId($this->_quote->getId());
        }
        
        foreach ($this->_storedAddresses as $addressHash => $address) {
            if ($address->getId()) {
                $this->setData(sprintf('address_%s', $address->getId()), $this->getDataForAddress($address));
            }
            
            $this->unsetData(sprintf('address_%s', $addressHash));
        }
        
        $this->_storedAddresses = array();
        return parent::_beforeSave();
    }


    /**
     * Overridden to support session like behavior with data clean up
     * 
     * @param string $key
     * @param null|bool $index
     * @return mixed
     */
    public function getData($key = '', $index = null)
    {
        if ($index === true) {
            $data = $this->getData($key);
            $this->unsetData($key);
            return $data;
        }
        
        return parent::getData($key, $index);
    }

    /**
     * Sets a data for an address object
     * 
     * @param Mage_Core_Model_Abstract $address
     * @param string|array $key
     * @param mixed|null $value
     * @return $this
     */
    public function setDataForAddress(Mage_Core_Model_Abstract $address, $key, $value = null)
    {
        $this->_validateAddress($address);
        $addressKey = $address->getId();
        
        if ($addressKey === null) {
            $addressKey = spl_object_hash($address);
            $this->_storedAddresses[$addressKey] = $address;
        }
        
        if (is_array($key)) {
            $this->setData(sprintf('address_%s', $addressKey), $key);
        } else {
            $data = $this->getDataForAddress($address);
            $data[$key] = $value;
            $this->setData(sprintf('address_%s', $addressKey), $key);
        }
        
        return $this;
    }

    /**
     * Returns a data for address entity
     * 
     * @param Mage_Core_Model_Abstract $address
     * @param null|string $key
     * @return array|mixed|null
     */
    public function getDataForAddress(Mage_Core_Model_Abstract $address, $key = null)
    {
        $this->_validateAddress($address);
        $addressKey = $address->getId();

        if ($addressKey === null) {
            $addressKey = spl_object_hash($address);
            if (!isset($this->_storedAddresses[$addressKey])) {
                return array();
            }
        }

        $data = $this->getData(sprintf('address_%s', $addressKey));
        if (!is_array($data)) {
            $data = array();
        }
        
        if ($key === null) {
            return $data;
        } elseif (isset($data[$key])) {
            return $data[$key];
        }
        
        return null;
    }
    
    /**
     * Validates address quote
     *
     * @param Mage_Core_Model_Abstract $address
     * @return $this
     */
    protected function _validateAddress(Mage_Core_Model_Abstract $address)
    {
        if ($this->_quote === null) {
            throw new RuntimeException('Quote object is not specified, cannot set address specific data');
        }

        if ($this->_quote !== $address->getQuote()) {
            throw new RuntimeException('Quote object does not match address model');
        }
        
        return $this;
    }
}
