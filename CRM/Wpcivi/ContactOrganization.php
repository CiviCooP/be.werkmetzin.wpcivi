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
  private $_organizationId = NULL;
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
      $this->_employeeRelationshipTypeId = civicrm_api3('RelationshipType', 'Getvalue', array('name_a_b' => 'Employee of', 'return' => 'id'));
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
    $result['prefix_id'] = CRM_Wpcivi_Utils::constructPrefixId($this->_activityParams['prefix']);
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
    $result['target_contact_id'] = array($this->_individualId, $this->_organizationId);
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
    $individual = new CRM_Wpcivi_Contact();
    $jobTitle = NULL;
    if (isset($this->_apiParams['functie'])) {
      $jobTitle = trim($this->_apiParams['functie']);
    }
    $found = $individual->count($this->_individualParams);
    switch ($found) {
      case 0:
        $this->_individualParams['source'] = 'Contactvraag Bedrijven';
        if (!empty($jobTitle)) {
          $this->_individualParams['job_title'] = $jobTitle;
        }
        $created = $individual->create($this->_individualParams);
        $result = $created['values'][$created['id']];
        break;
      case 1:
        // retrieve individual
        $result = $individual->getSingleContact($this->_individualParams);
        // update job title if passed
        if (!empty($jobTitle)) {
          $individual->create(array('id' => $result['id'], 'job_title' => $jobTitle));
          $result['job_title'] = $jobTitle;
        }
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
    // add phones if not exists yet (after org because phone has to be added to org too!)
    $this->processPhone();
  }

  /**
   * Method to create organization if it does not exist yet, and set relation between individual and organization
   */
  private function processOrganization() {
    if (isset($this->_apiParams['organisaties']) && !empty(trim($this->_apiParams['organisaties']))) {
      $organizationParams = array(
        'organization_name' => trim($this->_apiParams['organisaties']),
        'contact_type' => 'Organization'
      );
      $organization = new CRM_Wpcivi_Contact();
      if ($organization->count($organizationParams) == 0) {
        $organizationParams['source'] = 'Contactvraag Bedrijven';
        $created = $organization->create($organizationParams);
        $this->_organizationId = $created['id'];
      } else {
        $found = $organization->getSingleContact($organizationParams);
        $this->_organizationId = $found['id'];
      }
      if (!empty($this->_employeeRelationshipTypeId)) {
        $relationship = new CRM_Wpcivi_Relationship();
        $relationshipParams = array(
          'contact_id_a' => $this->_individualId,
          'contact_id_b' => $this->_organizationId,
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
   * phone needs to be added to both individual and organization
   */
  private function processPhone() {
    if (isset($this->_apiParams['telefoonnummer']) && !empty($this->_apiParams['telefoonnummer'])) {
      $contactIds = array($this->_individualId, $this->_organizationId);
      foreach ($contactIds as $contactId) {
      $phoneParams = array();
      $phoneParams['location_type_id'] = "Werk";
      $phoneParams['phone_type'] = "Phone";
      $phoneParams['phone'] = $this->_apiParams['telefoonnummer'];
      $phoneParams['contact_id'] = $contactId;
      $phone = new CRM_Wpcivi_Phone();
      if ($phone->count($phoneParams) == 0) {
        $primaryParams = array('contact_id' => $contactId, 'is_primary' => 1);
        if ($phone->count($primaryParams) == 0) {
          $phoneParams['is_primary'] = 1;
        } else {
          $phoneParams['is_primary'] = 0;
        }
        $phone->create($phoneParams);
        }
        unset($phone);
      }
    }
  }

  /**
   * Method to process the email address, only set is_primary = 1 if no primary email address for contact
   */
  private function processEmail() {
    $emailParams = array();
    if (isset($this->_apiParams['email']) && !empty($this->_apiParams['email'])) {
      $emailParams['location_type_id'] = "Werk";
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
    $created = $activity->create($this->_activityParams);
    // now add custom data
    $customData = $this->constructActivityCustomData($created['id']);
    if (!empty($customData)) {
      CRM_Wpcivi_Utils::addCustomData($customData);
    }
  }

  /**
   * Method to add activity custom data
   * @param $activityId
   * @return array
   */
  private function constructActivityCustomData($activityId) {
    $customData = array();
    if (!empty($activityId)) {
      $customData['entity_id'] = $activityId;
      $customGroup = new CRM_Wpcivi_CustomGroup();
      $customData['table_name'] = $customGroup->getTableNameWithName('contact_organization');
      $customData['query_action'] = "insert";
      $customData['custom_fields'] = $this->constructActivityCustomFields();
    }
    return $customData;
  }

  /**
   * Method to construct params for custom fields activity
   * @return array
   */
  private function constructActivityCustomFields() {
    $customFields['message_organization'] = array('value' => $this->_apiParams['bericht'], 'type' => 'String');
    return $customFields;
  }
}
