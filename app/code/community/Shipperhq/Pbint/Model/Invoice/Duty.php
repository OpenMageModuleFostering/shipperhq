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
class Shipperhq_Pbint_Model_Invoice_Duty extends Mage_Sales_Model_Order_Invoice_Total_Tax
{
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        if (Mage::helper('shipperhq_pbint')->isDebug()) {
            Mage::helper('wsalogger/log')->postDebug('Shipperhq_Pbint',
                'Shipperhq_Pbint_Model_Invoice_Duty', 'collect function');
        }
        $invoice->setShqPbDutyAmount(0);
        $invoice->setBaseShqPbDutyAmount(0);

        // Get any amount we've invoiced already
        $prevInvoiceDutyAmount = 0;
        $prevBaseInvoiceDutyAmount = 0;
        foreach ($invoice->getOrder()->getInvoiceCollection() as $prevInvoice) {
            if ($prevInvoice->getShqPbDutyAmount() && !$prevInvoice->isCanceled()) {
                $prevInvoiceDutyAmount += $prevInvoice->getShqPbDutyAmount();
                $prevBaseInvoiceDutyAmount += $prevInvoice->getBaseShqPbDutyAmount();
            }
        }

        $dutyAmount = 0;
        $baseDutyAmount = 0;
        $invoiceIsLast = true;
        foreach ($invoice->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            if ($orderItem->isDummy()) {
                continue;
            }

            if (!$item->isLast()) {
                $invoiceIsLast = false;
            }

        }
        if ($invoiceIsLast) {
            $dutyAmount = $invoice->getOrder()->getShqPbDutyAmount() - $prevInvoiceDutyAmount;
            $baseDutyAmount = $invoice->getOrder()->getBaseShqPbDutyAmount() - $prevBaseInvoiceDutyAmount;
        }

        $invoice->setShqPbDutyAmount($dutyAmount);
        $invoice->setBaseShqPbDutyAmount($baseDutyAmount);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $dutyAmount);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseDutyAmount);

        return $this;
    }
}