<?php

/**
 * Class processing address stuff for this extension
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 23 Feb 2016
 * @license AGPL-3.0
 */
class CRM_Wpcivi_Address {
  /**
   * CRM_Wpcivi_Address constructor.
   */
  public function __construct() {
  }

  /**
   * Method to create address
   *
   * @param $params
   * @return array
   * @throws Exception when error from API
   */
  public function create($params) {
    try {
      return civicrm_api3('Address', 'Create', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception(ts("Could not create address in CRM_Wpcivi_Address, error from API Address Create: ")
        .$ex->getMessage());
    }
  }
}