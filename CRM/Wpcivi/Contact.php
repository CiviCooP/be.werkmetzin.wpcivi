<?php

/**
 * Class processing contact stuff for this extension
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 17 Feb 2016
 * @license AGPL-3.0
 */
class CRM_Wpcivi_Contact {
  /**
   * CRM_Wpcivi_Contact constructor.
   */
  public function __construct() {
  }

  /**
   * Method to create or update contact
   *
   * @param $params
   * @return array
   * @throws Exception when error from API
   */
  public function create($params) {
    try {
      return civicrm_api3('Contact', 'Create', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception(ts("Could not create contact in CRM_Wpcivi_Contact, error from API Contact Create: ")
        .$ex->getMessage());
    }
  }

  /**
   * Method to count the number of contacts found with the params
   *
   * @param array $params
   * @return mixed
   */
  public function count($params) {
    return civicrm_api3('Contact', 'Getcount', $params);
  }

  /**
   * Method to get contact id
   *
   * @param array $params
   * @return array
   * @throws Exception when error in API
   */
  public function getContactId($params) {
    $params['return'] = 'id';
    try {
      return civicrm_api3('Contact', 'Getvalue', array($params));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception(ts('Could not find id for single contact in CRM_Wpcivi_Contact, error from API Contact Getvalue: '
        .$ex->getMessage()));
    }
  }

  /**
   * Method to get single contact with params
   *
   * @param array $params
   * @return array
   * @throws Exception when error in API
   */
  public function getSingleContact($params) {
    try {
      return civicrm_api3('Contact', 'Getsingle', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception(ts('Could not find single contact in CRM_Wpcivi_Contact with params '.implode("; ", $params)
        .', error from API Contact Getvalue: '.$ex->getMessage()));
    }
  }
}