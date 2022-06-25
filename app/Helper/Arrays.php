<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 18/11/2020
 * Time: 18:06
 */

namespace App\Helper;


class Arrays
{
    public static function stdClassToArray($stdClass)
    {
        return json_decode(json_encode($stdClass), true);
    }

    public static function group($array, $qty)
    {
        $totalPage = ceil(count($array)/$qty);
        $result = array();
        for ($i=1; $i<=$totalPage; $i++) {
            $result[] = array_slice($array, ($i-1)*$qty, $qty);
        }
        return $result;
    }

    /**
     * Build new array by key
     * @param array $array
     * @param string $keyName
     * @return array (value1, value3, ...)
     * @sample
     *  $array(
    array(
     *          keyName1 => value1,
     *          keyName2 => value2,
     *      ),
     *      array(
     *          keyName1 => value3,
     *          keyName2 => ...,
     *      ),
     *  )
     */
    public static function buildArrayByKey($array, $keyName)
    {
        $keyName = trim($keyName);
        $result = array();
        if ($array) {
            foreach ($array as $item) {
                $value = isset($item[$keyName]) ? $item[$keyName] : null ;
                if ($value && !in_array($value, $result) ) {
                    $result[] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * Check if all elements are null
     * @param array $row
     * @return bool
     */
    static function isValidatedRow($row)
    {
        $result = false;
        if ($row) {
            foreach ($row as $value) {
                if (!empty($value) && $value) {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

    public static function buildArrayInKeyAttribute($array, $keyName, $attributeName, callable $callback = null)
    {
        $result = array();
        if ($array) {
            foreach ($array as $item) {
                $key = isset($item[$keyName]) ? $item[$keyName] : null ;
                $attribute = isset($item[$attributeName]) ? $item[$attributeName] : null ;
                if ( !is_null($key) && !is_null($attribute) ) {
                    if ($callback) {
                        $result[$key] = $callback($attribute);
                    } else {
                        $result[$key] = $attribute;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Transform to std class
     * @param $array
     * @return \stdClass
     */
    public static function transformToStdClass($array)
    {
        $result = new \stdClass();
        foreach ($array as $key => $value) {
            $result->{$key} = $value;
        }
        return $result;
    }

    /**
     * Build array by key with condition
     * @param array $array
     * @param string $keyName
     * @param string $keyCondition
     * @param string $condition
     * @return array
     * @example
     *  array (
    array1(
     *          key1 => yes,
     *          key2 => value2,
     *          key3 => ...,
     *      ),
     *      array2(
     *          key1 => no,
     *          key2 => value2,
     *          key3 => ...,
     *      )
     *  )
     *  keyName = key2
     *  keyCondition = key1
     *  condition = yes
     *  => result = array(value2)
     */
    public static function buildArrayByKeyWithCondition($array, $keyName, $keyCondition, $condition)
    {
        $result = array();
        if ($array) {
            foreach ($array as $item) {
                if (isset($item[$keyName]) && isset($item[$keyCondition]) ) {
                    $check = true;
                    if (is_array($condition)) {
                        $check = in_array($item[$keyCondition], $condition);
                    } else {
                        $check = $item[$keyCondition]==$condition;
                    }
                    if ($check) {
                        $value = $item[$keyName];
                        if ($value) {
                            $result[] = $value;
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param string $key
     * @param array $data
     * @param string $lockKey
     * @return array
     */
    public static function  buildArrayGroupBy($key, $data , $lockKey = null) {
        $result = array();
        foreach($data as $val) {
            if(array_key_exists($key, $val)){
                if(!is_null($lockKey)){
                    $result[$val[$key]][] = $val[$lockKey];
                }else{
                    $result[$val[$key]][] = $val;
                }

            }else{
                $result[""][] = $val;
            }
        }
        return $result;
    }

    /**
     * @param $array
     * @return array
     */
    public static function convertSingleArrayToArrayKey($array)
    {
        $result = array();
        if ($array) {
            foreach ($array as $key) {
                $result[$key] = null;
            }
        }
        return $result;
    }

    /**
     * format Assoc Array to array in select box angular js
     * @param array $assocArray
     * @return array
     */
    public static function assocArrayToKeyValueArray($assocArray)
    {
        $result = [];
        foreach ((array) $assocArray as $key => $value) {
            $result[] = [
                'key' => strval($key),
                'value' => $value
            ];
        }
        return $result;
    }

    /**
     * Check if target is overlap by value
     * Sample
     *  input:
     *      $arraySource = [4,6]
     *      $assocTarget = [[3,5], [6,7]]
     *  output:
     *      $conflict = [[3,5]]
     * @param $arraySource
     * @param $assocTarget
     * @return array
     */
    public static function isOverlap($arraySource, $assocTarget) {
        $conflict = [];

        $sourceFrom = intval($arraySource[0]);
        $sourceEnd = intval($arraySource[1]);
        foreach ($assocTarget as $key => $arrayTarget) {
            $targetFrom = intval($arrayTarget[0]);
            $targetEnd = intval($arrayTarget[1]);
            if ($sourceEnd <= $targetFrom || $sourceFrom >= $targetEnd) {
                continue;
            }

            $conflict[$key] = $arrayTarget;
        }

        return $conflict;
    }
}
