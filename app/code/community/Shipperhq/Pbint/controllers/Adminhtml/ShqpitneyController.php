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
class Shipperhq_Pbint_Adminhtml_ShqpitneyController extends Mage_Adminhtml_Controller_Action
{

    public function uploadcatalogAction()
    {
        $pbExport = Mage::getModel('shipperhq_pbint/catalog_cron');
        $result = $pbExport->uploadCatalog();
        $pbExport->processStatusNotifications();
        $session = Mage::getSingleton('Mage_Adminhtml_Model_Session');
        $session->getMessages(true);
        $success = 1;

        if ($result === true) {
            $message = Mage::helper('shipperhq_pbint')->__('Catalog synch was a success');
            $session->addSuccess($message);
        }
        else {
            $message = Mage::helper('shipperhq_pbint')->__(
                'Catalog synch with Pitney Bowes did not complete. ');
            if(is_string($result)) {
                $message .= $result;
            }
            else {
                $message .=  Mage::helper('shipperhq_pbint')->__('Please review your log files for error details');
            }
            $session->addError($message);
            $success = 0;
        }

        $this->_initLayoutMessages('adminhtml/session');
        $session_messages = $this->getLayout()->getMessagesBlock()->getGroupedHtml();

        $result = array('result' => $success, 'message' => $message, 'session_messages' => $session_messages);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}