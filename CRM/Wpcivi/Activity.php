<?php

/**
 * Class processing activity stuff for this extension
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 17 Feb 2016
 * @license AGPL-3.0
 */
class CRM_Wpcivi_Activity {
  /**
   * CRM_Wpcivi_Activity constructor.
   */
  public function __construct() {
  }

  /**
   * Method to create or update activity
   *
   * @param $params
   * @return array
   * @throws Exception when error from API
   */
  public function create($params) {
    try {
      return civicrm_api3('Activity', 'Create', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception(ts("Could not create activity in CRM_Wpcivi_Activity, error from API Activity Create: ")
        .$ex->getMessage());
    }
  }

  /**
   * Method to get activity id
   *
   * @param $params
   * @return array
   * @throws Exception when error in API
   */
  public function getActivityId($params) {
    $params['return'] = 'id';
    try {
      return civicrm_api3('Activity', 'Getvalue', array($params));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception(ts('Could not find id for single activity in CRM_Wpcivi_Activity, error from API Activity Getvalue: '
        .$ex->getMessage()));
    }
  }
}