<?php
/**
 * Class to build Custom Data Query
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 17 Feb 2016
 * @license AGPL-3.0
 */
class CRM_Wpcivi_CustomDataQuery {

  protected $_query = NULL;
  protected $_queryParams = array();

  private $_sourceParams = array();
  private $_requiredParams = array();
  private $_validQueryActions = array();


  /**
   * CRM_Wpcivi_CustomDataQuery constructor.
   *
   * @param array $params
   */
  public function __construct($params) {
    $this->_sourceParams = $params;
    $this->_requiredParams = array('table_name', 'custom_fields', 'entity_id', 'query_action');
    $this->_validQueryActions = array('insert', 'update', 'select', 'delete');
    $this->validateSourceParams();
    // params before query so it can be corrected for select
    $this->setQueryParams();
    $this->setQuery();
  }

  /**
   * Method to get the query
   *
   * @return null
   * @access public
   */
  public function getQuery() {
    return $this->_query;
  }

  /**
   * Method to get the query parameters
   *
   * @return array
   * @access public
   */
  public function getQueryParams() {
    return $this->_queryParams;
  }

  /**
   * Method to build the relevant query from the sourceParams
   *
   * @access private
   */
  private function setQuery() {
    switch ($this->_sourceParams['query_action']) {
      case "insert":
        $this->buildInsertQuery();
        break;
      case "update":
        $this->buildUpdateQuery();
        break;
      case "delete":
        $this->buildDeleteQuery();
        break;
      case "select":
        $this->buildSelectQuery();
        break;
    }
  }

  /**
   * Method to build insert query
   *
   * @access private
   */
  private function buildInsertQuery() {
    $queryColumns = array("entity_id");
    $queryValues = array("%1");
    $index = 1;
    foreach ($this->_sourceParams['custom_fields'] as $column => $value) {
      $queryColumns[] = $column;
      $index++;
      $queryValues[] = "%".$index;
    }
    $columns = implode(",", $queryColumns);
    $values = implode("," , $queryValues);
    $this->_query = "INSERT INTO {$this->_sourceParams['table_name']} ({$columns}) VALUES({$values})";
  }

  /**
   * Method to build update query
   *
   * @access private
   */
  private function buildUpdateQuery() {
    $querySets = array();
    $index = 1;
    foreach ($this->_sourceParams['custom_fields'] as $column => $value) {
      $index++;
      $querySets[] = $column." = %".$index;
    }
    $sets = implode(",", $querySets);
    $this->_query = "UPDATE {$this->_sourceParams['table_name']} SET {$sets} WHERE entity_id = %1";
  }

  /**
   * Method to build delete query
   *
   * @access private
   */
  private function buildDeleteQuery() {
    $this->_query = "DELETE FROM ".$this->_sourceParams['table_name']." WHERE entity_id = ".$this->_sourceParams['entity_id'];
  }

  /**
   * Method to build select query
   *
   * @access private
   */
  private function buildSelectQuery() {
    $querySelectFields = array();
    $this->_queryParams = array(1 => array($this->_sourceParams['entity_id'], 'Integer'));
    foreach ($this->_sourceParams['custom_fields'] as $column => $value) {
      $querySelectFields[] = $column;
    }
    $this->_query = "SELECT ".implode(",", $querySelectFields)." FROM ".$this->_sourceParams['table_name']
      ." WHERE entity_id = %1";
  }

  /**
   * Method to build query params
   *
   * @access private
   */
  private function setQueryParams() {
    $this->_queryParams[1] = array($this->_sourceParams['entity_id'], 'Integer');
    $index = 1;
    foreach ($this->_sourceParams['custom_fields'] as $key => $value) {
      $index++;
      $this->_queryParams[$index] = array($value['value'], $value['type']);
    }
  }

  /**
   * Method to validate the source params before attempting to build the query
   *
   * @throws Exception when error found
   * @access private
   */
  private function validateSourceParams() {
    foreach ($this->_requiredParams as $required) {
      if (!isset($this->_sourceParams[$required])) {
        throw new Exception(ts('Missing mandatory param ').$required.ts(' in CRM_Wpcivi_CustomDataQuery'));
      }
    }
    if (empty($this->_sourceParams['custom_fields']) || !is_array($this->_sourceParams['custom_fields'])) {
      throw new Exception(ts('Could not find any data to build a custom data query for in CRM_Wpcivi_CustomDataQuery'));
    }
    if (empty($this->_sourceParams['table_name'])) {
      throw new Exception(ts('Empty table_name in params, could not build query for custom data in CRM_Wpcivi_CustomDataQuery'));
    }
    if (empty($this->_sourceParams['entity_id'])) {
      throw new Exception(ts('Empty entity_id in params, could not build query for custom data in CRM_Wpcivi_CustomDataQuery'));
    }
    if (empty($this->_sourceParams['query_action'])) {
      throw new Exception(ts('Empty query_action in params, could not build query for custom data in CRM_Wpcivi_CustomDataQuery'));
    }
    $this->_sourceParams['query_action'] = strtolower($this->_sourceParams['query_action']);
    if (!in_array($this->_sourceParams['query_action'], $this->_validQueryActions)) {
      throw new Exception(ts('Invalid query_action '.$this->_sourceParams['query_action']
        .' in params, could not build query for custom data in CRM_Wpcivi_CustomDataQuery'));
    }
  }
}