<?php

class Shipperhq_Frontend_Block_Catalog_Helper
    extends Mage_Core_Block_Template
{

    private static $_debug;

    protected $_storepickup;
    protected $_calendar;
    protected $_rates;
    protected $_accessorial;


    /**
     * Calendar block name
     *
     * @var string
     */
    protected $_calendarBlockType;

    /**
     * Calendar block template
     *
     * @var string
     */
    protected $_calendarBlockTemplate;

    /**
     * Store pickup block name
     *
     * @var string
     */
    protected $_storePickupBlockType;

    /**
     * Store pickup block template
     *
     * @var string
     */
    protected $_storePickupBlockTemplate;

    /**
     * Freight accessorials block name
     *
     * @var string
     */
    protected $_freightAccessorialsBlockType;

    /*
     * Freight accessorials block template
     *
     * @var string
     */
    protected $_freightAccessorialsBlockTemplate;

    /**
     * Sets debug flag
     */
    protected function _construct() {
        self::$_debug = Mage::helper('wsalogger')->isDebug('Shipperhq_Splitrates');

        parent::_construct();

    }

    /**
     * Sets up block properties
     *
     * @param array $options
     * @return $this
     */
    public function init($options)
    {
        if (isset($options['calendar_block'])) {
            $this->setCalendarBlockType($options['calendar_block']);
        }
        if (isset($options['calendar_template'])) {
            $this->setCalendarBlockTemplate($options['calendar_template']);
        }
//        if (isset($options['pickup_block'])) {
//            $this->setStorePickUpBlockType($options['pickup_block']);
//        }
//        if (isset($options['pickup_template'])) {
//            $this->setStorePickUpBlockTemplate($options['pickup_template']);
//        }

        return $this;
    }

    public function isCalendarRate($carrierGroupId, $carrierCode, $carrierType)
    {
        return Mage::helper('shipperhq_calendar')->hasCalendarDetails($carrierGroupId,$carrierCode, $carrierType);
    }

    public function getCalendarHtml($carriergroup, $code)
    {

       $block = $this->_getCalendar()
            ->setCarriergroupId($carriergroup)
            ->setCarrierCode($code)
            ->setName('calendar')
            ->setTemplate($this->getCalendarBlockTemplate())
            ->toHtml();
        return $block;
    }

    public function getStorepickupHtml($carrierCode, $carrierType, $carriergroup = null )
    {
        return $this->_getStorePickup('storepickup_'.$carrierCode)
            ->setCarriergroupId($carriergroup)
            ->setCarrierCode($carrierCode)
            ->setIsAccessPoints(Mage::helper('shipperhq_pickup')->isUpsAccessPointCarrier($carrierType))
            ->setCarrierType($carrierType)
            ->setName('storepickup_'.$carrierCode)
            ->setTemplate($this->getStorePickUpBlockTemplate())
            ->toHtml();
    }

    protected function _getCalendar($name = '')
    {
        if (!$this->_calendar) {
            $this->_calendar = $this->getLayout()->createBlock($this->getCalendarBlockType(), $name);
         //   $this->_calendar->setQuote($this->getQuote());
         //   $this->_calendar->setAddress($this->getAddress());
        }

        return $this->_calendar;
    }

    protected function _getStorePickup($name)
    {
        $this->_storepickup = $this->getLayout()->createBlock($this->getStorePickUpBlockType(), $name);
        $this->_storepickup->setQuote($this->getQuote());
        $this->_storepickup->setAddress($this->getAddress());
        return $this->_storepickup;
    }

    /**
     * Returns calendar block type
     *
     * @return string
     */
    public function getCalendarBlockType()
    {
        return $this->_calendarBlockType;
    }

    /**
     * Sets calendar block type
     *
     * @param string $blockType
     * @return $this
     */
    public function setCalendarBlockType($blockType)
    {
        $this->_calendarBlockType = $blockType;
        return $this;
    }

    /**
     * Returns calendar block type
     *
     * @return string
     */
    public function getCalendarBlockTemplate()
    {
        return $this->_calendarBlockTemplate;
    }

    /**
     * Sets calendar block type
     *
     * @param string $blockType
     * @return $this
     */
    public function setCalendarBlockTemplate($blockType)
    {
        $this->_calendarBlockTemplate = $blockType;
        return $this;
    }

    /**
     * Returns a block type, that should be used for a calendar
     *
     * @return string
     */
    public function getStorePickUpBlockTemplate()
    {
        return $this->_storePickupBlockTemplate;
    }

    /**
     * Sets store pickup block type
     *
     * @param string $template
     * @return $this
     */
    public function setStorePickUpBlockTemplate($template)
    {
        $this->_storePickupBlockTemplate = $template;
        return $this;
    }

    /**
     * Returns a block type, that should be used for a calendar
     *
     * @return string
     */
    public function getStorePickUpBlockType()
    {
        return $this->_storePickupBlockType;
    }

    /**
     * Sets store pickup block type
     *
     * @param $blockType
     * @return $this
     */
    public function setStorePickUpBlockType($blockType)
    {
        $this->_storePickupBlockType = $blockType;
        return $this;
    }
}