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

class Shipperhq_Migration_Adminhtml_Shqmigration_AjaxController extends Mage_Adminhtml_Controller_Action
{
    protected static $debug = false;

    public function migrateSelectAction()
    {
        if ($this->expireAjax()) {
            return;
        }

        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $starttime = $mtime;

        self::$debug = Mage::helper('shipperhq_shipper')->isDebug();
        $processThese = array();
        if ($this->getRequest()->isGet()) {
            $params = $this->getRequest()->getParams();
            $attributeCode = $this->getRequest()->getParam('attribute_code');
            $toAttributeCode = $this->getRequest()->getParam('to_attribute_code');
            $storeId = (int)$this->getRequest()->getParam('store');
            $attributeToType = $this->getRequest()->getParam('attribute_to_type');
            foreach($params as $key => $value)
            {
                if(strstr($key, $attributeCode)) {
                    $processThese[$attributeCode][str_replace($attributeCode.'_','', $key)] = $value;
                }
            }
        }
        $resultSet = array('attribute_code' => $attributeCode);
        $migrator = Mage::getModel('shipperhq_migration/migrate');
        $resultSet['number_updated']= $migrator->migrateAttributeValues($processThese, $attributeCode, $toAttributeCode, $storeId, $attributeToType);

        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $totaltime = ($endtime - $starttime);

        if(self::$debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq Migration', 'Migrated values of ' .$attributeCode
                .' attribute to ' .$toAttributeCode .' attribute, ' .$resultSet['number_updated'] .' product(s) have been updated'
                ,"This process completed in ".$totaltime." seconds");
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($resultSet));
    }

    public function migrateDefaultAction()
    {
        if ($this->expireAjax()) {
            return;
        }

        self::$debug = Mage::helper('shipperhq_shipper')->isDebug();
        $numberProcessed = 0;
        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $starttime = $mtime;
        if ($this->getRequest()->isGet()) {
            $attributeCode = $this->getRequest()->getParam('attribute_code');
            $storeId = (int)$this->getRequest()->getParam('store');
            $toAttributeCode = $this->getRequest()->getParam('to_attribute_code');
            $resultSet = array('attribute_code' => $attributeCode);
            $migrator = Mage::getSingleton('shipperhq_migration/migrate');
            $result = $migrator->copyAttributeValues($attributeCode, $toAttributeCode, $storeId);
            if($result < 0) {
                $resultSet['error'] = Mage::helper('shipperhq_migration')->__('There was an error saving new attribute values');
            }
            else {
                $numberProcessed+= $result;
            }
        }
        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $totaltime = ($endtime - $starttime);
        if(self::$debug) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq Migration', 'Migrating values of ' .$attributeCode
                .' attribute to ' .$toAttributeCode .' attribute, '
                ,$numberProcessed .' product(s) have been updated ' ."This process completed in ".$totaltime." seconds");
        }
        $resultSet['number_updated']=$numberProcessed;
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($resultSet));
    }

    protected function expireAjax()
    {
        $action = $this->getRequest()->getActionName();

        if (!in_array($action, array('migrateSelect', 'migrateDefault'))) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        return false;
    }

    protected function _ajaxRedirectResponse()
    {
        $this->getResponse()
            ->setHeader('HTTP/1.1', '403 Session Expired')
            ->setHeader('Login-Required', 'true')
            ->sendResponse();
        return $this;
    }

}
