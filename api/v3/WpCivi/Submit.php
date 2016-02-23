<?php

/**
 * WpCivi.Submit API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_wp_civi_Submit_spec(&$spec) {
  $spec['form_type']['api.required'] = 1;
}

/**
 * WpCivi.Submit API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_wp_civi_Submit($params) {
  if (array_key_exists('form_type', $params)) {
    $returnValues = array(ts('Data of form type '.$params['form_type']. 'processed'));
    $handler = CRM_Wpcivi_ApiHandler::getHandler($params['form_type']);
    $handler->processParams();
    return civicrm_api3_create_success($returnValues, $params, 'WpCivi', 'Submit');
  } else {
    throw new API_Exception(ts('Parameter form_type is mandatory', 1000));
  }
}

