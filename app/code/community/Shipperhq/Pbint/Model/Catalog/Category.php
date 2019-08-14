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
class Shipperhq_Pbint_Model_Catalog_Category
{
    protected $category;
    private static $roots;
    protected $url;
    protected $_debug;

    public function __construct($id, $url = null)
    {

        $this->_debug = Mage::helper('shipperhq_pbint')->isDebug();
        if (gettype($id) == "object" && $id instanceOf Mage_Catalog_Model_Category) {
            $this->category = $id;
        } else {
            $this->category = Mage::getModel('catalog/category')->load($id);
        }
        $this->url = $url;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function getName()
    {
        return $this->category->getName();
    }

    public function getCode()
    {
        return $this->category->getId();
    }

    public function getUrl()
    {
        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'Category processing, url is ', $this->category->getUrl() );
        }
        if ($this->url)
            return $this->url;
        return $this->category->getUrl();
    }

    public function isRoot()
    {
        if (!$this->category->getParentId()) {
            return true;
        }
        if (!self::$roots) {
            $categoryOp = new Mage_Adminhtml_Block_Catalog_Category_Abstract();
            self::$roots = $categoryOp->getRootIds();
        }
        foreach (self::$roots as $rootId) {
            if ($this->getCode() == $rootId) {
                return true;
            }
        }
        return false;
    }

    public function getParentCode()
    {
        return $this->category->getParentId();
    }

    public function writeToFile($file)
    {
        $name = Shipperhq_Pbint_Model_Catalog_File::stripHtml($this->getName());
        $name = preg_replace("/[,.\-\+=;:\\(){}\[\]@?%$#]/", '', $name);
        $parentCateID = '';
        if ($this->getParentCode() != 1 && !$this->isRoot()) {
            $parentCateID = $this->getParentCode();
        }
        fputcsv($file, array($this->getCode(), $parentCateID, $name, '', $this->getUrl()));
    }

    public function upload()
    {
        if ($this->_debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'Uploading category:  ', $this->category->getName() . "... " );
        }

        if (Shipperhq_Pbint_Model_Api::addCategory($this)) {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                    'Uploading category result:  ', 'OK');
            }
        } else {
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                    'Uploading category result:  ', 'Failed');
            }
        }
        $products = $this->category->getProductCollection();

        foreach ($products as $product) {
            $clearPathProduct = new Shipperhq_Pbint_Model_Catalog_Product($product);
            if ($this->_debug) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                    'Uploading product:  ',$clearPathProduct->getName() . "... ");
            }
            if (Shipperhq_Pbint_Model_Api::addCommodity($clearPathProduct)) {
                if ($this->_debug) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                        'Uploading product:  ',$clearPathProduct->getName() . " - OK");
                }
            } else {
                if ($this->_debug) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                        'Uploading product:  ',$clearPathProduct->getName() . "  - Failed");
                }
            }
        }


        $children = $this->category->getChildrenCategories();

        // TODO: this code will run out of memory if there are a lot of categories....
        // Need to convert the recursive call to a loop at some point.
        foreach ($children as $child) {
            $childCategory = new Shipperhq_Pbint_Model_Catalog_Category($child);
            $childCategory->upload();
        }
    }

    // TODO This fuction starts at the Magento root and its not very efficient... see isRoot for proper implementation.
    // This function is only used by the API upload.
    public static function getAllRootCategories()
    {
        $roots = array();

        $categories = Mage::getModel('catalog/category')->getCollection(); //->getSelect()->order(array('IF(`id`>0, `id`, 9999) ASC'));
        $categories = $categories->getIterator();

        foreach ($categories as $category) {
            $parents = $category->getParentId();
            if (!$parents) {
                $rootCategory = new Shipperhq_Pbint_Model_Catalog_Category($category->getId());
                array_push($roots, $rootCategory);
            }
        }
        return $roots;
    }

}

?>
