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
class Shipperhq_Pbint_Model_Creditmemo_Duty extends Mage_Sales_Model_Order_Creditmemo_Total_Tax
{
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {

        $creditmemo->setShqPbDutyAmount(0);
        $creditmemo->setBaseShqPbDutyAmount(0);

        $prevCreditmemoDutyAmount = 0;
        $prevBaseCreditmemoDutyAmount = 0;

        foreach ($creditmemo->getOrder()->getCreditmemosCollection() as $prevCreditmemo) {
            if ($prevCreditmemo->getShqPbDutyAmount() && ($prevCreditmemo->getState() != Mage_Sales_Model_Order_Creditmemo::STATE_CANCELED)) {
                $prevCreditmemoDutyAmount += $prevCreditmemo->getShqPbDutyAmount();
                $prevBaseCreditmemoDutyAmount += $prevCreditmemo->getBaseShqPbDutyAmount();
            }
        }

        $allowedAmount = $creditmemo->getOrder()->getShqPbDutyAmount() - $prevCreditmemoDutyAmount;
        $baseAllowedAmount = $creditmemo->getOrder()->getBaseShqPbDutyAmount() - $prevBaseCreditmemoDutyAmount;

        $creditmemoIsLast = true;
        foreach ($creditmemo->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            if ($orderItem->isDummy()) {
                continue;
            }

            if (!$item->isLast()) {
                $creditmemoIsLast = false;
                break;
            }
        }

        if ($creditmemoIsLast) {
            $creditmemo->setShqPbDutyAmount($allowedAmount);
            $creditmemo->setBaseShqPbDutyAmount($baseAllowedAmount);

            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $allowedAmount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseAllowedAmount);
        }

        return $this;
    }
}
