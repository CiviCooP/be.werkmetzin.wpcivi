<?php

/**
 * Abstract Class handling API call
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 10 Feb 2016
 * @license AGPL-3.0
 */
abstract class CRM_Wpcivi_ApiHandler {
  protected $_apiParams = array();

  /**
   * CRM_Wpcivi_ApiHandler constructor.
   *
   * @param array $params
   * @throws Exception when $params not an array
   */
  public function __construct($params) {
    if (!is_array($params)) {
      throw new Exception(ts('Params passed to CRM_Wpcivi_ApiHandler is not an array!'));
    }
    $this->_apiParams = $params;
  }

  /**
   * Abstract method to process the params
   *
   * @return mixed
   */
  abstract public function processParams();

  /**
   * Method to determine which handler to use based on the type of form processed
   *
   * @param string $formType
   * @throws Exception if $formType is invalid
   * @throws Exception if class not found
   *
   */
  public static function getHandler($formType) {
    if (!ctype_alpha($formType)) {
      throw new Exception(ts('The type of form passed to CRM_Wpcivi_APiHandler::getHandler can only contain alphanumeric
      characters, '.$formType.' is not valid.'));
    }
    $className = "CRM_Wpcivi_".$formType;
    if (!class_exists($className)) {
      throw new Exception(ts('No handling class for '.$formType.' defined in extension be.werkmetzin.wpcivi.
      Api call can not be processed.'));
    }
    $handler = new $className();
    return $handler;
  }
}