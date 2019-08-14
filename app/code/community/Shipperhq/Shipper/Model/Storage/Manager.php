<?php

/**
 * Storage manager
 * 
 * Used to collect storage per quote 
 * 
 */
class Shipperhq_Shipper_Model_Storage_Manager
{
    /**
     * Storage by quote registry
     * 
     * @var Shipperhq_Shipper_Model_Storage[]
     */
    protected $_storageByQuote = array();

    /**
     * Returns a storage by quote
     * 
     * @param Mage_Sales_Model_Quote $quote
     * @return Shipperhq_Shipper_Model_Storage
     */
    public function findByQuote(Mage_Sales_Model_Quote $quote)
    {
        $this->updateQuote($quote);
        
        $key = $quote->getId();
        if ($key === null) {
            $key = spl_object_hash($quote);
        }
        
        if (!isset($this->_storageByQuote[$key])) {
            $this->_storageByQuote[$key] = Mage::getModel('shipperhq_shipper/storage');
            $this->_storageByQuote[$key]->loadByQuote($quote);
        }
        
        return $this->_storageByQuote[$key];
    }

    /**
     * Returns list of all storage objects
     * 
     * @return Shipperhq_Shipper_Model_Storage[]
     */
    public function getStorageObjects()
    {
        return array_values($this->_storageByQuote);
    }

    /**
     * Updates storage by quote
     * 
     * @param Mage_Sales_Model_Quote $quote
     * @return $this
     */
    public function updateQuote(Mage_Sales_Model_Quote $quote)
    {
        if (isset($this->_storageByQuote[spl_object_hash($quote)]) && $quote->getId()) {
            $this->_storageByQuote[$quote->getId()] = $this->_storageByQuote[spl_object_hash($quote)];
            unset($this->_storageByQuote[spl_object_hash($quote)]);
            $this->_storageByQuote[$quote->getId()]->setId($quote->getId());
        }
        
        return $this;
    }
}
