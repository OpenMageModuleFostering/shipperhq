<?php

/**
 * Mapper for a data arrays tranformation
 */
class Shipperhq_Shipper_Helper_Mapper
    extends Mage_Core_Helper_Abstract
{
    /**
     * Maps data by specified rules
     * 
     * @param array $mapping
     * @param array $source
     * @return array
     */
    public function map($mapping, $source)
    {
        $target = array();
        foreach ($mapping as $targetField => $sourceField) {
            if (is_string($sourceField)) {
                if (strpos($sourceField, '/') !== false) {
                    $fields = explode('/', $sourceField);
                    $value = $source;
                    while ($fields) {
                        $field = array_shift($fields);
                        if (isset($value[$field])) {
                            $value = $value[$field];
                        } else {
                            $value = null;
                            break;
                        }
                    }
                    $target[$targetField] = $value;
                } else {
                    $target[$targetField] = $source[$sourceField];
                }
            } elseif (is_array($sourceField)) {
                list($field, $defaultValue) = $sourceField;
                $target[$targetField] = (isset($source[$field]) ? $source[$field] : $defaultValue);
            } elseif ($sourceField instanceof Closure) {
                $mapping = $sourceField($source);
                $target[$targetField] = $mapping;
            }
        }
        
        return $target;
    }
}