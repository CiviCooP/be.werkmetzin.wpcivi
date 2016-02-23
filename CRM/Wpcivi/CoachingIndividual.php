<?php

/**
 * Abstract Class handling API call
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 10 Feb 2016
 * @license AGPL-3.0
 */

class CRM_Wpcivi_CoachingIndividual extends CRM_Wpcivi_ApiHandler {

  private $_contactParams = array();
  private $_activityParams = array();

  /**
   * Method to process the params from the api into contact and activity
   */
  public function processParams() {
    $this->_contactParams = $this->constructContactParams();
    $this->_activityParams = $this->constructActivityParams();
    // TODO: Implement processParams() method.
  }

  private function constructContactParams() {
    $contactParams = array();
    return $contactParams;
  }

  private function constructActivityParams() {
    $activityParams = array();
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
        return $contact->create($this->_contactParams);
        break;
      case 1:
        return $contact->getSingleContact($this->_contactParams);
        break;
      default:
        throw new Exception(ts('Found more than 1 contact in CRM_Wpcivi_CoachingIndividual with params ')
          .implode('; ', $this->_contactParams));
        break;
    }
  }

  /**
   * Method to create or update activity
   */
  public function processActivity() {
    $activity = new CRM_Wpcivi_Activity();
    // todo: check what needs doing if we already have an activity for contact?
    $activity->create($this->_activityParams);
  }
}