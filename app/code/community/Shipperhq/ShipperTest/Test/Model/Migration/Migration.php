<?php
/**
 * Created by JetBrains PhpStorm.
 * User: karen baker
 * Date: 25/01/2013
 * Time: 08:44
 */

/**
 * @loadFixture config
 */
class Shipperhq_ShipperTest_Test_Model_Migration_Migration extends EcomDev_PHPUnit_Test_Case {

    protected $_product = null;

    protected static $_migrateHelper;

    protected function setUp() {

        // mock this model, and this method within the model
        $this->_product = $this->getModelMock('catalog/product');

     /*   // when method getQuote is called then return $this->_quote
        $this->_quoteItem->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($this->_quote));

        $this->replaceByMock('singleton','sales/quote_item',$this->_quoteItem);

        $this->app()->getRequest()->setBaseUrl('http://www.localhost.com');
        $_SESSION = array();

        // reset whole weight so it picks it up
        $shipUsaHelper = Mage::helper('shipusa');
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($shipUsaHelper,'_wholeWeightRounding',null); */
    }

    public static function setUpBeforeClass() {
        self::$_migrateHelper = Mage::helper('shipperhq_migration');
    }


    public function testUpdateOneProductAttribute() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testing Update One Product attribute');

        //TODo this should load an actual product setup as part of setup script
        $product1 = $this->_product->load(1);

        $result = self::$_migrateHelper->updateProductAttribute($product1, 'attribute_code', 'attribute_value');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],2*5);
    }

    /**
     * Test Single Boxes
     */
    public function testSingle4OneBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle4OneBox - Test 4 items in single box');

        $packages = $this->getPackages('4*SINGLE_MAXQTY_4_NODIMS_5LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],4*5);
    }

    public function testSingle5OneBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle5OneBox - Test 5 items in 2 boxes');

        $packages = $this->getPackages('5*SINGLE_MAXQTY_4_NODIMS_5LB');

        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],4*5);
        $this->ensurePackageWeight($packages[1],1*5);
    }

    public function testSingle7OneBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle7OneBox - Test 7 items in 2 boxes');

        $packages = $this->getPackages('7*SINGLE_MAXQTY_4_NODIMS_5LB');

        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],4*5);
        $this->ensurePackageWeight($packages[1],3*5);
    }

    public function testSingle11OneBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle11OneBox - Test 11 items in 3 boxes');

        $packages = $this->getPackages('11*SINGLE_MAXQTY_4_NODIMS_5LB');

        $this->ensurePackageSize($packages,3);
        $this->ensurePackageWeight($packages[0],4*5);
        $this->ensurePackagePrice($packages[0],4*12);
        $this->ensurePackageWeight($packages[1],4*5);
        $this->ensurePackagePrice($packages[1],4*12);
        $this->ensurePackageWeight($packages[2],3*5);
        $this->ensurePackagePrice($packages[2],3*12);
    }

    /**
     * Hasnt got a box that matches, send with standard box (this is a bad config)
     */
    public function testSingle15OneBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle15OneBox - Test 15 items in 1 boxes - exceeds rules of 12 max');

        $packages = $this->getPackages('15*SINGLE_MAXQTY_4_NODIMS_5LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],15*5);
        $this->ensurePackagePrice($packages[0],15*12);
    }

    /**
     * Single with 2 possible boxes
     */
    public function testSingle1TwoBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle1TwoBox - Test 1 item in single box with 2 possible boxes');

        $packages = $this->getPackages('1*SINGLE_2_MAXQTY_NODIMS_7LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*7);
        $this->ensurePackagePrice($packages[0],1*16);
    }

    /**
     * 	 *
     */
    public function testSingle5TwoBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle5TwoBox - Test 5 item in single box with 2 possible boxes');

        $packages = $this->getPackages('5*SINGLE_2_MAXQTY_NODIMS_7LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],5*7);
        $this->ensurePackagePrice($packages[0],5*16);
    }


    /**
     * Test to ensure moves onto the next box
     */
    public function testSingle15TwoBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle15TwoBox - Test 5 item in single box with 2 possible boxes');

        $packages = $this->getPackages('15*SINGLE_2_MAXQTY_NODIMS_7LB');

        $this->ensurePackageSize($packages,3);
        $this->ensurePackageWeight($packages[0],6*7);
        $this->ensurePackagePrice($packages[0],6*16);
        $this->ensurePackageWeight($packages[1],6*7);
        $this->ensurePackagePrice($packages[1],6*16);
        $this->ensurePackageWeight($packages[2],3*7);
        $this->ensurePackagePrice($packages[2],3*16);
    }

    /*
     * Hasnt got a box that matches
     */
    public function testSingle30TwoBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle30TwoBox - Test 30 items in 2 poss boxes - exceeds rules of 25 max');

        $packages = $this->getPackages('30*SINGLE_2_MAXQTY_NODIMS_7LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],30*7);
        $this->ensurePackagePrice($packages[0],30*16);
    }

    /*
     * Hit Dim Box  - Test uses 2 different box sizes
     */
    public function testSingle27DimBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle27DimBox - 27 items, a dimensional box');

        $packages = $this->getPackages('27*SINGLE_2_MAXQTY_NODIMS_7LB');

        $this->ensurePackageTotalPrice($packages,27*16);
        $this->ensurePackageTotalWeight($packages,27*7);


        $this->ensurePackageSize($packages,3);
        $this->ensurePackageWeight($packages[0],9*7);
        $this->ensurePackagePrice($packages[0],9*16);
        $this->ensurePackageDimensions($packages[0],array(5,5,3));

        $this->ensurePackageWeight($packages[1],9*7);
        $this->ensurePackagePrice($packages[1],9*16);
        $this->ensurePackageDimensions($packages[1],array(5,5,3));

        $this->ensurePackageWeight($packages[2],9*7);
        $this->ensurePackagePrice($packages[2],9*16);
        $this->ensurePackageDimensions($packages[2],array(5,5,3));

    }


    /*
     * 2 box sizes. The better box changes on volume
     */
    public function testSingle1TwoDimBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle1TwoDimBox - Test 1 dim items in 2 poss boxes ');

        $packages = $this->getPackages('1*SING_2_VOL_5LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*5);
        $this->ensurePackagePrice($packages[0],1*17);
    }

    /*
     * Hasnt got a box that matches
     */
    public function testSingle6TwoDimBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle6TwoDimBox - Test 6 dim items in 2 poss boxes ');

        $packages = $this->getPackages('6*SING_2_VOL_5LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],6*5);
        $this->ensurePackagePrice($packages[0],6*17);
    }


    /*
     * Test to ensure -1 in max qty works
     */
    public function testSingleNoMaxQty()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingleNoMaxQty - Max qty is -1 ');

        $packages = $this->getPackages('1*SING_8LB_NOMAX');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*8);
        $this->ensurePackagePrice($packages[0],1*13);
        $this->ensurePackageDimensions($packages[0],array(0,0,0));
    }

    public function testSingle12NoMaxQtyNoMaxBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle12NoMaxQty - Max qty is -1 , max box is -1');

        $packages = $this->getPackages('12*SING_8LB_NOMAX');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],12*8);
        $this->ensurePackagePrice($packages[0],12*13);
    }

    public function testSingle3NoMaxQtyMaxBox10()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle3NoMaxQtyMaxBox10 - 3 items - Max qty is -1, max box is 10 ');

        $packages = $this->getPackages('3*SING_8LB_MAX10');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],3*8);
        $this->ensurePackagePrice($packages[0],3*13);
    }

    public function testSingle12NoMaxQtyMaxBox10()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle12NoMaxQtyMaxBox10 - 12 items, 2 boxes - Max qty is -1, max box is 10 ');

        $packages = $this->getPackages('12*SING_8LB_MAX10');

        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],10*8);
        $this->ensurePackagePrice($packages[0],10*13);
        $this->ensurePackageWeight($packages[1],2*8);
        $this->ensurePackagePrice($packages[1],2*13);
    }

    /*
     * Test to ensure -1 in max qty works when max qty in ship box overrides, 1 item
     */
    public function testSingle1NoMaxQtyBoxMaxQty()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle1NoMaxQtyBoxMaxQty - 1 item, Max qty is -1, box max qty is 4 ');

        $packages = $this->getPackages('1*SING_BOXA_8LB_NOMAX');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*8);
        $this->ensurePackagePrice($packages[0],1*13);
        $this->ensurePackageDimensions($packages[0],array(24,14,20));
    }

    /*
     * Test to ensure -1 in max qty works when max qty in ship box overrides, 1 item
     */
    public function testSingle5NoMaxQtyBoxMaxQty()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle5NoMaxQtyBoxMaxQty - 5 items, Max qty is -1, box max qty is 4 ');

        $packages = $this->getPackages('5*SING_BOXA_8LB_NOMAX');

        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],4*8);
        $this->ensurePackagePrice($packages[0],4*13);
        $this->ensurePackageDimensions($packages[0],array(24,14,20));
        $this->ensurePackageWeight($packages[1],1*8);
        $this->ensurePackagePrice($packages[1],1*13);
        $this->ensurePackageDimensions($packages[1],array(24,14,20));
    }

    /*
     * Test to ensure picks up max weight in ship box - shouldnt create new box
     */
    public function testSingle1MaxWeight25()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle1MaxWeight25 - 1 item, Max qty is -1, box max qty is -1, box max weight is 25 ');

        $packages = $this->getPackages('1*SING_BOXF_8LB_MAXWEIGHT25LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*8);
        $this->ensurePackagePrice($packages[0],1*13);
        $this->ensurePackageDimensions($packages[0],array(24,14,20));
    }


    /*
     * Test to ensure picks up max weight in ship box - shouldnt create new box
     */
    public function testSingle3MaxWeight25()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle3MaxWeight25 - 3 item, Max qty is -1, box max qty is -1, box max weight is 25 ');

        $packages = $this->getPackages('3*SING_BOXF_8LB_MAXWEIGHT25LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],3*8);
        $this->ensurePackagePrice($packages[0],3*13);
        $this->ensurePackageDimensions($packages[0],array(24,14,20));
    }

    /*
     * Test to ensure picks up max weight in ship box - shouldnt create new box
     */
    public function testSingle4MaxWeight25()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle4MaxWeight25 - 1 item, Max qty is -1, box max qty is -1, box max weight is 25 ');

        $packages = $this->getPackages('4*SING_BOXF_8LB_MAXWEIGHT25LB');

        $this->ensurePackageSize($packages,2);
        $this->ensurePackageTotalWeight($packages,4*8);
        $this->ensurePackageTotalPrice($packages,4*13);
        $this->ensurePackageWeight($packages[0],3*8);
        $this->ensurePackagePrice($packages[0],3*13);
        $this->ensurePackageDimensions($packages[0],array(24,14,20));
        $this->ensurePackageWeight($packages[1],1*8);
        $this->ensurePackagePrice($packages[1],1*13);
        $this->ensurePackageDimensions($packages[1],array(24,14,20));

    }


    /**
     * Doesnt Hits max weight on ship box, max qty on single box, price 13
     */
    public function testSingle2MaxWeightMaxQtyExceeded()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle2MaxWeightMaxQtyExceeded - Test 2 items max weight 12, max qty 4');

        $packages = $this->getPackages('2*BOXG_SG_MAXQTY_4_5LB_MAXWGHT_12');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],2*5);
        $this->ensurePackagePrice($packages[0],2*13);
        $this->ensurePackageDimensions($packages[0],array(0,0,0));
    }


    /**
     * Hits max weight on ship box, max qty on single box, price 13
     */
    public function testSingle5MaxWeightMaxQtyExceeded()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle5MaxWeightMaxQtyExceeded - Test 5 items max weight 12, max qty 4');

        $packages = $this->getPackages('5*BOXG_SG_MAXQTY_4_5LB_MAXWGHT_12');

        $this->ensurePackageSize($packages,3);
        $this->ensurePackageWeight($packages[1],2*5);
        $this->ensurePackagePrice($packages[1],2*13);
        $this->ensurePackageDimensions($packages[1],array(0,0,0));
        $this->ensurePackageWeight($packages[1],2*5);
        $this->ensurePackagePrice($packages[1],2*13);
        $this->ensurePackageDimensions($packages[1],array(0,0,0));
        $this->ensurePackageWeight($packages[2],1*5);
        $this->ensurePackagePrice($packages[2],1*13);
        $this->ensurePackageDimensions($packages[2],array(0,0,0));
        $this->ensurePackageTotalPrice($packages,5*13);
        $this->ensurePackageTotalWeight($packages,5*5);
    }

    /**
     * Doesnt Hit max weight on ship box, max qty on single box, price 13
     * Hits the 2nd box so doesnt care
     */
    public function testSingle25MaxWeightMaxQtyNotExceeded()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle25MaxWeightMaxQtyExceeded - Test 25 items max weight 12, max qty 4');

        $packages = $this->getPackages('25*BOXG_SG_MAXQTY_4_5LB_MAXWGHT_12');

        $this->ensurePackageSize($packages,7);
        $this->ensurePackageWeight($packages[1],4*5);
        $this->ensurePackagePrice($packages[1],4*13);
        $this->ensurePackageDimensions($packages[1],array(0,0,0));
        $this->ensurePackageWeight($packages[5],4*5);
        $this->ensurePackagePrice($packages[5],4*13);
        $this->ensurePackageWeight($packages[6],1*5);
        $this->ensurePackagePrice($packages[6],1*13);
        $this->ensurePackageTotalPrice($packages,25*13);
        $this->ensurePackageTotalWeight($packages,25*5);

    }

    /***********************
     * Multiple item tests
     */

    /**
     * 2 Items in same box
     */
    public function testMultiple1Qty2Items1Box()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testMultiple1Qty2Items1Box - 1 of each 2 Items in same box');

        $packages = $this->getPackages('1*BOXA_5LB,1*BOXA_6LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],5+6);
        $this->ensurePackagePrice($packages[0],4+25);
    }

    /**
     * 2 Items in same box, 2 of each
     */
    public function testMultiple2Qty2Items1Box()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testMultiple2Qty2Items1Box - 2 of each 2 Items in same box, max 4 qty');

        $packages = $this->getPackages('2*BOXA_5LB,2*BOXA_6LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],2*5+2*6);
        $this->ensurePackagePrice($packages[0],2*4+2*25);
    }

    /**
     * 2 Items in same box, 3 of each
     */
    public function testMultiple3Qty2Items1Box()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testMultiple3Qty2Items1Box - 3 of each 2 Items in same box, max 4 qty');

        $packages = $this->getPackages('3*BOXA_5LB,3*BOXA_6LB');

        $this->ensurePackageTotalPrice($packages,3*4+3*25);
        $this->ensurePackageTotalWeight($packages,3*5+3*6);
        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],3*5+1*6);
        $this->ensurePackagePrice($packages[0],3*4+1*25);
        $this->ensurePackageWeight($packages[1],2*6);
        $this->ensurePackagePrice($packages[1],2*25);
    }

    /**
     * 2 Items in same box, max quantity of item X is 2, max quantity of item Y is 5
     * Box C = 25*20*18 no max qty or max weight
     */
    public function testMulti1Qty2DiffMaxQtyBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testMulti1Qty2DiffMaxQtyBox - 2 Items in same box, max quantity of item X is 2, max quantity of item Y is 5');

        $packages = $this->getPackages('1*BOXC_5LB_MAXQTY_2,1*BOXC_6LB_MAXQTY_5');

        $this->ensurePackageTotalPrice($packages,1*4+1*25);
        $this->ensurePackageTotalWeight($packages,1*5+1*6);
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*5+1*6);
        $this->ensurePackagePrice($packages[0],1*4+1*25);
    }

    public function testMulti5Qty2DiffMaxQtyBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testMulti5Qty2DiffMaxQtyBox - 5 qty, 2 Items in same box, max quantity of item X is 2, max quantity of item Y is 5');

        $packages = $this->getPackages('2*BOXC_5LB_MAXQTY_2,3*BOXC_6LB_MAXQTY_5');

        $this->ensurePackageTotalPrice($packages,2*4+3*25);
        $this->ensurePackageTotalWeight($packages,2*5+3*6);
        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],2*5);
        $this->ensurePackagePrice($packages[0],2*4);
        $this->ensurePackageWeight($packages[1],3*6);
        $this->ensurePackagePrice($packages[1],3*25);
    }

    public function testMulti7Qty2DiffMaxQtyBox()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug
            ('testMulti7Qty2DiffMaxQtyBox - 7 qty, 3+4 Items in same box, max quantity of item X is 2, max quantity of item Y is 5');

        $packages = $this->getPackages('3*BOXC_5LB_MAXQTY_2,4*BOXC_6LB_MAXQTY_5');

        $this->ensurePackageTotalPrice($packages,3*4+4*25);
        $this->ensurePackageTotalWeight($packages,3*5+4*6);
        $this->ensurePackageSize($packages,3);
        $this->ensurePackageWeight($packages[0],2*5);
        $this->ensurePackagePrice($packages[0],2*4);
        $this->ensurePackageWeight($packages[1],2*6+1*5);
        $this->ensurePackagePrice($packages[1],2*25+1*4);
        $this->ensurePackageWeight($packages[2],2*6);
        $this->ensurePackagePrice($packages[2],2*25);
    }

    /**
     * Same as above but with no volume
     */
    public function testMulti7Qty2DiffMaxQtyBoxNoVol()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testMulti7Qty2DiffMaxQtyBoxNoVol - 7 qty, 3+4 Items in same box, max quantity of item X is 2, max quantity of item Y is 5');

        $packages = $this->getPackages('4*DEFAULTBOX_5LB_MAXQTY_2,3*DEFAULTBOX_6LB_MAXQTY_5');

        $this->ensurePackageTotalPrice($packages,4*4+3*25);
        $this->ensurePackageTotalWeight($packages,4*5+3*6);
        $this->ensurePackageSize($packages,3);
        $this->ensurePackageWeight($packages[1],2*5);
        $this->ensurePackagePrice($packages[1],2*4);
        $this->ensurePackageWeight($packages[0],2*5);
        $this->ensurePackagePrice($packages[0],2*4);
        $this->ensurePackageWeight($packages[2],3*6);
        $this->ensurePackagePrice($packages[2],3*25);
    }

    /**
     * Same as above but with no volume
     */
    public function testMulti7Qty2DiffMaxQtyBoxNoVolDiffOrder()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testMulti7Qty2DiffMaxQtyBoxNoVolDiffOrder - Switch order of last test');

        $packages = $this->getPackages('3*DEFAULTBOX_6LB_MAXQTY_5,4*DEFAULTBOX_5LB_MAXQTY_2');

        $this->ensurePackageTotalPrice($packages,4*4+3*25);
        $this->ensurePackageTotalWeight($packages,4*5+3*6);
        $this->ensurePackageSize($packages,3);
        $this->ensurePackageWeight($packages[0],3*6);
        $this->ensurePackagePrice($packages[0],3*25);
        $this->ensurePackageWeight($packages[2],2*5);
        $this->ensurePackagePrice($packages[2],2*4);
        $this->ensurePackageWeight($packages[1],2*5);
        $this->ensurePackagePrice($packages[1],2*4);
    }

    /**
     * Multi items, same box, merged together
     */
    public function testMultiCombBox1()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testMultiCombBox1 - Multi items, same box, merged together');

        $packages = $this->getPackages('2*DEFAULTBOX_6LB_MAXQTY_5,4*DEFAULTBOX_5LB_MAXQTY_2');

        $this->ensurePackageTotalPrice($packages,4*4+2*25);
        $this->ensurePackageTotalWeight($packages,4*5+2*6);
        $this->ensurePackageSize($packages,3);
        $this->ensurePackageWeight($packages[0],2*6+1*5);
        $this->ensurePackagePrice($packages[0],2*25+1*4);
        $this->ensurePackageWeight($packages[1],2*5);
        $this->ensurePackagePrice($packages[1],2*4);
        $this->ensurePackageWeight($packages[2],1*5);
        $this->ensurePackagePrice($packages[2],1*4);
    }

    /**
     * Multi items, same box, merged together
     */
    public function testMultiCombBox1Opp()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testMultiCombBox1 - Multi items, same box, merged together');

        $packages = $this->getPackages('4*DEFAULTBOX_5LB_MAXQTY_2,2*DEFAULTBOX_6LB_MAXQTY_5');

        $this->ensurePackageTotalPrice($packages,4*4+2*25);
        $this->ensurePackageTotalWeight($packages,4*5+2*6);
        $this->ensurePackageSize($packages,3);
        $this->ensurePackageWeight($packages[0],2*5);
        $this->ensurePackagePrice($packages[0],2*4);
        $this->ensurePackageWeight($packages[1],2*5);
        $this->ensurePackagePrice($packages[1],2*4);
        $this->ensurePackageWeight($packages[2],2*6);
        $this->ensurePackagePrice($packages[2],2*25);
    }

    /**
     *
     * Has a box id and another box declared locally
     */
    public function testMulti1Box1LocalBox() {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testMultiCombBox1 - Multi items, same box, merged together');

        $packages = $this->getPackages('1*SING_2_VOL_5LB,1*DEFAULTBOX_6LB_MAXQTY_5');

        $this->ensurePackageTotalPrice($packages,1*17+1*25);
        $this->ensurePackageTotalWeight($packages,1*5+1*6);
        $this->ensurePackageSize($packages,2);

        $this->ensurePackageWeight($packages[1],1*6);
        $this->ensurePackagePrice($packages[1],1*25);
        $this->ensurePackageWeight($packages[0],1*5);
        $this->ensurePackagePrice($packages[0],1*17);
    }

    /**
     * Multi items, same box, merged together
     */
    public function testMultiCombBox11Local()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testMultiCombBox1 - Multi items, 2 in same box type, 1 on its own');

        $packages = $this->getPackages('4*DEFAULTBOX_5LB_MAXQTY_2,1*SING_2_VOL_5LB,2*DEFAULTBOX_6LB_MAXQTY_5');

        $this->ensurePackageTotalPrice($packages,4*4+2*25+1*17);
        $this->ensurePackageTotalWeight($packages,4*5+2*6+1*5);
        $this->ensurePackageSize($packages,4);

        $this->ensurePackageWeight($packages[0],2*5);
        $this->ensurePackagePrice($packages[0],2*4);

        $this->ensurePackageWeight($packages[1],2*5);
        $this->ensurePackagePrice($packages[1],2*4);

        $this->ensurePackageWeight($packages[2],2*6);
        $this->ensurePackagePrice($packages[2],2*25);

        $this->ensurePackageWeight($packages[3],1*5);
        $this->ensurePackagePrice($packages[3],1*17);
    }

    /**
     * Multi items, same box, merged together
     */
    public function testMultiCombBox1Local10()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testMultiCombBox1Local10 - Multi items, 2 in same box type, 1 on its own with 10qty');

        $packages = $this->getPackages('4*DEFAULTBOX_5LB_MAXQTY_2,10*SING_2_VOL_5LB,2*DEFAULTBOX_6LB_MAXQTY_5');

        $this->ensurePackageTotalPrice($packages,4*4+2*25+10*17);
        $this->ensurePackageTotalWeight($packages,4*5+2*6+10*5);
        $this->ensurePackageSize($packages,4);
        $this->ensurePackageWeight($packages[0],2*5);
        $this->ensurePackagePrice($packages[0],2*4);
        $this->ensurePackageDimensions($packages[0],array(10,8,3));

        $this->ensurePackageWeight($packages[2],2*6);
        $this->ensurePackagePrice($packages[2],2*25);
        $this->ensurePackageDimensions($packages[2],array(10,8,3));
        $this->ensurePackageWeight($packages[1],2*5);
        $this->ensurePackagePrice($packages[1],2*4);
        $this->ensurePackageDimensions($packages[1],array(10,8,3));
        $this->ensurePackageWeight($packages[3],10*5);
        $this->ensurePackagePrice($packages[3],10*17);
        $this->ensurePackageDimensions($packages[3],array(10,8,5));
    }


    /**
     * Multi items, multi boxes
     */
    public function testMultiMulti1()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testMultiMulti1 - Multi items, 2 items with different boxes');

        $packages = $this->getPackages('1*DEFAULTBOX_5LB_MAXQTY_2,1*BOXC_6LB_MAXQTY_5');

        $this->ensurePackageSize($packages,2);
        $this->ensurePackageTotalPrice($packages,1*4+1*25);
        $this->ensurePackageTotalWeight($packages,1*5+1*6);
        $this->ensurePackageWeight($packages[1],1*6);
        $this->ensurePackagePrice($packages[1],1*25);
        $this->ensurePackageDimensions($packages[1],array(25,20,18));
        // smallest box goes first
        $this->ensurePackageWeight($packages[0],1*5);
        $this->ensurePackagePrice($packages[0],1*4);
        $this->ensurePackageDimensions($packages[0],array(10,8,3));
    }

    /**
     * Multi items, multi boxes, more qty
     */
    public function testMultiMulti2()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testMultiMulti2 - Multi items, 2 items with different boxes, larger qty');

        $packages = $this->getPackages('9*DEFAULTBOX_5LB_MAXQTY_2,6*BOXC_6LB_MAXQTY_5');

        $this->ensurePackageTotalPrice($packages,9*4+6*25);
        $this->ensurePackageTotalWeight($packages,9*5+6*6);
        $this->ensurePackageSize($packages,7);


        $this->ensurePackageWeight($packages[0],5*6);
        $this->ensurePackagePrice($packages[0],5*25);
        $this->ensurePackageDimensions($packages[0],array(25,20,18));

        $this->ensurePackageWeight($packages[1],1*6);
        $this->ensurePackagePrice($packages[1],1*25);
        $this->ensurePackageDimensions($packages[1],array(25,20,18));

        $this->ensurePackageWeight($packages[2],2*5);
        $this->ensurePackagePrice($packages[2],2*4);
        $this->ensurePackageDimensions($packages[2],array(10,8,3));


        $this->ensurePackageWeight($packages[6],1*5);
        $this->ensurePackagePrice($packages[6],1*4);
        $this->ensurePackageDimensions($packages[6],array(10,8,3));


    }


    public function testSingle3Boxes1() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle3Boxes1 - 1 item 3 box choices, 1 qty');

        $packages = $this->getPackages('1*3BOXES_3LB_MAXQTY_7');

        $this->ensurePackageTotalPrice($packages,1*7);
        $this->ensurePackageTotalWeight($packages,1*3);
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*3);
        $this->ensurePackagePrice($packages[0],1*7);
        $this->ensurePackageDimensions($packages[0],array(4,5,3));
    }


    public function testSingle3Boxes7() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle3Boxes7 - 1 item 3 box choices, 7 qty');

        $packages = $this->getPackages('7*3BOXES_3LB_MAXQTY_7');

        $this->ensurePackageTotalPrice($packages,7*7);
        $this->ensurePackageTotalWeight($packages,7*3);
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],7*3);
        $this->ensurePackagePrice($packages[0],7*7);
        $this->ensurePackageDimensions($packages[0],array(4,5,3));
    }


    /**
     * TODO This test will fail
     *
     * Not currently working, is picking different box. Need to assess both and work out which is right
     * Only happened after recent refactor around box volume being calculated on the fly.
     */
//    public function testSingle3Boxes8() {
//
//        Mage::helper('webshopapps_wsatestframework/log')->debug('testSingle3Boxes8 - 1 item 3 box choices, 8 qty');
//
//        $packages = $this->getPackages('8*3BOXES_3LB_MAXQTY_7');
//
//        $this->ensurePackageTotalPrice($packages,8*7);
//        $this->ensurePackageTotalWeight($packages,8*3);
//        $this->ensurePackageSize($packages,1);
//        $this->ensurePackageWeight($packages[0],8*3);
//        $this->ensurePackagePrice($packages[0],8*7);
//        $this->ensurePackageDimensions($packages[0],array(10,4,6));
//    }

    public function testMultiMultiBox3() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testMultiMultiBox3 - 2 items, 3 box choices, 2 matching, 1 of each');

        $packages = $this->getPackages('1*3BOXES_3LB_MAXQTY_7,1*3BOXES_15LB_NOMAX');

        $this->ensurePackageTotalPrice($packages,1*7+1*4);
        $this->ensurePackageTotalWeight($packages,1*3+1*15);
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*3+1*15);
        $this->ensurePackagePrice($packages[0],1*7+1*4);
        $this->ensurePackageDimensions($packages[0],array(6,4,10));
    }

    public function testSep1() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testSep1 - 1 item separate ship qty 1');

        $packages = $this->getPackages('1*SEP_5LB_NO_DIM');

        $this->ensurePackageTotalPrice($packages,1*10);
        $this->ensurePackageTotalWeight($packages,1*5);
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*5);
        $this->ensurePackagePrice($packages[0],1*10);
        $this->ensurePackageDimensions($packages[0],array(0,0,0));
    }

    public function testSep2() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testSep2 - 2 item separate ship ,1 with single');

        $packages = $this->getPackages('1*SEP_5LB_NO_DIM,1*SEP_12LB_DIM_SINGLE');

        $this->ensurePackageTotalPrice($packages,1*10+1*16);
        $this->ensurePackageTotalWeight($packages,1*5+1*12);
        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],1*12);
        $this->ensurePackagePrice($packages[0],1*16);
        $this->ensurePackageDimensions($packages[0],array(18,20,25));
        $this->ensurePackageWeight($packages[1],1*5);
        $this->ensurePackagePrice($packages[1],1*10);
        $this->ensurePackageDimensions($packages[1],array(0,0,0));
    }

    public function testSep3() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testSep3 - 2 item separate ship ,1 with single, qty 5each');

        $packages = $this->getPackages('5*SEP_5LB_NO_DIM,5*SEP_12LB_DIM_SINGLE');

        $this->ensurePackageTotalPrice($packages,5*10+5*16);
        $this->ensurePackageTotalWeight($packages,5*5+5*12);
        $this->ensurePackageSize($packages,10);
        $this->ensurePackageWeight($packages[0],1*12);
        $this->ensurePackagePrice($packages[0],1*16);
        $this->ensurePackageDimensions($packages[0],array(18,20,25));
        $this->ensurePackageWeight($packages[6],1*5);
        $this->ensurePackagePrice($packages[6],1*10);
        $this->ensurePackageDimensions($packages[6],array(0,0,0));
    }

    public function testSep4() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testSep4 -  max qty on this item is 10, but is ship separate so can do');

        $packages = $this->getPackages('15*SEP_12LB_DIM_SINGLE');

        $this->ensurePackageTotalPrice($packages,15*16);
        $this->ensurePackageTotalWeight($packages,15*12);
        $this->ensurePackageSize($packages,15);
        $this->ensurePackageWeight($packages[0],1*12);
        $this->ensurePackagePrice($packages[0],1*16);
        $this->ensurePackageDimensions($packages[0],array(18,20,25));
    }

    public function testError1() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testError1 -  cant find a box, max qty on this item is 10' );

        $packages = $this->getPackages('15*MAX_BOX_10_12LB');

        $this->ensurePackageTotalPrice($packages,15*18);
        $this->ensurePackageTotalWeight($packages,15*12);
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],15*12);
        $this->ensurePackagePrice($packages[0],15*18);
        $this->ensurePackageDimensions($packages[0],array(0,0,0));
    }

    public function testError2() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testError1 -  items that can be boxes with items that cant' );

        $packages = $this->getPackages('1*3BOXES_3LB_MAXQTY_7,1*3BOXES_15LB_NOMAX,15*MAX_BOX_10_12LB');

        $this->ensurePackageTotalPrice($packages,1*7+1*4+15*18);
        $this->ensurePackageTotalWeight($packages,1*3+1*15+15*12);
        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],15*12);
        $this->ensurePackagePrice($packages[0],15*18);
        $this->ensurePackageDimensions($packages[0],array(0,0,0));
        $this->ensurePackageWeight($packages[1],1*3+1*15);
        $this->ensurePackagePrice($packages[1],1*7+1*4);
        $this->ensurePackageDimensions($packages[1],array(6,4,10));
    }

    /**
     * Should ship in one box
     */
    public function testMaxWeight80lb() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testMaxWeight80lb -  test selects right box weight 80lb' );

        $packages = $this->getPackages('1*SML_80LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*80);
        $this->ensurePackagePrice($packages[0],1*14);
        $this->ensurePackageDimensions($packages[0],array(20,25,29));
        $this->ensurePackageTotalPrice($packages,14);
        $this->ensurePackageTotalWeight($packages,80);
    }

    public function testChangeQty_4Items() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testChangeQty_4Items -  Change based on quantity 4 items, 1st box max is 6' );

        $packages = $this->getPackages('4*WIN_6QTY_2LB');

        $this->ensurePackageTotalWeight($packages,4*2);
        $this->ensurePackageTotalPrice($packages,4*3);
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],4*2);
        $this->ensurePackagePrice($packages[0],4*3);
        $this->ensurePackageDimensions($packages[0],array(10,8,7));
    }

    public function testChangeQty_7Items() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testChangeQty_7Items -  Change based on quantity 7 items, 1st box max is 6' );

        $packages = $this->getPackages('7*WIN_6QTY_2LB');

        $this->ensurePackageTotalPrice($packages,7*3);
        $this->ensurePackageTotalWeight($packages,7*2);
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],7*2);
        $this->ensurePackagePrice($packages[0],7*3);
        $this->ensurePackageDimensions($packages[0],array(12,14,12));
    }

    /**
     * Uses 2 different box sizes - see DIMSHIP-43
     */
    public function testChangeQty_15Items() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testChangeQty_15Items -  Change based on quantity 15 items, 2nd box max is 12' );

        $packages = $this->getPackages('15*WIN_6QTY_2LB');

        $this->ensurePackageTotalPrice($packages,15*3);
        $this->ensurePackageTotalWeight($packages,15*2);
        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],12*2);
        $this->ensurePackagePrice($packages[0],12*3);
        $this->ensurePackageDimensions($packages[0],array(12,14,12));
        $this->ensurePackageWeight($packages[1],3*2);
        $this->ensurePackagePrice($packages[1],3*3);
        $this->ensurePackageDimensions($packages[1],array(7,8,10));
    }


    public function testChangeQty_15EachItems() {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testChangeQty_15EachItems -  Change based on quantity 15 of 2 lots items, 2nd box max is 12' );

        $packages = $this->getPackages('7*WIN_6QTY_2LB,8*WIN_6QTY_2LB_1');

        $this->ensurePackageTotalPrice($packages,15*3);
        $this->ensurePackageTotalWeight($packages,15*2);
        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],12*2);
        $this->ensurePackagePrice($packages[0],12*3);
        $this->ensurePackageDimensions($packages[0],array(12,14,12));
        $this->ensurePackageWeight($packages[1],3*2);
        $this->ensurePackagePrice($packages[1],3*3);
        $this->ensurePackageDimensions($packages[1],array(7,8,10));
    }

    public function testChangeQty_15EachItems_1() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testChangeQty_15EachItems_1 -  Change based on quantity 15  of 2 lotsitems, 2nd box max is 12' );

        $packages = $this->getPackages('1*WIN_6QTY_2LB,14*WIN_6QTY_2LB_1');

        $this->ensurePackageTotalPrice($packages,15*3);
        $this->ensurePackageTotalWeight($packages,15*2);
        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],12*2);
        $this->ensurePackagePrice($packages[0],12*3);
        $this->ensurePackageDimensions($packages[0],array(12,14,12));
        $this->ensurePackageWeight($packages[1],3*2);
        $this->ensurePackagePrice($packages[1],3*3);
        $this->ensurePackageDimensions($packages[1],array(7,8,10));
    }



    /**
     * Should ship in one box
     */
    public function testMaxWeight20lb() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testMaxWeight20lb -  test selects right box weight 20lb' );

        $packages = $this->getPackages('1*SML_20LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*20);
        $this->ensurePackagePrice($packages[0],1*24);
        $this->ensurePackageDimensions($packages[0],array(0,0,0));
        $this->ensurePackageTotalPrice($packages,24);
        $this->ensurePackageTotalWeight($packages,20);
    }

    /**
     * Should ship in one box
     */
    public function test3MaxWeight20lb() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test3MaxWeight20lb -  test selects right box weight 20lb * 3' );

        $packages = $this->getPackages('3*SML_20LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],3*20);
        $this->ensurePackagePrice($packages[0],3*24);
        $this->ensurePackageDimensions($packages[0],array(20,25,28));
        $this->ensurePackageTotalPrice($packages,3*24);
        $this->ensurePackageTotalWeight($packages,3*20);
    }

    /**
     * Should ship in one box
     */
    public function test3MaxWeightML20lb() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test3MaxWeightML20lb -  test selects right box weight 20lb * 3, only ML' );

        $packages = $this->getPackages('3*ML_20LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],3*20);
        $this->ensurePackagePrice($packages[0],3*24);
        $this->ensurePackageDimensions($packages[0],array(20,25,28));
        $this->ensurePackageTotalPrice($packages,3*24);
        $this->ensurePackageTotalWeight($packages,3*20);
    }

    /**
     * Should ship in one box
     */
    public function test4MaxWeight20lb() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test4MaxWeight20lb -  test selects right box weight 20lb * 4' );

        $packages = $this->getPackages('4*SML_20LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],4*20);
        $this->ensurePackagePrice($packages[0],4*24);
        $this->ensurePackageDimensions($packages[0],array(20,25,29));
        $this->ensurePackageTotalPrice($packages,4*24);
        $this->ensurePackageTotalWeight($packages,4*20);
    }

    /**
     * Should ship in one box
     */
    public function test2DiffMaxWeight50lb() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test2DiffMaxWeight50lb -  test selects right box weight 20lb * 1 & 30lb *1' );

        $packages = $this->getPackages('1*SML_20LB,1*SML_30LB');

        $this->ensurePackageTotalPrice($packages,1*24+1*13);
        $this->ensurePackageTotalWeight($packages,1*20+1*30);
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*20+1*30);
        $this->ensurePackagePrice($packages[0],1*24+1*13);
        $this->ensurePackageDimensions($packages[0],array(20,25,28));

    }

    /**
     * Should ship in one box
     */
    public function test2DiffMaxWeight70lb() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test2DiffMaxWeight70lb -  test selects right box weight 20lb * 2 & 30lb *1' );

        $packages = $this->getPackages('2*SML_20LB,1*SML_30LB');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],2*20+1*30);
        $this->ensurePackagePrice($packages[0],2*24+1*13);
        $this->ensurePackageDimensions($packages[0],array(20,25,28));
        $this->ensurePackageTotalPrice($packages,2*24+1*13);
        $this->ensurePackageTotalWeight($packages,2*20+1*30);
    }

    /**
     * How to ship 190lb when choice of 3 boxes, 30, 70, 120lb
     */
    public function test2DiffMaxWeight190lb() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test2DiffMaxWeight190lb -  test selects right box weight 20lb * 2 & 30lb *5' );

        $packages = $this->getPackages('2*SML_20LB,5*SML_30LB');
        $this->ensurePackageTotalPrice($packages,2*24+5*13);
        $this->ensurePackageTotalWeight($packages,2*20+5*30);
        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],2*20+2*30);
        $this->ensurePackagePrice($packages[0],2*24+2*13);
        $this->ensurePackageDimensions($packages[0],array(20,25,29));
        $this->ensurePackageWeight($packages[1],3*30);
        $this->ensurePackagePrice($packages[1],3*13);
        $this->ensurePackageDimensions($packages[1],array(20,25,29));

    }

    /**
     * How to ship 130lb when choice of 3 boxes, 30, 70, 120lb
     * TODO: Test this with latest code to see which is cheaper
     */
    public function test2DiffMaxWeight130lb() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test2DiffMaxWeight130lb -  test selects right box weight 20lb * 2 & 30lb *3' );

        $packages = $this->getPackages('2*SML_20LB,3*SML_30LB');
        $this->ensurePackageSize($packages,2);
        $this->ensurePackageTotalPrice($packages,2*24+3*13);
        $this->ensurePackageTotalWeight($packages,2*20+3*30);
        $this->ensurePackageWeight($packages[0],2*20+1*30);
        $this->ensurePackagePrice($packages[0],2*24+1*13);
        $this->ensurePackageDimensions($packages[0],array(20,25,28));
        $this->ensurePackageDimensions($packages[1],array(20,25,28));
        $this->ensurePackageWeight($packages[1],2*30);
        $this->ensurePackagePrice($packages[1],2*13);

    }




    /**
     * 130lb item in 70lb box - , should split.
     */
    public function test130lbin70lbBox() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test130lbin70lbBox -  test splits correctly 130lb item in 70lb box' );

        $packages = $this->getPackages('1*130LB_70LBBOX');
        $this->ensurePackageTotalPrice($packages,7);
        $this->ensurePackageTotalWeight($packages,130);

        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],1*70);
        $this->ensurePackagePrice($packages[0],70*(7/130));
        $this->ensurePackageDimensions($packages[0],array(20,25,28));
        $this->ensurePackageDimensions($packages[1],array(20,25,28));
        $this->ensurePackageWeight($packages[1],60);
        $this->ensurePackagePrice($packages[1],60*(7/130));

    }

    /**
     * 1 plane alone, 1 package //40.6x7.9x10.8
     *
     * @loadFixture config-roundup
     */
    public function test1Plane1() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test1Plane1 -  test dim plane' );
        $packages = $this->getPackages('1*PLANE_103x27.5x20_2.24');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*3);
        $this->ensurePackagePrice($packages[0],1*6);
        $this->ensurePackageDimensions($packages[0],array(41,8,11));
        $this->ensurePackageTotalPrice($packages,1*6);
        $this->ensurePackageTotalWeight($packages,1*3);
    }


    /**
     * 1 plane with batteries that can be added for free.
     *
     * @loadFixture config-roundup
     */
    public function test1Plane1Batteries() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test1Plane1Batteries -  test dim plane with batteries that take up no space' );
        $packages = $this->getPackages('1*PLANE_103x27.5x20_2.24,1*BATTERIES_0.27');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],ceil(1*2.24+1*0.27));
        $this->ensurePackagePrice($packages[0],1*6+1*4);
        $this->ensurePackageDimensions($packages[0],array(41,8,11));
        $this->ensurePackageTotalPrice($packages,1*6+1*4);
        $this->ensurePackageTotalWeight($packages,ceil(1*2.24+1*0.27));
    }


    /**
     * 1 plane with batteries that can be added for free.
     * @loadFixture config-roundup
     */
    public function test1Plane10Batteries() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test1Plane10Batteries -  test dim plane with batteries that take up no space' );
        $packages = $this->getPackages('1*PLANE_103x27.5x20_2.24,10*BATTERIES_0.27');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],ceil(1*2.24+10*0.27));
        $this->ensurePackagePrice($packages[0],1*6+10*4);
        $this->ensurePackageDimensions($packages[0],array(41,8,11));
        $this->ensurePackageTotalPrice($packages,1*6+10*4);
        $this->ensurePackageTotalWeight($packages,ceil(1*2.24+10*0.27));
    }

    /**
     * 1 plane with batteries that can be added for free.
     * @loadFixture config-roundup
     */
    public function test1Plane1BatteriesRev() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test1Plane1BatteriesRev -  test dim plane with batteries that take up no space reverse' );
        $packages = $this->getPackages('1*BATTERIES_0.27,1*PLANE_103x27.5x20_2.24');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],ceil(1*2.24+1*0.27));
        $this->ensurePackagePrice($packages[0],1*6+1*4);
        $this->ensurePackageDimensions($packages[0],array(41,8,11));
        $this->ensurePackageTotalPrice($packages,1*6+1*4);
        $this->ensurePackageTotalWeight($packages,ceil(1*2.24+1*0.27));
    }


    /**
     * 1 plane with batteries that can be added for free.
     * @loadFixture config-roundup
     */
    public function test1Plane10BatteriesRev() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test1Plane10BatteriesRev -  test dim plane with batteries that take up no space' );
        $packages = $this->getPackages('10*BATTERIES_0.27,1*PLANE_103x27.5x20_2.24');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],ceil(1*2.24+10*0.27));
        $this->ensurePackagePrice($packages[0],1*6+10*4);
        $this->ensurePackageDimensions($packages[0],array(41,8,11));
        $this->ensurePackageTotalPrice($packages,1*6+10*4);
        $this->ensurePackageTotalWeight($packages,ceil(1*2.24+10*0.27));
    }

    /**
     * 1 plane with batteries that can be added for free.
     * @loadFixture config-roundup
     */
    public function test1Plane100Batteries() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test1Plane100Batteries -  test dim plane with batteries that take up no space' );
        $packages = $this->getPackages('1*PLANE_103x27.5x20_2.24,100*BATTERIES_0.27');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],ceil(1*2.24+100*0.27));
        $this->ensurePackagePrice($packages[0],1*6+100*4);
        $this->ensurePackageDimensions($packages[0],array(41,8,11));
        $this->ensurePackageTotalPrice($packages,1*6+100*4);
        $this->ensurePackageTotalWeight($packages,ceil(1*2.24+100*0.27));
    }

    /**
     * 2 plane with batteries that can be added for free.
     * No roundup
     */
    public function test2Plane100BatteriesNoRoundup() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test2Plane100BatteriesNoRoundup -  test 2 dim plane with batteries that take up no space' );
        $packages = $this->getPackages('2*PLANE_103x27.5x20_2.24,100*BATTERIES_0.27');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageTotalPrice($packages,2*6+100*4);
        $this->ensurePackageTotalWeight($packages,2*2.24+100*0.27);
        $this->ensurePackagePrice($packages[0],2*6+100*4);
        $this->ensurePackageWeight($packages[0],2*2.24+100*0.27);
        $this->ensurePackageDimensions($packages[0],array(40.6,7.9,10.8));
    }

    /**
     * 2 plane with batteries that can be added for free.
     * @loadFixture config-roundup
     */
    public function test2Plane100Batteries() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test2Plane100Batteries -  test 2 dim plane with batteries that take up no space' );
        $packages = $this->getPackages('2*PLANE_103x27.5x20_2.24,100*BATTERIES_0.27');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageTotalPrice($packages,2*6+100*4);
        $this->ensurePackageTotalWeight($packages,ceil(2*2.24+100*0.27));
        $this->ensurePackagePrice($packages[0],2*6+100*4);
        $this->ensurePackageWeight($packages[0],ceil(2*2.24+100*0.27));
        $this->ensurePackageDimensions($packages[0],array(41,8,11));
    }

    /**
     * SML_80LB_1PKG
     */
    public function test1SML80OwnPackage() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test1SML80OwnPackage -  test 80LB item that cant be split' );
        $packages = $this->getPackages('1*SML_80LB_1PKG');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*80);
        $this->ensurePackagePrice($packages[0],1*18);
        $this->ensurePackageDimensions($packages[0],array(25,30,35));
        $this->ensurePackageTotalPrice($packages,1*18);
        $this->ensurePackageTotalWeight($packages,1*80);
    }

    /**
     * SML_80LB_3PKG
     *
     * Diff 1 large box, 3 small - is cheaper to send 1 large box air, slight cheaper to send 3 boxes ground. On looking lets put it air
     *
     *  3 Small Box
     *
     * Priority Mail $71.20
    United Parcel Service
    UPS Ground $41.06
    UPS Second Day Air $73.64
    UPS Three-Day Select $87.34
    UPS Next Day Air $118.63
    UPS Next Day Air Early A.M. $292.72

     * 1 large box

    United Parcel Service
    UPS Ground $41.93
    UPS Second Day Air $69.66
    UPS Three-Day Select $79.34
    UPS Next Day Air $115.83
    UPS Next Day Air Early A.M. $220.97
     */
    public function test1SML80Own3Package() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('test1SML80Own3Package -  test 80LB item that can be split, 3 box types' );
        $packages = $this->getPackages('1*SML_80LB_3PKG');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageTotalWeight($packages,1*80);
        $this->ensurePackageTotalPrice($packages,1*12);
        $this->ensurePackageWeight($packages[0],80);
        $this->ensurePackagePrice($packages[0],12);
        $this->ensurePackageDimensions($packages[0],array(35,30,25));


    }


    /**
     * Split 1 Item into multiple boxes
     */
    public function testSplitItemIntoMultipleBoxes() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testSplitItemIntoMultipleBoxes -  test 80LB item that can be split, only 1 box type' );
        $packages = $this->getPackages('1*SML_80LB_3PKG_1BOX');
        $this->ensurePackageSize($packages,3);
        $this->ensurePackageTotalWeight($packages,1*80);
        $this->ensurePackageTotalPrice($packages,1*12);
        $this->ensurePackageWeight($packages[0],30);
        $this->ensurePackagePrice($packages[0],12*(30/80));
        $this->ensurePackageDimensions($packages[0],array(10,15,18));
        $this->ensurePackageWeight($packages[1],30);
        $this->ensurePackagePrice($packages[1],12*(30/80));
        $this->ensurePackageDimensions($packages[1],array(10,15,18));
        $this->ensurePackageWeight($packages[2],20);
        $this->ensurePackagePrice($packages[2],12*(20/80));
        $this->ensurePackageDimensions($packages[2],array(10,15,18));

    }


    /**
     * Cant box it as to large, so send as standard
     */
    public function testSendAsStandardBox() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testSendAsStandardBox -  Cant box it as to large, so send as standard' );
        $packages = $this->getPackages('1*SML_80LB_1PKG_1BOX');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageTotalWeight($packages,1*80);
        $this->ensurePackageTotalPrice($packages,1*12);
        $this->ensurePackageWeight($packages[0],80);
        $this->ensurePackagePrice($packages[0],12);
        $this->ensurePackageDimensions($packages[0],array(0,0,0));
    }





    /**
     * packing weight test
     */
    public function testPackWeight1Item() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testPackWeight1Item -  1 item with packing weight' );
        $packages = $this->getPackages('1*PackWeight20_Qty5_6lb');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1*6+20);
        $this->ensurePackagePrice($packages[0],1*5);
        $this->ensurePackageDimensions($packages[0],array(0,0,0));
        $this->ensurePackageTotalWeight($packages,1*6+20);
        $this->ensurePackageTotalPrice($packages,1*5);
    }


    /**
     * packing weight test
     */
    public function testPackWeight4Item() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testPackWeight4Item -  4 items with packing weight' );
        $packages = $this->getPackages('4*PackWeight20_Qty5_6lb');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],4*6+20);
        $this->ensurePackagePrice($packages[0],4*5);
        $this->ensurePackageDimensions($packages[0],array(0,0,0));
        $this->ensurePackageTotalWeight($packages,4*6+20);
        $this->ensurePackageTotalPrice($packages,4*5);
    }

    /**
     * packing weight test
     */
    public function testPackWeight6Item() {

        Mage::helper('webshopapps_wsatestframework/log')->debug('testPackWeight6Item -  6 items with packing weight' );
        $packages = $this->getPackages('6*PackWeight20_Qty5_6lb');
        $this->ensurePackageSize($packages,2);
        $this->ensurePackageTotalWeight($packages,6*6+2*20);
        $this->ensurePackageTotalPrice($packages,6*5);
        $this->ensurePackageWeight($packages[0],5*6+20);
        $this->ensurePackagePrice($packages[0],5*5);
        $this->ensurePackageDimensions($packages[0],array(0,0,0));
        $this->ensurePackageWeight($packages[1],1*6+20);
        $this->ensurePackagePrice($packages[1],1*5);
        $this->ensurePackageDimensions($packages[1],array(0,0,0));

    }


    /**
     * DIMShIP-44 calculation logic not correct when multiple items not sharing boxes
     */
    public function testGoodwindsSingle3BoxSelectSmallest() {
        Mage::helper('webshopapps_wsatestframework/log')->debug(
            'testGoodwindsSingle3BoxSelectSmallest - 3 boxes pick smallest');

        $packages = $this->getPackages('1*Goodwinds_3boxes');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],0.05+1);  // packing weight 1
        $this->ensurePackageDimensions($packages[0],array(61,4,4));
    }

    /**
     * DIMShIP-44 calculation logic not correct when multiple items not sharing boxes
     */
    public function testGoodwindsMultiple3BoxSelectSmallest() {
        Mage::helper('webshopapps_wsatestframework/log')->debug(
            'testGoodwindsMultiple3BoxSelectSmallest - 3 boxes pick smallest, 2 items of different boxes in cart');

        $packages = $this->getPackages('1*Goodwinds_3boxes,1*SML_80LB_3PKG');

        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[1],0.05+1);  // packing weight 1
        $this->ensurePackageDimensions($packages[1],array(61,4,4));
    }



    /**
     * DIMSHIP-43 - Test to ensure recalculates boxes when has greater than 1 box for a single product
     */
    public function testDiffBoxQtys1Item()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('DIFF_BOX_QTYS_85 - Test 8 items in single box');

        $packages = $this->getPackages('15*DIFF_BOX_QTYS_85');

        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],12);
        $this->ensurePackageDimensions($packages[0],array(10,10,10));
        $this->ensurePackageWeight($packages[1],3);
        $this->ensurePackageDimensions($packages[1],array(5,5,5));
    }


    // Also test fractions
    public function testSimpleFraction1Prod1Box1Item()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSimpleFraction1Prod1Box1Item - Test 2 products 1.5 qty in box, single box');

        $packages = $this->getPackages('1*FRACT_BOX_QTY1_5_86');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1);
        $this->ensurePackageDimensions($packages[0],array(12,12,12));
    }

    public function testSimpleFraction1Prod1Box2Item()  {
        Mage::helper('webshopapps_wsatestframework/log')->debug('testSimpleFraction1Prod1Box2Item - Test 2 products 1.5 qty in box, single box');

        $packages = $this->getPackages('2*FRACT_BOX_QTY1_5_86');

        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],1);
        $this->ensurePackageWeight($packages[1],1);
        $this->ensurePackageDimensions($packages[0],array(12,12,12));
        $this->ensurePackageDimensions($packages[1],array(12,12,12));
    }

    public function testSimpleFraction2Prod1Box2Item()  {
        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testSimpleFraction2Prod1Box2Item - Test 2 diff products, 2 items each, 1.5 qty in box, single box non shared');

        $packages = $this->getPackages('2*FRACT_BOX_QTY1_5_86,2*FRACT_BOX_QTY2_3_87');

        $this->ensurePackageSize($packages,3);
        $this->ensurePackageWeight($packages[0],1);
        $this->ensurePackageWeight($packages[1],1);
        $this->ensurePackageWeight($packages[2],6);
        $this->ensurePackageDimensions($packages[0],array(12,12,12));
        $this->ensurePackageDimensions($packages[1],array(12,12,12));
        $this->ensurePackageDimensions($packages[2],array(12,12,12));

    }

    public function testSharedFraction1Prod1Box1Item()  {
        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testSharedFraction1Prod1Box1Item - Test 2 products 1.5 qty in box, single box shared');

        $packages = $this->getPackages('1*FRACT_SHARED_BOX_QTY1_5_88');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],2); // box packing weight of 1
        $this->ensurePackageDimensions($packages[0],array(12,12,12));
    }

    public function testSharedFraction1Prod1Box2Item()  {
        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testSharedFraction1Prod1Box2Item - Test 2 products 1.5 qty in box, single box shared');

        $packages = $this->getPackages('2*FRACT_SHARED_BOX_QTY1_5_88');

        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],2);
        $this->ensurePackageWeight($packages[1],2);
        $this->ensurePackageDimensions($packages[0],array(12,12,12));
        $this->ensurePackageDimensions($packages[1],array(12,12,12));
    }

    public function testSharedFraction1Prod1Box2ItemFull()  {
        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testSharedFraction1Prod1Box2ItemFull - Test 1 diff products, 2 items each, 1 qty in box, 2max');

        $packages = $this->getPackages('2*FRACT_SHARED_BOX_QTY2_3_89'); //  2 respectively

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],7);
        $this->ensurePackageDimensions($packages[0],array(12,12,12));

    }

    public function testSharedFraction2Prod1Box2ItemSep()  {
        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testSharedFraction2Prod1Box2Item - Test 2 diff products, 2 items each, 1.5 qty in box, single box shared no space');

        $packages = $this->getPackages('2*FRACT_SHARED_BOX_QTY1_5_88,2*FRACT_SHARED_BOX_QTY2_3_89'); // max boxes 1.5, 2 respectively

        $this->ensurePackageSize($packages,3);
        $this->ensurePackageWeight($packages[0],2);
        $this->ensurePackageWeight($packages[1],2);
        $this->ensurePackageWeight($packages[2],7);
        $this->ensurePackageDimensions($packages[0],array(12,12,12));
        $this->ensurePackageDimensions($packages[1],array(12,12,12));
        $this->ensurePackageDimensions($packages[2],array(12,12,12));
    }

    public function testSharedFraction2Prod1Box2ItemReverseSep()  {
        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testSharedFraction2Prod1Box2ItemReverse - Test 2 diff products, 2 items each, 1.5 qty in box, single box shared');

        $packages = $this->getPackages('2*FRACT_SHARED_BOX_QTY2_3_89,2*FRACT_SHARED_BOX_QTY1_5_88');

        $this->ensurePackageSize($packages,3);
        $this->ensurePackageWeight($packages[2],2);
        $this->ensurePackageWeight($packages[1],2);
        $this->ensurePackageWeight($packages[0],7);
        $this->ensurePackageDimensions($packages[0],array(12,12,12));
        $this->ensurePackageDimensions($packages[1],array(12,12,12));
        $this->ensurePackageDimensions($packages[2],array(12,12,12));


        $this->ensurePackagePrice($packages[0],2*6);
        $this->ensurePackagePrice($packages[1],1*15);
        $this->ensurePackagePrice($packages[2],1*15);
        $this->ensurePackageTotalWeight($packages,2*1+2*3+3);
        $this->ensurePackageTotalPrice($packages,2*6+2*15);

    }

    public function testLargeSmallItemNoVolSmall()  {
        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testLargeSmallItemNoVolSmall - Test large and small item where small takes up no volume');

        $packages = $this->getPackages('1*LARGE_ITEM_5LB_90,1*SMALL_ITEM_1LB_91');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageTotalWeight($packages,5+1);
        $this->ensurePackageTotalPrice($packages,12+20);
        $this->ensurePackageWeight($packages[0],5+1);
        $this->ensurePackageDimensions($packages[0],array(12,14,14));
        $this->ensurePackagePrice($packages[0],12+20);


    }

    /**
     * 2 items, both can fit in 1 of boxes, but first item will fill the box up
     * Need to ensure it re-assesses the boxes after the first one is full up
     */
    public function testTwoTubes()  {
        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testTwoTubes - Test large and small tubes to ensure selects correct');

        $packages = $this->getPackages('1*A0_278_92,1*300_400_95lb_93');

        $this->ensurePackageSize($packages,2);
        $this->ensurePackageWeight($packages[0],205+278);
        $this->ensurePackageWeight($packages[1],37+95);
        $this->ensurePackageTotalWeight($packages,615);
        /*   $this->ensurePackageTotalPrice($packages,12+20);
             $this->ensurePackageWeight($packages[0],5+1);
             $this->ensurePackageDimensions($packages[0],array(12,14,14));
             $this->ensurePackagePrice($packages[0],12+20);*/


    }


    /**
     * Scenario from ldavis@ventata.com
     * 12 * 1 item, has 3 possible boxes. Is not selecting properly when max qty is 9999
     */
    public function test12Ices()  {
        Mage::helper('webshopapps_wsatestframework/log')->
            debug('test12Ices - Test 12*ices goes into correct box, no box max qty');

        $packages = $this->getPackages('12*LUKE_SMART_LIVING_ICES');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageTotalWeight($packages,2.9*12);
        $this->ensurePackageTotalPrice($packages,17.5*12);
        $this->ensurePackagePrice($packages[0],17.5*12);
        $this->ensurePackageWeight($packages[0],2.9*12);
        $this->ensurePackageDimensions($packages[0],array(31.5,18,8));


    }

    /**
     * Scenario from ldavis@ventata.com
     * 12 * 1 item, has 3 possible boxes. Is not selecting properly when max qty is 9999
     */
    public function test24Ices()  {
        Mage::helper('webshopapps_wsatestframework/log')->
            debug('test24Ices - Test 24*ices goes into correct box, no box max qty');

        $packages = $this->getPackages('24*LUKE_SMART_LIVING_ICES');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageTotalWeight($packages,2.9*24);
        $this->ensurePackageTotalPrice($packages,17.5*24);
        $this->ensurePackagePrice($packages[0],17.5*24);
        $this->ensurePackageWeight($packages[0],2.9*24);
        $this->ensurePackageDimensions($packages[0],array(31.5,18,16));


    }

    /**
     * Scenario from ldavis@ventata.com
     * 12 * 1 item, has 3 possible boxes. Is not selecting properly when max qty is 9999
     */
    public function test12Ices9999()  {
        Mage::helper('webshopapps_wsatestframework/log')->
            debug('test12Ices9999 - Test 12*ices goes into correct box, 9999 box max qty');

        $packages = $this->getPackages('12*LUKE_SMART_LIVING_ICES_9999');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageTotalWeight($packages,2.9*12);
        $this->ensurePackageTotalPrice($packages,17.5*12);
        $this->ensurePackagePrice($packages[0],17.5*12);
        $this->ensurePackageWeight($packages[0],2.9*12);
        $this->ensurePackageDimensions($packages[0],array(31.5,18,8));


    }

    /**
     * Scenario from ldavis@ventata.com
     * 12 * 1 item, has 3 possible boxes. Found out issue was config.
     */
    public function test24Ices9999()  {
        Mage::helper('webshopapps_wsatestframework/log')->
            debug('test24Ices9999 - Test 24*ices goes into correct box, 9999 box max qty');

        $packages = $this->getPackages('24*LUKE_SMART_LIVING_ICES_9999');

        $this->ensurePackageSize($packages,2);
        $this->ensurePackageTotalWeight($packages,2.9*24);
        $this->ensurePackageTotalPrice($packages,17.5*24);
        $this->ensurePackagePrice($packages[0],17.5*12);
        $this->ensurePackageWeight($packages[0],2.9*12);
        $this->ensurePackageDimensions($packages[0],array(31.5,18,8));
        $this->ensurePackagePrice($packages[1],17.5*12);
        $this->ensurePackageWeight($packages[1],2.9*12);
        $this->ensurePackageDimensions($packages[1],array(31.5,18,8));
    }


    /**
     * TODO Currently broke - To be investigated
     * DIMSHIP-48
     */
//    public function testMinus99LargeBoxMaxWeight()  {
//
//        Mage::helper('webshopapps_wsatestframework/log')->
//            debug('testMinus99LargeBoxMaxWeight - Test max box weight is not exceeded when -99 used for a Box Definition (Large Box)');
//
//        $packages = $this->getPackages('10*MINUS_99_PRODUCT_10LBS');
//        $this->ensurePackageSize($packages,2);
//
//        $this->ensurePackageWeight($packages[0],60);
//        $this->ensurePackageWeight($packages[1],40);
//        $this->ensurePackageTotalWeight($packages,100);
//    }


    /**
     * TODO Currently broke - To be investigated
     * DIMSHIP-48
     */
//    public function testLargeBoxMaxWeight()  {
//
//        Mage::helper('webshopapps_wsatestframework/log')->
//            debug('testLargeBoxMaxWeight - Test max box weight is not exceeded using a Box Definition (Large Box)');
//
//        $packages = $this->getPackages('10*PRODUCT_10LBS');
//        $this->ensurePackageSize($packages,2);
//
//        $this->ensurePackageWeight($packages[0],60);
//        $this->ensurePackageWeight($packages[1],40);
//        $this->ensurePackageTotalWeight($packages,100);
//    }

    /**
     * Currently broke - To be investigated TODO
     * DIMSHIP-48
     */
    public function testMinus99CarrierMaxWeightB()  {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testMinus99CarrierMaxWeightB - Test box weight < max carrier weight when -99 used. Box def max=-1');

        $packages = $this->getPackages('4*MINUS_99_PRODUCT_50LBS_B');
        $this->ensurePackageSize($packages,2);

        $this->ensurePackageWeight($packages[0],150); // max carrier weight variable? sql value? default is 150
        $this->ensurePackageWeight($packages[1],50);
        $this->ensurePackageTotalWeight($packages,200);
    }


    /**
     * Currently broke - To be investigated TODO
     * DIMSHIP-48
     */
    public function testMinus99CarrierMaxWeightA() {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testMinus99CarrierMaxWeightA - Test box weight < max carrier weight when -99 used. Box def max=0');

        // box def max=0 adds only one item?
        // this is expected behaviour
        // box definition should have a max weight of -1 or >0. Not =0.

        $packages = $this->getPackages('4*MINUS_99_PRODUCT_50LBS');
        $this->ensurePackageSize($packages,4);

        $this->ensurePackageWeight($packages[0],50);
        $this->ensurePackageWeight($packages[1],50);
        $this->ensurePackageTotalWeight($packages,200);
    }


    /**
     * DIMSHIP-63 DimShip uses a larger box when it could use smaller - can't reproduce
     */
    public function testSmallestBox1() {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testSmallestBox1 - Test if smallest box is used when one item in package');

        $packages = $this->getPackages('1*72IN_LONG_x1');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],1+1); // packing weight of 1lb
        $this->ensurePackageTotalPrice($packages,6);
        $this->ensurePackagePrice($packages[0],6);
        $this->ensurePackageTotalWeight($packages,1+1);
        $this->ensurePackageDimensions($packages[0],array(74,4,4));
    }


    /**
     * DIMSHIP-63 DimShip uses a larger box when it could use smaller
     *
     *    The correct box 74x4x4 has a weight of 1
     * The incorrect box 98x4x4 has a weight of 2
     * ( 100 items * 1kg ) + box weight 1
     * correct weight should be 101, not 102
     */
    public function testSmallestBox100() {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testSmallestBox100 - Test if smallest box is used when many items in package');

        $packages = $this->getPackages('100*72IN_LONG_x100');

        $this->ensurePackageSize($packages,1);
        $this->ensurePackageWeight($packages[0],101);
        $this->ensurePackageTotalWeight($packages,101);
        $this->ensurePackageTotalPrice($packages,6*100);
        $this->ensurePackagePrice($packages[0],6*100);
        $this->ensurePackageDimensions($packages[0],array(74,4,4));

    }


    /**
     * DIMSHIP-62 Highlights issue where 2 items of qty 1 dont pack in the same box - 2 items qty 2 box
     */
    public function testMultiItemShippingBoxes2items() {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testMultiItemShippingBoxes2items - Test if 2 items set to same box with same quantity, pack in qty 2 box');

        $packages = $this->getPackages('1*MULTI_ITEM_A,1*MULTI_ITEM_B');
        $this->ensurePackageSize($packages,1);

        $this->ensurePackageWeight($packages[0],2);
        $this->ensurePackageTotalWeight($packages,2);
        $this->ensurePackageDimensions($packages[0],array(10,10,10));
    }

    /**
     * DIMSHIP-62 Highlights issue where 2 items of qty 1 dont pack in the same box  - 1 item, qty 1 box
     */
    public function testMultiItemShippingBoxes1Item() {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testMultiItemShippingBoxes1Item - Test if 1 item set to same box with same quantity, pack in small box');

        $packages = $this->getPackages('1*MULTI_ITEM_A');
        $this->ensurePackageSize($packages,1);

        $this->ensurePackageWeight($packages[0],1);
        $this->ensurePackageTotalWeight($packages,1);
        $this->ensurePackageDimensions($packages[0],array(9,9,9));
    }

    /**
     * DIMSHIP-62 Highlights issue where 2 items of qty 1+2 dont pack in the same box - 2 items qty 2 box
     */
    public function testMultiItemShippingBoxes3items() {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testMultiItemShippingBoxes3items - Test if 3 items set to same box with same quantity, pack in qty 3 box');

        $packages = $this->getPackages('1*MULTI_ITEM_A,2*MULTI_ITEM_B');
        $this->ensurePackageSize($packages,1);

        $this->ensurePackageWeight($packages[0],3);
        $this->ensurePackageTotalWeight($packages,3);
        $this->ensurePackageDimensions($packages[0],array(11,11,11));
    }

    /**
     * DIMSHIP-62 Highlights issue where 2 items of qty 1+2 dont pack in the same box - 2 items qty 2 box
     * Could argue would pack into the 15 and 10, but I think this is okay
     * 1lb item
     */
    public function testNuethicPacking() {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testNuethicPacking - Ship 15 items, not choosing correct boxes');

        $packages = $this->getPackages('15*NUETHIC_PACK');
        $this->ensurePackageSize($packages,2);
        $this->ensurePackageTotalWeight($packages,15);

        $this->ensurePackageDimensions($packages[0],array(23,23,13));
        $this->ensurePackageWeight($packages[0],8);

        $this->ensurePackageDimensions($packages[1],array(23,23,13));
        $this->ensurePackageWeight($packages[1],7);
    }


    /**
     * DIMSHIP-130 Case qty, divde the qty by the divider, price each 15.
     */
    public function testCaseQuantity36() {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testCaseQuantity3 - Case Qty is 12, 36 in cart, dividor is 3. Will result in 1 boxes');

        $packages = $this->getPackages('36*CASE_QTY_3_12BOX_3LB');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageTotalWeight($packages,3*36);
        $this->ensurePackageTotalPrice($packages,15*36);

        $this->ensurePackageDimensions($packages[0],array(12,14,14));
        $this->ensurePackageWeight($packages[0],36*3);
        $this->ensurePackagePrice($packages[0],36*15);
    }


    /**
     * DIMSHIP-130 Case qty, divde the qty by the divider, price each 15.
     */
    public function testCaseQuantity37() {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testCaseQuantity3 - Case Qty is 12, 37 in cart, dividor is 3. Will result in 2 boxes');

        $packages = $this->getPackages('37*CASE_QTY_3_12BOX_3LB');
        $this->ensurePackageSize($packages,2);
        $this->ensurePackageTotalWeight($packages,3*37);
        $this->ensurePackageTotalPrice($packages,15*37);

        $this->ensurePackageDimensions($packages[0],array(12,14,14));
        $this->ensurePackageWeight($packages[0],36*3);
        $this->ensurePackagePrice($packages[0],36*15);

        $this->ensurePackageDimensions($packages[1],array(12,14,14));
        $this->ensurePackageWeight($packages[1],1*3);
        $this->ensurePackagePrice($packages[1],1*15);
    }


    /**
     * DIMSHIP-130 Case qty, divde the qty by the divider, price each 15.
     */
    public function testCaseQuantity72() {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testCaseQuantity3 - Case Qty is 12, 72 in cart, dividor is 3. Will result in 2 boxes');

        $packages = $this->getPackages('72*CASE_QTY_3_12BOX_3LB');
        $this->ensurePackageSize($packages,2);
        $this->ensurePackageTotalWeight($packages,3*72);
        $this->ensurePackageTotalPrice($packages,15*72);

        $this->ensurePackageDimensions($packages[0],array(12,14,14));
        $this->ensurePackageWeight($packages[0],36*3);
        $this->ensurePackagePrice($packages[0],36*15);

        $this->ensurePackageDimensions($packages[1],array(12,14,14));
        $this->ensurePackageWeight($packages[1],36*3);
        $this->ensurePackagePrice($packages[1],36*15);
    }


    /**
     * DIMSHIP-130 Case qty, divde the qty by the divider, price each 15.
     */
    public function testCaseQuantity35() {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testCaseQuantity3 - Case Qty is 12, 35 in cart, dividor is 3. Will result in 1 boxes');

        $packages = $this->getPackages('35*CASE_QTY_3_12BOX_3LB');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageTotalWeight($packages,3*35);
        $this->ensurePackageTotalPrice($packages,15*35);

        $this->ensurePackageDimensions($packages[0],array(12,14,14));
        $this->ensurePackageWeight($packages[0],35*3);
        $this->ensurePackagePrice($packages[0],35*15);
    }



    /**
     * DIMSHIP-130 Case qty, divde the qty by the divider, price each 15.
     */
    public function testCaseQuantity1() {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testCaseQuantity3 - Case Qty is 12, 1 in cart, dividor is 3. Will result in 1 boxes');

        $packages = $this->getPackages('1*CASE_QTY_3_12BOX_3LB');
        $this->ensurePackageSize($packages,1);
        $this->ensurePackageTotalWeight($packages,3*1);
        $this->ensurePackageTotalPrice($packages,15*1);

        $this->ensurePackageDimensions($packages[0],array(12,14,14));
        $this->ensurePackageWeight($packages[0],1*3);
        $this->ensurePackagePrice($packages[0],1*15);

    }


    /**
     * DIMSHIP-130 Case qty, divde the qty by the divider, price each 15.
     */
    public function testNoCaseQuantity36() {

        Mage::helper('webshopapps_wsatestframework/log')->
            debug('testCaseQuantity3 - Case Qty is 12, 36 in cart, dividor is not set. Will result in 3 boxes');

        $packages = $this->getPackages('36*NO_CASE_QTY_12BOX_3LB');
        $this->ensurePackageSize($packages,3);
        $this->ensurePackageTotalWeight($packages,3*36);
        $this->ensurePackageTotalPrice($packages,15*36);

        $this->ensurePackageDimensions($packages[0],array(12,14,14));
        $this->ensurePackageWeight($packages[0],12*3);
        $this->ensurePackagePrice($packages[0],12*15);

        $this->ensurePackageDimensions($packages[1],array(12,14,14));
        $this->ensurePackageWeight($packages[1],12*3);
        $this->ensurePackagePrice($packages[1],12*15);

        $this->ensurePackageDimensions($packages[2],array(12,14,14));
        $this->ensurePackageWeight($packages[2],12*3);
        $this->ensurePackagePrice($packages[2],12*15);
    }



}