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
 * ST_Core_Model_Resource_Iterator_Batched : Kalen Jordan
 */
class Shipperhq_Migration_Model_Migrate extends Mage_Core_Model_Abstract
{

    const BATCH_SIZE = 100;
    /**
     * Assign values from one attribute to another - for text or numeric attribute types, uses callback function below
     * @param $oldAttributeCode
     * @param $newAttributeCode
     * @return int
     */
    public function copyAttributeValues($oldAttributeCode, $newAttributeCode, $storeId)
    {
        $collection = Mage::getModel('catalog/product')->setStoreId($storeId)->getCollection();
        $collection->addAttributeToSelect($oldAttributeCode);
        if($storeId == '') {
            $storeId = 0;
        }
        if($storeId != '') {
            $collection->addStoreFilter((int)$storeId);
        }
        $collection->addFieldToFilter(array(
            array('attribute'=>$oldAttributeCode,'neq'=>''),
        ));

        $collection->setPageSize(self::BATCH_SIZE);
        if($collection) {
            try {
                if(Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq Migration', 'Migration about to begin for',
                        ' products to copy value for ' .$oldAttributeCode);
                }

                $args = array('old_attribute'=> $oldAttributeCode, 'new_attribute'=> $newAttributeCode,
                    'store_id' => $storeId);
                $batcher = Mage::getSingleton('shipperhq_migration/resource_iterator_batched');
                $result = $batcher->walk(
                    $collection,
                    array($this, 'copyAttributeCallback'),
                    $args,
                    array($this, 'copyAttributeBatchCallback')
                );
            }
            catch(Exception $e){
                Mage::logException("ShipperHQ Migration Exception");
                Mage::logException($e);
                return -1;
            }
        }
        else {
            if(Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq Migration', 'No migration required',
                     'No products for ' .$oldAttributeCode);
            }
        }
        return $result;
    }

    /**
     * Callback function to copy attribute values
     * @param $args
     */
    public function copyAttributeCallback($args)
    {
        $product = $args[0];
        $data = $args[1];
        $oldAttributeCode = $data['old_attribute'];
        $newAttributeCode = $data['new_attribute'];
        $valueToCopy = $product[$oldAttributeCode];
        $productArray = array($product->getId());
        $attData = array($newAttributeCode => $valueToCopy);

        Mage::getResourceSingleton('catalog/product_action')
            ->updateAttributes($productArray, $attData, $data['store_id']);
    }

    public function copyAttributeBatchCallback($args)
    {
        $currentPage = (int)$args['current_page'];
        $totalPages = (int)$args['total_pages'];

        $percentage = number_format(($currentPage/$totalPages)*100,2);
        if(Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq Migration', 'Current migration task', $currentPage
                .' of ' .$totalPages .' pages of products processed, ' .$percentage .'% Complete');
        }
    }

    public function migrateAttributeValues($processThese, $attributeCode, $toAttributeCode, $storeId, $attributeToType)
    {

        $numberProcessed = 0;
        if($storeId == '') {
            $storeId = 0;
        }
        if(!empty($processThese)) {
            //suspend indexing for the time being
          //  $this->unSetIndex();
            foreach($processThese as $attributeCode => $valuesToMigrate) {
                foreach($valuesToMigrate as $oldValue => $newValue) {
                    if($newValue != '') {
                        $result = $this->setAttributeValue($attributeCode, $oldValue, $toAttributeCode, $newValue, $storeId, $attributeToType);
                        if($result < 0) {
                            $result = Mage::helper('shipperhq_migration')->__(
                                'There was an error saving new attribute values');
                        }
                        else {
                            $numberProcessed+= $result;
                        }
                    }
                }

            }
            //reinstate indexing
          //  $this->setIndex();
        }
        return $numberProcessed;

    }

    /**
     * Update array of products' attribute with new value
     * @param $productArray - array of product IDs
     * @param $attribute_code
     * @param $value
     * @return int
     */
    public function setAttributeValue($from_attribute_code, $oldValue, $to_attribute_code, $value, $storeId, $attributeToType)
    {
        $productCollection = Mage::getModel('catalog/product')->setStoreId($storeId)->getCollection();
        if(!is_null($storeId) && $storeId != '') {
            $productCollection->addStoreFilter((int)$storeId);
        }

        $productCollection->addAttributeToSelect($from_attribute_code);
        $productCollection->addFieldToFilter(array(
            array('attribute'=>$from_attribute_code,'finset'=>$oldValue),
        ));
        $productCollection->setPageSize(self::BATCH_SIZE);
        if($productCollection) {
            try {
                if(Mage::helper('shipperhq_shipper')->isDebug()) {
                    Mage::helper('wsalogger/log')->postDebug('Shipperhq Migration', 'Migration about to begin for',
                        ' products with ' .$from_attribute_code .' attribute value of ' .$oldValue);
                }
                $args = array('values' => array($to_attribute_code => $value),
                        'store_id' => $storeId, 'attribute_to_type' => $attributeToType);
                $batcher = Mage::getSingleton('shipperhq_migration/resource_iterator_batched');
                $result = $batcher->walk(
                    $productCollection,
                    array($this, 'setAttributeValueCallback'),
                    $args,
                    array($this, 'setAttributeValueBatchCallback')
                );

            }
            catch(Exception $e){
                Mage::logException("ShipperHQ Migration Exception");
                Mage::logException($e);
                return -1;
            }
        }
        else {
            if(Mage::helper('shipperhq_shipper')->isDebug()) {
                Mage::helper('wsalogger/log')->postDebug('Shipperhq Migration', 'No migration required',
                     'No products with ' .$from_attribute_code .' attribute value of ' .$oldValue);
            }
        }
        return $result;

    }

    /**
     * Callback function to set attribute value
     * @param $args
     */
    public function setAttributeValueCallback($args)
    {
        $product = $args[0];
        $data = $args[1];
        $productArray = array($product->getId());
        $isMultiSelect = $data['attribute_to_type'] == 'multiselect';
        if($isMultiSelect) {
            Mage::getSingleton('shipperhq_migration/product_action')
                ->updateAttributes($productArray, $data['values'], $data['store_id']);
        }
        else {
              Mage::getResourceSingleton('catalog/product_action')
                  ->updateAttributes($productArray, $data['values'], $data['store_id']);
        }

    }

    public function setAttributeValueBatchCallback($args)
    {
        $currentPage = (int)$args['current_page'];
        $totalPages = (int)$args['total_pages'];

        $percentage = number_format(($currentPage/$totalPages)*100,2);
        if(Mage::helper('shipperhq_shipper')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq Migration', 'Current migration task', $currentPage
                .' of ' .$totalPages .' pages of products processed, ' .$percentage .'% Complete');
        }
    }

    /**
     * Retrieve option id for given attribute value
     * @param $attribute
     * @param $value
     * @return mixed
     */
    protected function _getOptionId($attribute, $value)
    {
        //get the source
        $source = $attribute->getSource();

        //get the id
        $id = $source->getOptionId($value);
        return $id;
    }

    private function unSetIndex()
    {
        $processes = Mage::getSingleton('index/indexer')->getProcessesCollection();
        $processes->walk('setMode', array(Mage_Index_Model_Process::MODE_MANUAL));
        $processes->walk('save');

        return true;
    }

    private function setIndex()
    {
        $processes = Mage::getSingleton('index/indexer')->getProcessesCollection();
        $processes->walk('reindexAll');
        $processes->walk('setMode', array(Mage_Index_Model_Process::MODE_REAL_TIME));
        $processes->walk('save');

        return true;
    }

}