<?php

/**
 * Temp API for scheduled job to fix the employer_id in contacts that have a relationship but no employer
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 19 Sep 2016
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_employer_Fix($params) {
  $returnValues = array();
  // get relationship type for employee/employer
  try {
    $relationshipTypeId = civicrm_api3('RelationshipType', 'getvalue', array(
      'name_a_b' => 'Employee of',
      'return' => 'id'
    ));
    $query = 'SELECT contact_id_a AS employee_id, contact_id_b AS employer_id FROM civicrm_relationship WHERE relationship_type_id = %1';
    $daoRel = CRM_Core_DAO::executeQuery($query, array(
      1 => array($relationshipTypeId, 'Integer')
    ));
    while ($daoRel->fetch()) {
      $contactUpdate = 'UPDATE civicrm_contact SET employer_id = %1 WHERE id = %2';
      $contactParams = array(
        1 => array($daoRel->employer_id, 'Integer'),
        2 => array($daoRel->employee_id, 'Integer'));
      CRM_Core_DAO::executeQuery($contactUpdate, $contactParams);
    }
    return civicrm_api3_create_success($returnValues, $params, 'Employer', 'fix');
  } catch (CiviCRM_API3_Exception $ex) {
    throw new API_Exception('Could not find relationship type with name_a_b Employee of, error executing job', 1001);
  }
}

