<?php
/**
 * WpCivi.Initialize API - create or update config items for extension
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_wp_civi_Initialize($params) {
  new CRM_Wpcivi_ConfigItems();
  $returnValues = array(ts('Updated config items for extension be.werkmetzin.wpcivi'));
  return civicrm_api3_create_success($returnValues, $params, 'WpCivi', 'Initialize');
}

