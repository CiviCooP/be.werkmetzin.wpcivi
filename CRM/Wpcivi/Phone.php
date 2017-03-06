<?php

/**
 * Class processing phone stuff for this extension
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 23 Feb 2016
 * @license AGPL-3.0
 */
class CRM_Wpcivi_Phone {
  /**
   * CRM_Wpcivi_Phone constructor.
   */
  public function __construct() {
  }

  /**
   * Method to create phone
   *
   * @param $params
   * @return array
   * @throws Exception when error from API
   */
  public function create($params) {
    try {
      return civicrm_api3('Phone', 'Create', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception(ts("Could not create phone in CRM_Wpcivi_Phone, error from API Phone Create: ")
        .$ex->getMessage());
    }
  }

  /**
   * Method to count phones already existing
   *
   * @param $params
   * @return array
   * @throws Exception when error from API
   */
  public function count($params) {
    try {
      return civicrm_api3('Phone', 'Getcount', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception(ts("Error when trying to execute API Phone Getcount in ".__METHOD__.", error from API :".$ex->getMessage()));
    }
  }
}