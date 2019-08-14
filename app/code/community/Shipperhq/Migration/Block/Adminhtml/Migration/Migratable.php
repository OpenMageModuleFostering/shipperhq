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


class Shipperhq_Migration_Block_Adminhtml_Migration_Migratable extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct($attributes=array())
    {
        parent::__construct($attributes);
        $this->setTemplate('shipperhq/migrate_extension.phtml');

    }

    public function getHelperText()
    {
        $data = $this->getExtension();
        if(is_array($data) && array_key_exists('helper', $data)) {
            return $data['helper'];
        }
    }

    public function getAttributes()
    {
        $data = $this->getExtension();
        $arrayOfData = array();
        if(is_array($data) && array_key_exists('attributes', $data)) {
            $arrayOfData = $data['attributes'];
            if(!is_array($arrayOfData)) {
                $arrayOfData = $arrayOfData->asArray();
            }
        }
        return $arrayOfData;

    }

    public function getTableHtml($attributeConfig, $store)
    {
        $fromAttributeCode = $attributeConfig['from']['code'];
        $toAttributeCode = $attributeConfig['to']['code'];
        $toType = $attributeConfig['to']['type'];
        $html = '<div class="grid"><table id="'. $attributeConfig['from']['code'] .'" class="migration">';

        switch($attributeConfig['type']) {
            case 'select': {
                $html.= '<thead><tr class="headings">
                <th>Products with '.$attributeConfig['from']['title'] .' value of</th>
                <th class="last">Assigned ' .$attributeConfig['to']['title']. ' value of</th>
                </tr>
                </thead><tbody>';
                $html.= $this->getSelectTableHtml($attributeConfig, $store);
                $buttonOnClick = 'migrateSelectClick';
                break;
            }
            case 'textselect': {
                $html.= '<thead><tr class="headings">
                <th>Products with '.$attributeConfig['from']['title'] .' enabled</th>
                <th class="last">Assigned ' .$attributeConfig['to']['title']. ' value of</th>
                </tr>
                </thead><tbody>';
                $html.= $this->getTextSelectTableHtml($attributeConfig, $store);
                $buttonOnClick = 'migrateSelectClick';
                break;
            }
            default: {
                $html.= '<thead><tr class="headings"><th>Values Copied From Here</th>
                <th class="last">Into Here</th>
                </tr>
                </thead><tbody>
                <tr>
                <td class="label">'.$attributeConfig['from']['title'] .'</td>
                <td class="label">' .$attributeConfig['to']['title']. '</td>
                </tr>';
                $buttonOnClick = 'migrateDefaultClick';
            }
        }
        $html .='</tbody></table></div>';
        $button = $this->getButtonHtml('Migrate',  "{$buttonOnClick}('{$fromAttributeCode}', '{$toAttributeCode}', '{$toType}')", '', $fromAttributeCode.'_button');
        $display = '<div><span id="' .$fromAttributeCode .'_output_result" class="no-display"></span></div><br/>';
        $html.= $button .$display;
        return $html;
    }

    public function getSelectTableHtml($attributeConfig, $store = null)
    {
        $fromAttributeCode = $attributeConfig['from']['code'];
        $existingAttributeValues =  Mage::helper('shipperhq_migration')->getAllAttributeValues($fromAttributeCode, $store);
        $newAttributeValues = Mage::helper('shipperhq_migration')->getAllAttributeValues($attributeConfig['to']['code'], $store);
        $html = '';
        if(is_array($existingAttributeValues) && count($existingAttributeValues) > 0) {

            foreach($existingAttributeValues as $value => $name)
            {
                if(count($newAttributeValues) < 1) {
                    $newValuesSelect = $this->getErrorNoValues($attributeConfig['to']['code']);
                }
                else {
                    $newValuesSelect = $this->getSelect($fromAttributeCode.'_'.$value, $newAttributeValues);
                }
                if($value == '') continue;
                $rowHtml = '<tr class="even pointer" title="#">
                    <td class="a-left ">'
                    .$name.'</td>
                    <td class="a-left last">'. $newValuesSelect .'</td></tr>';

                $html.=$rowHtml;
            }


        }
        else {
            $notice = '<span class="notice"><span>'
                .$this->__('No existing attribute values, nothing to migrate.').'</span></span>';

            $html .=  '<tr class="even pointer" title="#">
                    <td class="a-left ">'
            .$notice.'</td>
                    <td class="a-left last"></td></tr>';
        }

        return $html;
    }

    public function getTextSelectTableHtml($attributeConfig, $store)
    {
        $fromAttributeCode = $attributeConfig['from']['code'];
        $existingAttributeValues =  Mage::helper('shipperhq_migration')->getBooleanValues();
        $newAttributeValues = Mage::helper('shipperhq_migration')->getAllAttributeValues($attributeConfig['to']['code'], $store);
        $html = '';
        if(is_array($existingAttributeValues) && count($existingAttributeValues) > 0) {

            foreach($existingAttributeValues as $value => $name)
            {
                if(count($newAttributeValues) < 1) {
                    $newValuesSelect = $this->getErrorNoValues($attributeConfig['to']['code']);
                }
                else {
                    $newValuesSelect = $this->getSelect($fromAttributeCode.'_'.$value, $newAttributeValues);
                }
                if($value == '') continue;
                $rowHtml = '<tr class="even pointer" title="#">
                    <td class="a-left ">'
                    .$name.'</td>
                    <td class="a-left last">'. $newValuesSelect .'</td></tr>';

                $html.=$rowHtml;
            }


        }
        else {
            $notice = '<span class="notice"><span>'
                .$this->__('No existing attribute values, nothing to migrate.').'</span></span>';

            $html .=  '<tr class="even pointer" title="#">
                    <td class="a-left ">'
                .$notice.'</td>
                    <td class="a-left last"></td></tr>';
        }

        return $html;
    }

    public function getSelect($id, $values)
    {
        $values[''] = Mage::helper('shipperhq_migration')->__('Select a value');
        ksort($values);
        $select = $this->getLayout()->createBlock('adminhtml/html_select')
            ->setData(array(
                'id' => $id,
                'class' => 'select select-product-option-type'
            ))
            ->setName($id)
            ->setOptions($values);

        return $select->getHtml();
    }

    public function getErrorNoValues($toAttribute)
    {
        $notice = '<span class="error">'
            .$this->__('No values for '. $toAttribute .' attribute. Please configure in ShipperHQ Dashboard then synchronize')
            .'</span>';
        return $notice;

    }

}