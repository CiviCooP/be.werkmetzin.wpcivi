<?php

/**
 * Class to handle API call form CoachingIndividual
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 10 Feb 2016
 * @license AGPL-3.0
 */

class CRM_Wpcivi_CoachingIndividual extends CRM_Wpcivi_ApiHandler {

  private $_contactParams = array();
  private $_activityParams = array();
  private $_contactId = NULL;
  private $_activityType = array();
  private $_yesValues = array();
  private $_noValues = array();

  /**
   * Method to process the params from the api into contact and activity
   */
  public function processParams() {
    $this->initialize();
    $this->_contactParams = $this->constructContactParams();
    if (!empty($this->_contactParams)) {
      $this->processContact();
    }
    $this->_activityParams = $this->constructActivityParams();
    if (!empty($this->_activityParams)) {
      $this->processActivity();
    }
  }

  /**
   * Method to construct params for email create
   *
   * @return array
   */
  private function constructEmailParams() {
    $emailParams = array();
    if (isset($this->_apiParams['email']) && !empty($this->_apiParams['email'])) {
      $emailParams['location_type'] = "Thuis";
      $emailParams['email'] = $this->_apiParams['email'];
      $emailParams['is_primary'] = 1;
      $emailParams['contact_id'] = $this->_contactId;
    }
    return $emailParams;
  }

  /**
   * Method to construct params for mobile phone create
   *
   * @return array
   */
  private function constructMobileParams() {
    $mobileParams = array();
    if (isset($this->_apiParams['mobile']) && !empty($this->_apiParams['mobile'])) {
      $mobileParams['location_type'] = "Thuis";
      $mobileParams['phone_type'] = "Mobile";
      $mobileParams['phone'] = $this->_apiParams['mobile'];
      $mobileParams['is_primary'] = 0;
      $mobileParams['contact_id'] = $this->_contactId;
    }
    return $mobileParams;
  }

  /**
   * Method to construct params for phone create
   *
   * @return array
   */
  private function constructPhoneParams() {
    $phoneParams = array();
    if (isset($this->_apiParams['phone']) && !empty($this->_apiParams['phone'])) {
      $phoneParams['location_type_id'] = "Thuis";
      $phoneParams['phone_type'] = "Phone";
      $phoneParams['phone'] = $this->_apiParams['phone'];
      $phoneParams['is_primary'] = 1;
      $phoneParams['contact_id'] = $this->_contactId;
    }
    return $phoneParams;
  }
  /**
   * Method to set address params for address create
   *
   * @return array
   */
  private function constructAddressParams() {
    $addressParams = array();
    if (isset($this->_apiParams['street_address']) && !empty($this->_apiParams['street_address'])) {
      $addressParams['street_address'] = trim($this->_apiParams['street_address']);
    }
    if (isset($this->_apiParams['postal_code']) && !empty($this->_apiParams['postal_code'])) {
      $addressParams['postal_code'] = $this->_apiParams['postal_code'];
    }
    if (isset($this->_apiParams['city']) && !empty($this->_apiParams['city'])) {
      $addressParams['city'] = $this->_apiParams['city'];
    }
    if (!empty($addressParams)) {
      $addressParams['contact_id'] = $this->_contactId;
      $addressParams['is_primary'] = 1;
      $addressParams['location_type_id'] = "Thuis";
    }
    return $addressParams;
  }
  /**
   * Method to set the basic settings for this type of wordpress form
   */
  private function initialize() {
    $activityType = new CRM_Wpcivi_ActivityType();
    $this->_activityType = $activityType->getWithNameAndOptionGroupId('form_ind_job_coaching',
      $activityType->getOptionGroupId());
    $this->_yesValues = array('ja', 'Ja', 'J', 'j');
  }

  /**
   * Method to construct the params for the contact processing
   *
   * @return mixed
   * @throws Exception
   */
  private function constructContactParams() {
    $mandatoryKeys = array('first_name', 'last_name', 'email', 'birth_date');
    foreach ($mandatoryKeys as $mandatoryKey) {
      if (!array_key_exists($mandatoryKey, $this->_apiParams)) {
        throw new Exception(ts('Mandatory param '.$mandatoryKey.' not found in parameters list passed into ').__CLASS__);
      }
    }
    $contactParams['contact_type'] = "Individual";
    $contactParams['gender_id'] = $this->constructGenderId();
    $contactParams['first_name'] = $this->_apiParams['first_name'];
    $contactParams['last_name'] = $this->_apiParams['last_name'];
    $contactParams['birth_date'] = $this->_apiParams['birth_date'];
    return $contactParams;
  }

  /**
   * Method to set the gender based on prefix
   *
   * @return int
   */
  private function constructGenderId() {
    switch ($this->_apiParams['prefix']) {
      case "Mevrouw":
        return 1;
      break;
      case "Mijnheer":
        return 2;
      break;
      default:
        return 3;
      break;
    }
  }

  /**
   * Method to construct activity params
   *
   * @return mixed
   */
  private function constructActivityParams() {
    $activityParams['activity_type_id'] = $this->_activityType['value'];
    $activityParams['subject'] = "Formulier Individuele Loopbaancoaching";
    $activityParams['activity_date_time'] = date('Ymd H:i:s');
    $activityParams['location'] = "Wordpress form";
    $activityParams['is_current_revision'] = 1;
    $activityParams['source_contact_id'] = 1;
    $activityParams['target_contact_id'] = $this->_contactId;
    $activityParams['status_id'] = 2; //completed
    return $activityParams;
  }

  /**
   * Method to process the contact
   *
   * @return array
   * @throws Exception when more than 1 contact found
   */
  public function processContact() {
    $contact = new CRM_Wpcivi_Contact();
    $count = $contact->count($this->_contactParams);
    switch ($count) {
      case 0:
        $createdContact = $contact->create($this->_contactParams);
        $this->_contactId = $createdContact['id'];
        $addressParams = $this->constructAddressParams();
        if (!empty($addressParams)) {
          $address = new CRM_Wpcivi_Address();
          $address->create($addressParams);
        }
        $phoneParams = $this->constructPhoneParams();
        if (!empty($phoneParams)) {
          $phone = new CRM_Wpcivi_Phone();
          $phone->create($phoneParams);
        }
        $mobileParams = $this->constructMobileParams();
        if (!empty($mobileParams)) {
          $mobile = new CRM_Wpcivi_Phone();
          $mobile->create($mobileParams);
        }
        $emailParams = $this->constructEmailParams();
        if (!empty($emailParams)) {
          $email = new CRM_Wpcivi_Email();
          $email->create($emailParams);
        }
        return $createdContact;
        break;
      case 1:
        $findParams = array(
          'first_name' => $this->_apiParams['first_name'],
          'last_name' => $this->_apiParams['last_name'],
          'birth_date' => $this->_apiParams['birth_date'],
          'contact_type' => "Individual"
        );
        $foundContact = $contact->getSingleContact($findParams);
        $this->_contactId = $foundContact['id'];
        return $foundContact;
        break;
      default:
        throw new Exception(ts('Found more than 1 contact in CRM_Wpcivi_CoachingIndividual with params ')
          .implode('; ', $this->_contactParams));
        break;
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
      $customData['table_name'] = $customGroup->getTableNameWithName('ind_job_coaching');
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
    $radioColumns = array('previous_job_coaching', 'previous_past');
    $customFields = array();
    // array holding custom field column as key and params key as value
    $possibleCustomFields = array(
      'location_preference_1st' => array('name' => 'location_preference_1st', 'type' => 'String'),
      'location_preference_2nd' => array('name' => 'location_preference_2nd', 'type' => 'String'),
      'location_preference_3rd' => array('name' => 'location_preference_3rd', 'type' => 'String'),
      'highest_certificate' => array('name' => 'highest_certificate', 'type' => 'String'),
      'contact_preference' => array('name' => 'contact_preference', 'type' => 'String'),
      'preference_days' => array('name' => 'preference_days', 'type' => 'String'),
      'employment_status' => array('name' => 'employment_status', 'type' => 'String'),
      'other_employment' => array('name' => 'other_employment', 'type' => 'String'),
      'previous_job_coaching' => array('name' => 'previous_job_coaching', 'type' => 'Integer'),
      'previous_past' => array('name' => 'previous_past', 'type' => 'Integer'),
      'previous_date' => array('name' => 'previous_date', 'type' => 'Date'),
      'found_us_how' => array('name' => 'found_us_how', 'type' => 'String'),
      'message' => array('name' => 'remarks', 'type' => 'String')
    );
    foreach ($possibleCustomFields as $column => $possibleParams) {
      if (in_array($column, $radioColumns)) {
        if (in_array($this->_apiParams[$possibleParams['name']], $this->_yesValues)) {
          $this->_apiParams[$possibleParams['name']] = 1;
        } else {
          $this->_apiParams[$possibleParams['name']] = 0;
        }
      }
      if (isset($this->_apiParams[$possibleParams['name']])) {
        $customFields[$column] = array('value' => $this->_apiParams[$possibleParams['name']], 'type' => $possibleParams['type']);
      }
    }
    return $customFields;
  }
  /**
   * Method to create activity
   */
  public function processActivity() {
    $activity = new CRM_Wpcivi_Activity();
    $created = $activity->create($this->_activityParams);
    // now add custom data
    $customData = $this->constructActivityCustomData($created['id']);
    if (!empty($customData)) {
      CRM_Wpcivi_Utils::addCustomData($customData);
    }
  }
}
