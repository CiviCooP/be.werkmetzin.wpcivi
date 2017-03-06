<?php
/**
 * Class for Group configuration
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 25 March 2016
 * @license AGPL-3.0
 */
class CRM_Wpcivi_Group {

  protected $_apiParams = array();

  /**
   * CRM_Wpcivi_Group constructor.
   */
  public function __construct() {
    $this->_apiParams = array();
  }
  /**
   * Method to validate params for create
   *
   * @param $params
   * @throws Exception when missing mandatory params
   */
  private function validateCreateParams($params) {
    if (!isset($params['name']) || empty($params['name'])) {
      throw new Exception('Missing mandatory param name in class '.__CLASS__);
    }
    $this->_apiParams = $params;
  }

  /**
   * Method to create or update group
   *
   * @param $params
   * @throws Exception when error in API Group Create or when missing mandatory param name
   * @access public
   */
  public function create($params) {
    $this->validateCreateParams($params);
    $existing = $this->getWithName($this->_apiParams['name']);
    if (isset($existing['id'])) {
      $this->_apiParams['id'] = $existing['id'];
    }
    $this->sanitizeParams();
    try {
      civicrm_api3('Group', 'Create', $this->_apiParams);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not create or update group type with name'
        .$this->_apiParams['name'].' in '.__METHOD__.', error from API Group Create: ' . $ex->getMessage());
    }
  }

  /**
   * Method to get the group with a name
   *
   * @param string $groupName
   * @return array|bool
   * @access public
   */
  public function getWithName($groupName) {
    try {
      return civicrm_api3('Group', 'Getsingle', array('name' => $groupName));
    } catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
  }

  /**
   * Method to sanitize params for group create api
   *
   * @access private
   */
  private function sanitizeParams() {
    if (!isset($this->_apiParams['is_active'])) {
      $this->_apiParams['is_active'] = 1;
    }
    if (isset($this->_apiParams['group_type'])) {
      $this->_apiParams['group_type'] = CRM_Core_DAO::VALUE_SEPARATOR
        .$this->_apiParams['group_type'].CRM_Core_DAO::VALUE_SEPARATOR;
    }
    if (empty($this->_apiParams['title']) || !isset($this->_apiParams['title'])) {
      $this->_apiParams['title'] = CRM_Wpcivi_Utils::buildLabelFromName($this->_apiParams['name']);
    }
    // if parent is set, retrieve parent number with name and set parents
    if (isset($this->_apiParams['parent'])) {
      $parentGroup = $this->getWithName($this->_apiParams['parent']);
      if ($parentGroup) {
        $this->_apiParams['parents'] = $parentGroup['id'];
      }
      unset($this->_apiParams['parent']);
    }
  }
}