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
namespace ShipperHQ\Checkout;

/**
 * Class Cart
 *
 * @package ShipperHQ\Checkout
 */
class Cart {

   public $weight;
   public $price;
   public $qty;
   public $declaredValue;
   public $freeShipping;
   public $items;

   /**
    * @param float $declaredValue
    * @param boolean $freeShipping
    * @param array $items
    * @param float $price
    * @param int $qty
    * @param float $weight
    */
   function __construct($declaredValue = 0.0, $freeShipping = false, $items = array(),
                        $price = 0.0, $qty = 0, $weight = 0.0)
   {
      $this->declaredValue = $declaredValue;
      $this->freeShipping = $freeShipping;
      $this->items = $items;
      $this->price = $price;
      $this->qty = $qty;
      $this->weight = $weight;
   }

   /**
    * @param float $declaredValue
    */
   public function setDeclaredValue(\float $declaredValue)
   {
      $this->declaredValue = $declaredValue;
   }

   /**
    * @return float
    */
   public function getDeclaredValue()
   {
      return $this->declaredValue;
   }

   /**
    * @param boolean $freeShipping
    */
   public function setFreeShipping(\boolean $freeShipping)
   {
      $this->freeShipping = $freeShipping;
   }

   /**
    * @return boolean
    */
   public function getFreeShipping()
   {
      return $this->freeShipping;
   }

   /**
    * @param array $items
    */
   public function setItems(array $items)
   {
      $this->items = $items;
   }

   /**
    * @return array
    */
   public function getItems()
   {
      return $this->items;
   }

   /**
    * @param float $price
    */
   public function setPrice(\float $price)
   {
      $this->price = $price;
   }

   /**
    * @return float
    */
   public function getPrice()
   {
      return $this->price;
   }

   /**
    * @param int $qty
    */
   public function setQty(\integer $qty)
   {
      $this->qty = $qty;
   }

   /**
    * @return int
    */
   public function getQty()
   {
      return $this->qty;
   }

   /**
    * @param float $weight
    */
   public function setWeight(\float $weight)
   {
      $this->weight = $weight;
   }

   /**
    * @return float
    */
   public function getWeight()
   {
      return $this->weight;
   }
}