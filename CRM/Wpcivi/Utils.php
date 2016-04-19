<?php
/**
 * Class with extension specific util functions
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 15 Feb 2016
 * @license AGPL-3.0
 */

class CRM_Wpcivi_Utils {

  /**
   * Public function to generate label from name
   *
   * @param $name
   * @return string
   * @access public
   * @static
   */
  public static function buildLabelFromName($name) {
    $nameParts = explode('_', strtolower($name));
    foreach ($nameParts as $key => $value) {
      $nameParts[$key] = ucfirst($value);
    }
    return implode(' ', $nameParts);
  }

  /**
   * Generic method to add custom data using CRM_Core_DAO::executeQuery
   *
   * @param array $params
   * @throws Exception when unable to execute query
   * @access public
   * @static
   */
  public static function addCustomData($params) {
    $queryData = new CRM_Wpcivi_CustomDataQuery($params);
    $query = $queryData->getQuery();
    $queryParams = $queryData->getQueryParams();
    if (!empty($query)) {
      try {
        CRM_Core_DAO::executeQuery($query, $queryParams);
      } catch (Exception $ex) {
        throw new Exception(ts('Unable to add custom data in CRM_Wpcivi_Utils::addCustomData, error message :')
          . $ex->getMessage());
      }
    }
  }

  /**
   * Method to retrieve the group id with group name
   * 
   * @param $groupName
   * @return array|bool
   * @static
   */
  public static function getGroupIdWithName($groupName) {
    try {
      return civicrm_api3('Group', 'Getvalue', array('name' => (string) $groupName, 'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
  }

  /**
   * Method to set the gender based on prefix
   *
   * @param string $prefix
   * @return int
   * @static
   */
  public static function constructGenderId($prefix) {
    $prefix = strtolower($prefix);
    switch ($prefix) {
      case "mevrouw":
        return 1;
        break;
      case "mijnheer":
        return 2;
        break;
      default:
        return 3;
        break;
    }
  }

  /**
   * Method to set the prefix id
   * 
   * @param $prefix
   * @return string
   */
  public static function constructPrefixId($prefix) {
    $result = NULL;
    $prefixToBeChecked = strtolower($prefix);
    try {
      $optionValues = civicrm_api3('OptionValue', 'Get',
        array('option_group_id' => 'individual_prefix', 'is_active' => 1));
      foreach ($optionValues['values'] as $optionValue) {
        $foundPrefix = strtolower($optionValue['label']);
        if ($prefixToBeChecked == $foundPrefix) {
          $result = $optionValue['value'];
        }
      }
    } catch (CiviCRM_API3_Exception $ex) {}
    return $result;
  }

}