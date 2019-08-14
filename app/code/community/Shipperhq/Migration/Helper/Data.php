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

/**
 * Shipping data helper
 */
class Shipperhq_Migration_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @var array
     */
    protected $_complexTypes = array('multiselect', 'select');
    /**
     * @var null
     */
    protected $_migrateConfig = null;

    /**
     * Parse xml to attribute migration configuration
     * @return array
     */
    public function getAttributesToMigrate()
    {
       $config = $this->getMigrationConfig();
       $attributeConfig = $config->getNode('modules');
        $migrate = array();
        foreach ($attributeConfig->children() as $attribute) {
            $configArray = $this->verifyAttributes((array)$attribute);

            if($configArray) {
                $migrate[] = $configArray;
            }
        }
        return $migrate;
    }

    /**
     * Retrieve XML configuration from file
     * @return Mage_Core_Model_Config_Base|null
     */
    public function getMigrationConfig()
    {
        if (is_null($this->_migrateConfig)) {
            $migrationConfig = new Varien_Simplexml_Config;
            $migrationConfig->loadString('<?xml version="1.0"?><shipperhq_migrate></shipperhq_migrate>');
            $migrationConfig = Mage::getConfig()->loadModulesConfiguration('migrate.xml');
            $this->_migrateConfig = $migrationConfig;
        }
        return $this->_migrateConfig;

    }

    /**
     * Retrieve all possible attribute values for selectable attribute types
     * @param $attribute_code
     * @param null $store
     * @return array
     */
    public function getAllAttributeValues($attribute_code, $store = null)
    {
        $attribute = $this->getAttribute($attribute_code, $store);
        $source = $attribute->getSource();
        $options = array();
        foreach($source->getAllOptions(false) as $option)
        {
            $options[$option['value']] = $option['label'];
        }

        return $options;
    }

    public function getBooleanValues()
    {
        return array(1 => "YES");
    }

    /**
     * Verify configured attributes are installed
     * @param $configArray
     * @return mixed
     */
    protected function verifyAttributes($configArray)
    {
        $configArray['attributes'] = $configArray['attributes']->asArray();
        $module = $configArray['module'];
        if(!Mage::helper('shipperhq_shipper')->isModuleEnabled($module)) {
            return false;
        }
        foreach($configArray['attributes'] as $attribute_code => $config)
        {
            if(!$this->getAttribute($attribute_code)) {
                 unset($configArray['attributes'][$attribute_code]);
                continue;
            }

        }

        if(empty($configArray['attributes'])) {
            return false;
        }
        return $configArray;
    }

    /**
     * Retrieve attribute catalog product attribute
     * @param $attribute_code
     * @param null $store
     * @return mixed
     */
    protected function getAttribute($attribute_code, $store = null) {
        $attribute = Mage::getResourceModel('catalog/product')
            ->getAttribute($attribute_code);

        if(is_null($store) || $store == '') {
            $store = Mage_Core_Model_App::ADMIN_STORE_ID;
        }
        if($attribute) {
            $attribute->setStoreId($store);

            return $attribute;
        }
        return false;
    }

}
