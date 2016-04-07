<?php

/**
 * Class to handle API call form ContactIndividual
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 7 Apr 2016
 * @license AGPL-3.0
 */

class CRM_Wpcivi_ContactIndividual extends CRM_Wpcivi_ApiHandler {

  private $_individualParams = array();
  private $_activityParams = array();
  private $_individualId = NULL;
  private $_activityType = array();

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
    $this->_activityType = $activityType->getWithNameAndOptionGroupId('contact_individual',
      $activityType->getOptionGroupId());
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
    $result['subject'] = "Formulier Contactvraag Individuen";
    $result['activity_date_time'] = date('Ymd H:i:s');
    $result['location'] = "Wordpress form";
    $result['is_current_revision'] = 1;
    $result['source_contact_id'] = 1;
    $result['target_contact_id'] = array($this->_individualId);
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
    $individual = new CRM_Wpcivi_Contact();
    $found = $individual->count($this->_individualParams);
    switch ($found) {
      case 0:
        $this->_individualParams['source'] = 'Contactvraag Individuen';
        $created = $individual->create($this->_individualParams);
        $result = $created['values'][$created['id']];
        break;
      case 1:
        // retrieve individual
        $result = $individual->getSingleContact($this->_individualParams);
        break;
      default:
        throw new Exception('Found more than one individuals in '.__METHOD__.", 
          could not process form from website of type ".$this->_apiParams['form_type']);
        break;
    }
    $this->_individualId = $result['id'];
    // add email if not exists yet
    $this->processEmail();
  }

  /**
   * Method to process the email address, only set is_primary = 1 if no primary email address for contact
   */
  private function processEmail() {
    $emailParams = array();
    if (isset($this->_apiParams['email']) && !empty($this->_apiParams['email'])) {
      $emailParams['location_type'] = "Thuis";
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
      $customData['table_name'] = $customGroup->getTableNameWithName('contact_individual');
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
    $customFields['message_individual'] = array('value' => $this->_apiParams['bericht'], 'type' => 'String');
    return $customFields;
  }
}
