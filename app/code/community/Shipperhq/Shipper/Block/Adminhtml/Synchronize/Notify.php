<?php
/**
 * Magento
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
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Shipperhq_Shipper_Block_Adminhtml_Synchronize_Notify extends Mage_Adminhtml_Block_Template
{
    // Cache kay for saving verification result
    const SYNCH_STATUS_RESULT_CACHE_KEY = 'shipperhq_shipper_synch_status';

    /**
     * File path for verification
     * @var string
     */
    private $_filePath = 'app/etc/local.xml';

    /**
     * Time out for HTTP verification request
     * @var int
     */
    private $_verificationTimeOut  = 2;


    public function getSynchUrl()
    {
        return $this->getUrl('adminhtml/shqsynchronize');
    }
    /**
     * Check verification result and return true if system must to show notification message
     *
     * @return bool
     */
    protected function _canShowNotification()
    {
        if(Mage::getSingleton('adminhtml/session')->getAlreadySynched()) {
            return false;
        }

        if ($this->_isSynchRequired()) {
            return true;
        }

        Mage::getSingleton('adminhtml/session')->setAlreadySynched(true);
        return false;
    }

    /**
     * Check synchronization status
     *
     * @return bool
     */
    protected function _isSynchRequired()
    {
        $synchStatus = Mage::getModel('shipperhq_shipper/synchronize')->checkSynchStatus();
        if($synchStatus != false) {
            if($synchStatus != "1") {
                return true;
            }
        }
        return false;
    }

    /**
     * Prepare html output
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_canShowNotification()) {
            return '';
        }
        return parent::_toHtml();
    }

}
