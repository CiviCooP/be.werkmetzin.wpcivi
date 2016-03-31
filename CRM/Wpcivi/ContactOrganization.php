<?php

/**
 * Class to handle API call form ContactOrganization
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 31 Mar 2016
 * @license AGPL-3.0
 */

class CRM_Wpcivi_ContactOrganization extends CRM_Wpcivi_ApiHandler {

  private $_individualParams = array();
  private $_activityParams = array();
  private $_individualId = NULL;
  private $_activityType = array();
  private $_employeeRelationshipTypeId = NULL;

  /**
   * Method to process the params from the api into contact and activity
   */
  public function processParams() {
    $this->initialize();
    $this->_individualParams = $this->constructIndividualParams();
    if (!empty($this->_individualParams)) {
      $this->processIndividual();
    }
    $this->_activityParams = $this->constructActivityParams();
    if (!empty($this->_activityParams)) {
      $this->processActivity();
    }
  }

  /**
   * Method to set the basic settings for this type of wordpress form
   */
  private function initialize() {
    $activityType = new CRM_Wpcivi_ActivityType();
    $this->_activityType = $activityType->getWithNameAndOptionGroupId('contact_organization',
      $activityType->getOptionGroupId());
    try {
      $this->_employeeRelationshipTypeId = civicrm_api3('RelationshipType', 'Getsingle', array('name_a_b' => 'Employee of'));
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
    $mandatoryKeys = array('Voornaam', 'Achternaam');
    foreach ($mandatoryKeys as $mandatoryKey) {
      if (!array_key_exists($mandatoryKey, $this->_apiParams)) {
        throw new Exception(ts('Mandatory param '.$mandatoryKey.' not found in parameters list passed into ').__CLASS__);
      }
    }
    $result['contact_type'] = "Individual";
    $result['first_name'] = $this->_apiParams['Voornaam'];
    $result['last_name'] = $this->_apiParams['Achternaam'];
    return $result;
  }

  /**
   * Method to construct activity params
   *
   * @return mixed
   */
  private function constructActivityParams() {
    $result = array();
    $result['activity_type_id'] = $this->_activityType['value'];
    $result['subject'] = "Formulier Contactvraag Bedrijven";
    $result['activity_date_time'] = date('Ymd H:i:s');
    $result['location'] = "Wordpress form";
    $result['is_current_revision'] = 1;
    $result['source_contact_id'] = 1;
    $result['target_contact_id'] = $this->_individualId;
    $result['status_id'] = 1; //scheduled
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
    $individualParams = $this->constructIndividualParams();
    $individual = new CRM_Wpcivi_Contact();
    $jobTitle = NULL;
    if (isset($this->_apiParams['Functie'])) {
      $jobTitle = trim($this->_apiParams['Functie']);
    }
    $found = $individual->count($individualParams);
    switch ($found) {
      case 0:
        $individualParams['source'] = 'Contactvraag Bedrijven';
        if (!empty($jobTitle)) {
          $individualParams['job_title'] = $jobTitle;
        }
        $created = $individual->create($individualParams);
        $result = $created['values'];
        break;
      case 1:
        // retrieve individual
        $found = $individual->getSingleContact($individualParams);
        // update job title if passed
        if (!empty($jobTitle)) {
          $individual->create(array('id' => $found['id'], 'job_tile' => $jobTitle));
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
    // add phone if not exists yet
    $this->processPhone();
    // add organization + relation between individual and organization if not exists yet
    $this->processOrganization();
  }

  /**
   * Method to create organization if it does not exist yet, and set relation between individual and organization
   */
  private function processOrganization() {
    if (isset($this->_apiParams['Organisatie']) && !empty(trim($this->_apiParams['Organisatie']))) {
      $organizationParams = array(
        'organization_name' => trim($this->_apiParams['Organisatie']),
        'contact_type' => 'Organization'
        );
      $organization = new CRM_Wpcivi_Contact();
      if ($organization->count($organizationParams) == 0) {
        $created = $organization->create($organizationParams);
        $organizationId = $created['values']['id'];
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
   * Method to process the phone, only set is_primary = 1 if no primary phone for contact
   */
  private function processPhone() {
    $phoneParams = array();
    if (isset($this->_apiParams['Telefoonnummer']) && !empty($this->_apiParams['Telefoonnummer'])) {
      $phoneParams['location_type'] = "Werk";
      $phoneParams['phone_type'] = "Phone";
      $phoneParams['phone'] = $this->_apiParams['Telefoonnummer'];
      $phoneParams['contact_id'] = $this->_individualId;
    }
    $phone = new CRM_Wpcivi_Phone();
    if ($phone->count($phoneParams) == 0) {
      $primaryParams = array('contact_id' => $this->_individualId, 'is_primary' => 1);
      if ($phone->count($primaryParams) == 0) {
        $phoneParams['is_primary'] = 1;
      } else {
        $phoneParams['is_primary'] = 0;
      }
      $phone->create($phoneParams);
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

  /**
   * Method to create activity
   */
  private function processActivity() {
    $activity = new CRM_Wpcivi_Activity();
    $activity->create($this->_activityParams);
  }
}
