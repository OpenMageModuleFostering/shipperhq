<?php

class Shipperhq_ShipperTest_Test_Model_Shipper_Shippertest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var Shipperhq_Shipper_Shipper_Shipper
     */
    protected $_model;

    protected function setUp()
    {
       // $this->_model = new ReflectionMethod('Shipperhq_Shipper', 'shipperRequest' );
       // $this->_model->setAccessible(TRUE);
    }

    protected function tearDown()
    {

    }

    /**
     * Test shipperRequest()
     */
    public function testShipperRequest()
    {
     /*   $jsonObj = '{"credentials":{"user_id":"tom","password":"password","api_key":"d00b67d7eb546a67f717055b84b8ca96"},"siteDetails":{"ecommerce_cart":"Magento Community","ecommerce_version":"1.7.0.2","website_url":"http:\/\/www.localhost.com\/70\/saas\/index.php\/","ip_address":"::1"},"cart":{"price":32,"price_with_discount":12,"weight":1.4,"qty":1,"declared_value":"","additional_attributes":{"package_qty":1,"package_value":32}},"items":[{"client_item_id":"43","name":"Samsung Mobile Phone 1.4lbs","weight":"1.4000","row_weight":0,"qty":1,"price":32,"type":"simple","children":[],"base_price":32,"base_price_incl_tax":32,"price_incl_tax":32,"row_total":32,"base_row_total":32,"attributes":{"package_id":"FREE"},"base_currency":"USD","package_currency":"USD","store_base_currency":"USD","store_current_currency":"USD","tax_percentage":0,"discount_percent":"0.0000","discount_amount":"20.0000","free_shipping":false,"free_method_weight":0,"additional_attributes":{"price":32}}],"destination":{"country":"US","region":"NY","city":null,"street":null,"zipcode":"10016"},"mode":"1","url":{"live":"","development":"http:\/\/localhost:8080\/shipperhq-ws\/v1\/rates"}}
';
        $methodResponse = $this->_model->invoke(new Shipper_Shipper, $jsonObj);
        $methodResponse = json_decode($methodResponse['result'], true);
        $response = $methodResponse['response'];
        $itemsArr = $response['carriers'][0]['rates'][0];
        //$destinationArray = $methodResponse['origin'];

        // Check method returns an array
        $this->assertEquals(true, is_array($methodResponse));

        // Check method has 'main section' array keys
        $this->assertArrayHasKey('responseSummary', $response);
        $this->assertArrayHasKey('carriers', $response);

        // Check input JSON values match outputted array
        $this->assertEquals('2nd day', $itemsArr['name']);
        $this->assertEquals('Will be there by Friday', $itemsArr['description']);
        $this->assertEquals('2_day', $itemsArr['code']);
        $this->assertEquals('25.67', $itemsArr['price']);

        //$this->assertEquals('US', $destinationArray['country']);
        //$this->assertEquals('', $destinationArray['region']);
        //$this->assertEquals('90034', $destinationArray['zipcode']);
     */
        Mage::log('commented out shipperTest test');
    }
}