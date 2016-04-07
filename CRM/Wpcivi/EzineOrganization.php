<?php

/**
 * Class to handle API call form EzineOrganization
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 31 Mar 2016
 * @license AGPL-3.0
 */

class CRM_Wpcivi_EzineOrganization extends CRM_Wpcivi_ApiHandler {

  private $_individualParams = array();
  private $_individualId = NULL;
  private $_employeeRelationshipTypeId = NULL;
  private $_ezineGroupId = NULL;

  /**
   * Method to process the params from the api into contact, organization and relationship
   */
  public function processParams() {
    $this->initialize();
    $this->_individualParams = $this->constructIndividualParams();
    if (!empty($this->_individualParams)) {
      $this->processIndividual();
    }
  }

  /**
   * Method to set the basic settings for this type of wordpress form
   */
  private function initialize() {
    try {
      $this->_employeeRelationshipTypeId = civicrm_api3('RelationshipType', 'Getvalue', array('name_a_b' => 'Employee of', 'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {}
    try {
      $this->_ezineGroupId = civicrm_api3('Group', 'Getvalue', array('name' => 'ezine_organizations', 'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {}
  }

  /**
   * Method to construct the params for the contact processing
   *
   * @return mixed
   * @throws Exception
   */
  private function constructIndividualParams() {
    $result = array();
    $mandatoryKeys = array('voornaam', 'achternaam');
    foreach ($mandatoryKeys as $mandatoryKey) {
      if (!array_key_exists($mandatoryKey, $this->_apiParams)) {
        throw new Exception(ts('Mandatory param '.$mandatoryKey.' not found in parameters list passed into ').__CLASS__);
      }
    }
    $result['contact_type'] = "Individual";
    $result['first_name'] = $this->_apiParams['voornaam'];
    $result['last_name'] = $this->_apiParams['achternaam'];
    $result['gender_id'] = CRM_Wpcivi_Utils::constructGenderId($this->_apiParams['prefix']);
    return $result;
  }

  /**
   * Method to process the individual
   *
   * @return array
   * @throws Exception when more than 1 individual found
   */
  private function processIndividual() {
    // if necessary create new organization
    $individual = new CRM_Wpcivi_Contact();
    $jobTitle = NULL;
    if (isset($this->_apiParams['functie'])) {
      $jobTitle = trim($this->_apiParams['functie']);
    }
    $found = $individual->count($this->_individualParams);
    switch ($found) {
      case 0:
        $this->_individualParams['source'] = 'E-Zine Bedrijven';
        if (!empty($jobTitle)) {
          $this->_individualParams['job_title'] = $jobTitle;
        }
        $created = $individual->create($this->_individualParams);
        $result = $created['values'][$created['id']];
        break;
      case 1:
        // retrieve individual
        $found = $individual->getSingleContact($this->_individualParams);
        // update job title if passed
        if (!empty($jobTitle)) {
          $individual->create(array('id' => $found['id'], 'job_title' => $jobTitle));
          $found['job_title'] = $jobTitle;
        }
        $result = $found;
        break;
      default:
        throw new Exception('Found more than one individuals in '.__METHOD__.", 
          could not process form from website of type ".$this->_apiParams['form_type']);
        break;
    }
    $this->_individualId = $result['id'];
    // add email if not exists yet
    $this->processEmail();
    // add organization + relation between individual and organization if not exists yet
    $this->processOrganization();
    // add contact to group ezine organizations
    civicrm_api3('GroupContact', 'Create', array('group_id' => $this->_ezineGroupId, 'contact_id' => $this->_individualId));
  }

  /**
   * Method to create organization if it does not exist yet, and set relation between individual and organization
   */
  private function processOrganization() {
    if (isset($this->_apiParams['organisatie']) && !empty(trim($this->_apiParams['organisatie']))) {
      $organizationParams = array(
        'organization_name' => trim($this->_apiParams['organisatie']),
        'contact_type' => 'Organization'
        );
      $organization = new CRM_Wpcivi_Contact();
      if ($organization->count($organizationParams) == 0) {
        $created = $organization->create($organizationParams);
        $organizationId = $created['id'];
      } else {
        $found = $organization->getSingleContact($organizationParams);
        $organizationId = $found['id'];
      }
      if (!empty($this->_employeeRelationshipTypeId)) {
        $relationship = new CRM_Wpcivi_Relationship();
        $relationshipParams = array(
          'contact_id_a' => $this->_individualId,
          'contact_id_b' => $organizationId,
          'relationship_type_id' => $this->_employeeRelationshipTypeId
        );
        if ($relationship->count($relationshipParams) == 0) {
          $relationshipParams['is_active'] = 1;
          $relationship->create($relationshipParams);
        } else {
          $found = $relationship->getSingle($relationshipParams);
          if ($found && $found['is_active'] == 0) {
            $relationshipParams['id'] = $found['id'];
            $relationshipParams['is_active'] = 1;
            $relationship->create($relationshipParams);
          }
        }
      }
    }
  }

  /**
   * Method to process the email address, only set is_primary = 1 if no primary email address for contact
   */
  private function processEmail() {
    $emailParams = array();
    if (isset($this->_apiParams['email']) && !empty($this->_apiParams['email'])) {
      $emailParams['location_type'] = "Werk";
      $emailParams['email'] = $this->_apiParams['email'];
      $emailParams['contact_id'] = $this->_individualId;
    }
    $email = new CRM_Wpcivi_Email();
    if ($email->count($emailParams) == 0) {
      $primaryParams = array('contact_id' => $this->_individualId, 'is_primary' => 1);
      if ($email->count($primaryParams) == 0) {
        $emailParams['is_primary'] = 1;
      } else {
        $emailParams['is_primary'] = 0;
      }
      $email->create($emailParams);
    }
  }
}
