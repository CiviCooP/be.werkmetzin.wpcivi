<?php

/**
 * Class processing relationship stuff for this extension
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 31 Mar 2016
 * @license AGPL-3.0
 */
class CRM_Wpcivi_Relationship {
  /**
   * CRM_Wpcivi_Relationship constructor.
   */
  public function __construct() {
  }

  /**
   * Method to create relationship
   *
   * @param $params
   * @return array
   * @throws Exception when error from API
   */
  public function create($params) {
    $mandatoryParams = array('contact_id_a', 'contact_id_b', 'relationship_type_id');
    foreach ($mandatoryParams as $mandatoryParam) {
      if (!array_key_exists($mandatoryParam, $params)) {
        throw new Exception(ts('Could not find mandatory parameter '.$mandatoryParam.' in params array in '.__METHOD__));
      }
    }
    try {
      return civicrm_api3('Relationship', 'Create', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception(ts("Could not create relationship in ".__METHOD__.", error from API Relationship Create: ")
        .$ex->getMessage());
    }
  }

  /**
   * Method to count already existing relationships
   * 
   * @param $params
   * @return array
   * @throws Exception when error from API
   */
  public function count($params) {
    try {
      return civicrm_api3('Relationship', 'Getcount', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception(ts("Error when trying to execute API Relationship Getcount in ".__METHOD__.", error from API :".$ex->getMessage()));
    }
  }

  /**
   * Method to get single relationship 
   * 
   * @param $params
   * @return array|bool
   */
  public function getSingle($params) {
    try {
      return civicrm_api3('Relationship', 'Getsingle', $params);
    } catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
  }
}