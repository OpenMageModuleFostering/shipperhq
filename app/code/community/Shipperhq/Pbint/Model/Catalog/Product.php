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
 * Shipper HQ Pitney Bowes International
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipping_Carrier
 * @copyright Copyright (c) 2014 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */

/**
 * Class Shipperhq_Pbint_Model_Catalog_Product
 *
 */
class Shipperhq_Pbint_Model_Catalog_Product
{
    /*@var $product Mage_Catalog_Model_Product */
    protected $product;
    protected $_productUrl;


    public function __construct($product, $url = null)
    {
        $id = $product;
        if (gettype($product) == "object" && $id instanceOf Mage_Catalog_Model_Product) {
            $this->product = $product;
        } else
            $this->product = Mage::getModel('catalog/product')->load($id);
        $this->_productUrl = $url;
    }

    /**
     * Created by BigPixel 6/15/2012
     * @return Mage_Catalog_Model_Product
     */
    public function getMageProduct()
    {
        return $this->product;
    }

    /**
     * @return string
     */
    public function getSKU()
    {
        return $this->product->getSku();
    }

    public function getPrice()
    {
        return $this->product->getPrice();
    }

    public function getUPC()
    {
    }

    /**
     * @return string
     */
    public function getURL()
    {
        if ($this->_productUrl)
            return $this->_productUrl;
        return $this->product->getProductUrl();
    }

    /**
     * @return int
     * Gets the id of the category that is direct parent of the product
     */
    public function getCategoryCode()
    {
        $categories = $this->product->getCategoryCollection();

        $maxDepth = -1;
        $finalCategory = null;
        foreach ($categories as $category) {
            $pathComponents = explode("/", $category->getPath());
            if (count($pathComponents) > $maxDepth) {
                $finalCategory = $category;
                $maxDepth = count($pathComponents);
            }
        }
        return $finalCategory->getId();
    }

    /**
     * @return string
     *
     */
    public function getCountryOfOrigin()
    {
        return $this->product->getCountryOfManufacture(); //added by kamran,1/14/2012
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return utf8_encode($this->product->getDescription());
    }

    public function getShortDescription()
    {
        return utf8_encode($this->product->getShortDescription());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return utf8_encode($this->product->getName());
    }

    /**
     * @return flaot
     */
    public function getWeight()
    {
        return $this->product->getWeight();
    }

    public function getSize()
    {
    }

    /**
     * @param $file
     * @param $categoryCode
     * Write this product into the xml file
     */
    public function writeToFile($file, $categoryCode, $parentSku, $category)
    {
        /** $categoryCode is passed in method because we don't have categoryCode for child products. BigPixel 6/11/2012 */
        $name = Shipperhq_Pbint_Model_Catalog_File::stripHtml($this->getName());
        $name = preg_replace("/[,.\-\+=;:\\(){}\[\]@?%$#]/", '', $name);
        $string = "<Commodity>\n";
        $string .= "<CategoryCode><![CDATA[" . htmlentities($categoryCode) . "]]></CategoryCode>\n";
        $string .= "<SKU><![CDATA[" . htmlentities($this->getSKU()) . "]]></SKU>\n";
        $string .= "<Name><![CDATA[" . htmlentities($name) . "]]></Name>\n";
        $string .= "<CountryOfOrigin><![CDATA[" . htmlentities(preg_replace("/[^A-Za-z0-9]/", '', $this->getCountryOfOrigin())) . "]]></CountryOfOrigin>\n"; //added by kamran,1/14/2012
        $description = Shipperhq_Pbint_Model_Catalog_File::stripHtml($this->getDescription());
        $description = preg_replace("/[,.\-\+=;:\\(){}\[\]@?%$#]/", '', $description);
        if (strlen($description) >= 2000) {

            $description = $this->chopString($description, 1999);
        }

        $shortDescription = Shipperhq_Pbint_Model_Catalog_File::stripHtml($this->getShortDescription());
        $shortDescription = preg_replace("/[,.\-\+=;:\\(){}\[\]@?%$#]/", '', $shortDescription);
        if (strlen($shortDescription) >= 2000) {

            $shortDescription = $this->chopString($shortDescription, 1999);
        }
        $string .= "<Description><![CDATA[" . $description . "]]></Description>\n";
        $string .= "<URL><![CDATA[" . htmlentities($this->getURL()) . "]]></URL>\n";

        if ($this->getWeight() != null) {
            $string .= "<Size><Weight><![CDATA[" . $this->getWeight() . "]]></Weight><Source><![CDATA[" . Shipperhq_Pbint_Model_Credentials::getMerchantCode() . "]]></Source></Size>\n";
        }
        $string .= "</Commodity>\n";
//        fputcsv($this->file,array('MERCHANT_COMMODITY_REF_ID','COMMODITY_NAME_TITLE','SHORT_DESCRIPTION',
//            'LONG_DESCRIPTION','RETAILER_ID','COMMODITY_URL','RETAILER_UNIQUE_ID','PCH_CATEGORY_ID',
//            'RH_CATEGORY_ID','STANDARD_PRICE','WEIGHT_UNIT','DISTANCE_UNIT','COO','IMAGE_URL','PARENT_SKU',
//            'CHILD_SKU','PARCELS_PER_SKU','UPC','UPC_CHECK_DIGIT','GTIN','MPN','ISBN','BRAND','MANUFACTURER',
//            'MODEL_NUMBER','MANUFACTURER_STOCK_NUMBER','COMMODITY_CONDITION','COMMODITY_HEIGHT',
//            'COMMODITY_WIDTH','COMMODITY_LENGTH','PACKAGE_WEIGHT','PACKAGE_HEIGHT','PACKAGE_WIDTH',
//            'PACKAGE_LENGTH','HAZMAT','ORMD','CHEMICAL_INDICATOR','PESTICIDE_INDICATOR','AEROSOL_INDICATOR',
//            'RPPC_INDICATOR','BATTERY_TYPE','NON_SPILLABLE_BATTERY','FUEL_RESTRICTION','SHIP_ALONE',
//            'RH_CATEGORY_ID_PATH','RH_CATEGORY_NAME_PATH','RH_CATEGORY_URL_PATH','GPC','COMMODITY_WEIGHT',
//            'HS_CODE','CURRENCY'));
        $merchantCode = Shipperhq_Pbint_Model_Credentials::getMerchantCode();
        fputcsv($file, array($this->getSKU(), $name, $shortDescription,
            $description, $merchantCode, $this->getURL(), $merchantCode, '',
            $categoryCode, $this->getPrice(), 'lb', '', $this->getCountryOfOrigin(), '', '',
            '', '', '', '', '', '', '', '', '',
            '', '', '', '',
            '', '', $this->getWeight(), '', '',
            '', '', '', '', '', '',
            '', '', '', '', '',
            strval($category->getData('id_path')), $category->getData('name_path')
            //,'','','','',$category->getData('store')->getCurrentCurrencyCode()
        ));
        //fwrite($file, $string);
    }

    /**
     * @param $lastDiff
     * @return bool
     * Checks whether we need to upload this product or not
     */
    public function shouldUpload($lastDiff)
    {
        $lastUpload = $this->product->getPbPbgspUpload();

        if (!$lastUpload) {
            // First upload.
            return true;
        } else if ($lastDiff <= $lastUpload - (30 * 60)) {
            // Added after the last diff
            return true;
        } else {
            return false;
        }
    }

    public function chopString($str, $len)
    {
        $end = $len;
        $lastFour = substr($str, $end - strlen("&amp"), strlen("&amp"));
        $pos = strpos($lastFour, "&");
        if (!($pos === FALSE)) {
            $end = $end - strlen("&amp") + $pos;
        }
        $ret = substr($str, 0, $end);
        return $ret;
    }

}

?>
