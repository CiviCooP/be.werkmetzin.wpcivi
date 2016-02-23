<?php

/**
 * Class processing email stuff for this extension
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 23 Feb 2016
 * @license AGPL-3.0
 */
class CRM_Wpcivi_Email {
  /**
   * CRM_Wpcivi_Email constructor.
   */
  public function __construct() {
  }

  /**
   * Method to create email
   *
   * @param $params
   * @return array
   * @throws Exception when error from API
   */
  public function create($params) {
    try {
      return civicrm_api3('Email', 'Create', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception(ts("Could not create email in CRM_Wpcivi_Email, error from API Email Create: ")
        .$ex->getMessage());
    }
  }
}