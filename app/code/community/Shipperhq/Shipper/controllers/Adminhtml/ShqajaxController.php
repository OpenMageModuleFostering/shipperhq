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

class Shipperhq_Shipper_Adminhtml_ShqajaxController extends Mage_Adminhtml_Controller_Action
{


    public function refreshcarriersAction()
    {
        $carrier = Mage::getModel('shipperhq_shipper/carrier_shipper');
        $refreshResult = $carrier->refreshCarriers();
        $success = 1;
        $session = Mage::getSingleton('Mage_Adminhtml_Model_Session');
        $session->getMessages(true);
        if (array_key_exists('error', $refreshResult)) {
            $message = $refreshResult['error'];
            $session->addError($message);
            $success = 0;
        }
        elseif(array_key_exists('warning', $refreshResult)) {
            $message = $refreshResult['warning'];
            $session->addSuccess($message);
            $success = 2;
        }
        else {
            $message = Mage::helper('shipperhq_shipper')->__('%s shipping methods have been updated from ShipperHQ', count($refreshResult));
            $session->addSuccess($message);
        }

        $this->_initLayoutMessages('adminhtml/session');
        $session_messages = $this->getLayout()->getMessagesBlock()->getGroupedHtml();

        $result= array('result' =>$success, 'message' =>$message, 'session_messages' => $session_messages);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}