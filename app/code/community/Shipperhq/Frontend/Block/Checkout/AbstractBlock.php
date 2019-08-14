<?php

abstract class Shipperhq_Frontend_Block_Checkout_AbstractBlock
    extends Mage_Core_Block_Template
{

    /**
     * Quote instance
     *
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote;

    /**
     * Instance of address for shipping calculations
     *
     * @var Mage_Sales_Model_Quote_Address
     */
    protected $_address;

    /**
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getAddress()
    {
        if (empty($this->_address)) {
            $this->_address = $this->getQuote()->getShippingAddress();
        }
        return $this->_address;
    }

    public function setAddress($address)
    {
        $this->_address = $address;
        return $this;
    }

    public function setQuote(Mage_Sales_Model_Quote $quote)
    {
        $this->_quote = $quote;
        return $this;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->_quote;
    }
    
}