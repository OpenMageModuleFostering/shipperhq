<?php

class Shipperhq_ShipperTest_Test_Model_Carrier_Shippertest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var Shipperhq_Shipper_Model_Carrier_shipper
     */
    protected $_model;
    protected $_helperModel;
    protected $backupGlobals = FALSE;
    protected $_quote = null;
    protected $_cart = null;

    protected $_quoteItem = null;

    protected function setUp()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->_model = Mage::getModel('shipperhq_shipper/carrier_shipper');
        $this->_helperModel = Mage::helper('shipperhq_shipper');

        $checkoutSession = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor()
            ->getMock();
        $this->replaceByMock('singleton','checkout/session', $checkoutSession);

        $this->_quote = Mage::getModel('sales/quote')->load(1);
   //     $this->_shippingAddress =
        // mock this model, and this method within the model
        $this->_cart = $this->getModelMock('checkout/cart',array('getQuote'));

        // when method getQuote is called then return $this->_quote
        $this->_cart->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($this->_quote));

        $this->replaceByMock('singleton','checkout/cart',$this->_cart);

        //$this->app()->getRequest()->setBaseUrl('http://www.localhost.com');
        //$this->registerCookieStub();
        //$_SESSION = array();

        // With this in place we are disabling the constructor
        // The consequence of this is that the session is not saved
        // Which has no impact as we are not concerned with testing this
        $customer = $this->getModelMockBuilder('customer/session')
            ->disableOriginalConstructor()
            ->setMethods(null)    // Please do not override any methods
            ->getMock();

        // All calls to Mage::getSingleton for customer/session will now return the mock object
        // Instead of the real one!
        $this->replaceByMock('singleton','customer/session',$customer);
    }

    protected function tearDown()
    {

    }

    /**
     * @loadFixture config
     */
    public function testGetQuotes() {
    /*    $request = Mage::getModel('shipping/rate_request');

        $request->setQuote($this->_quote);

        //$this->_model->setRequest($request);

        //$rawRequest = $this->readAttribute($this->_model,'_rawRequest'); //??

        $request->setAllItems($this->_quote->getAllItems());
        $request->setDestCountryId('US');
        $request->setDestRegionId($this->_quote->getRegionId());
        $request->setDestRegionCode($this->_quote->getRegionCode());
        /**
         * need to call getStreet with -1
         * to get data in string instead of array
         */
    /*    $request->setDestStreet($this->_quote->getStreet(-1));
        $request->setDestCity($this->_quote->getCity());
        $request->setDestPostcode('90210');
        $request->setPackageWeight(1);
        $request->setStoreId(1);

        $this->_model->setRequest($request);

        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->_model, '_getQuotes');

        $resultsArray = EcomDev_Utils_Reflection::getRestrictedPropertyValue($result, '_rates');

        $this->assertEquals('Mage_Shipping_Model_Rate_Result_Method', get_class($resultsArray[0])); // Checks rates are returned.

        Mage::log($result);*/
        Mage::log('commented out tests');
    }

   /* public function CollectRates() //TODO: test whole collectRates method
    {
        $request = Mage::getModel('shipping/rate_request');

        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->_model, 'collectRates', array($request));
    }*/

    /**
     * Test getShipperValues()
     */
  /*  public function testShipperValues()
    {
       $request = Mage::getModel('shipping/rate_request');

        $request->setQuote($this->_quote);

        $request->setAllItems($this->_quote->getAllItems()); // doesn't work
        $request->setDestCountryId('AUS');
        $request->setDestPostcode('2000');
        $request->setStoreId(1);

        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->_helperModel, 'getShipperValues', array($request));

        $result = json_decode($result, true);

        // Check values set in request are present in JSON response
        $this->assertEquals('AUS', $result['destination']['country']);
        $this->assertEquals('2000', $result['destination']['zipcode']);
    }*/
}