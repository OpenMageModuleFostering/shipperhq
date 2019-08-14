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
 * ST_Core_Model_Resource_Iterator_Batched : https://gist.github.com/kalenjordan/5483065
 * Thanks Kalen
 */

class Shipperhq_Migration_Model_Resource_Iterator_Batched extends Varien_Object
{
    const DEFAULT_BATCH_SIZE = 200;

    /**
    * @param $collection Varien_Data_Collection
    * @param array $callback
    */
    public function walk($collection, array $callbackForIndividual, array $indCallbackArgs = array(), array $callbackAfterBatch)
    {
        $currentPage = 1;
        $pages = $collection->getLastPageNumber();
        $count = 1;
        do {
            $collection->setCurPage($currentPage);
            $collection->load();
            foreach ($collection as $item) {
                $count++;
                $args = array($item, $indCallbackArgs);
                call_user_func($callbackForIndividual, $args);
            }
            $batchArgs = array('current_page' =>$currentPage, 'total_pages' => $pages);
            call_user_func($callbackAfterBatch, $batchArgs);

            $currentPage++;
            $collection->clear();
        } while ($currentPage <= $pages);
        return $count;
    }
}