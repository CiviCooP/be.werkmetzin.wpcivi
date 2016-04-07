<?php

/**
 * Class to handle API call form EzineIndividual
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 7 Apr 2016
 * @license AGPL-3.0
 */

class CRM_Wpcivi_EzineIndividual extends CRM_Wpcivi_ApiHandler {

  private $_individualParams = array();
  private $_individualId = NULL;
  private $_ezineGroupId = NULL;

  /**
   * Method to process the params from the api into contact
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
      $this->_ezineGroupId = civicrm_api3('Group', 'Getvalue', array('name' => 'ezine_individuals', 'return' => 'id'));
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
        $this->_individualParams['source'] = 'E-Zine Individuen';
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
    // add contact to group ezine individuals
    civicrm_api3('GroupContact', 'Create', array('group_id' => $this->_ezineGroupId, 'contact_id' => $this->_individualId));
  }

  /**
   * Method to process the email address, only set is_primary = 1 if no primary email address for contact
   */
  private function processEmail() {
    $emailParams = array();
    if (isset($this->_apiParams['Email']) && !empty($this->_apiParams['Email'])) {
      $emailParams['location_type'] = "Thuis";
      $emailParams['email'] = $this->_apiParams['Email'];
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
