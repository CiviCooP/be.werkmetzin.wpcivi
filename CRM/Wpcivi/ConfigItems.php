<?php
/**
 * Class following Singleton pattern o create or update configuration items from
 * JSON files in resources folder
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 15 Feb 2016
 * @license AGPL-3.0
 */
class CRM_Wpcivi_ConfigItems {

  private static $_singleton;

  protected $_resourcesPath;
  protected $_customDataDir;

  /**
   * CRM_CWpcivi_ConfigItems constructor.
   */
  function __construct() {

    $settings = civicrm_api3('Setting', 'Getsingle', array());
    $resourcesPath = $settings['extensionsDir'].'/be.werkmetzin.wpcivi/resources/';
    if (!is_dir($resourcesPath) || !file_exists($resourcesPath)) {
      throw new Exception(ts('Could not find the folder '.$resourcesPath
        .' which is required for extension be.werkmetzin.wpcivi in '.__METHOD__
        .'.It does not exist or is not a folder, contact your system administrator'));
    }
    $this->_resourcesPath = $resourcesPath;
    $this->setOptionGroups();
    $this->setActivityTypes();
    // customData as last one because it might need one of the previous ones (option group, relationship types)
    $this->setCustomData();
  }

  /**
   * Singleton method
   *
   * @return CRM_Wpcivi_ConfigItems
   * @access public
   * @static
   */
  public static function singleton() {
    if (!self::$_singleton) {
      self::$_singleton = new CRM_Wpcivi_ConfigItems();
    }
    return self::$_singleton;
  }

  /**
   * Method to create option groups
   *
   * @throws Exception when resource file not found
   * @access protected
   */
  protected function setOptionGroups() {
    $jsonFile = $this->_resourcesPath.'option_groups.json';
    if (!file_exists($jsonFile)) {
      throw new Exception(ts('Could not load option_groups configuration file for extension,
      contact your system administrator!'));
    }
    $optionGroupsJson = file_get_contents($jsonFile);
    $optionGroups = json_decode($optionGroupsJson, true);
    foreach ($optionGroups as $name => $optionGroupParams) {
      $optionGroup = new CRM_Wpcivi_OptionGroup();
      $optionGroup->create($optionGroupParams);
    }
  }

  /**
   * Method to create activity types
   *
   * @throws Exception when resource file not found
   * @access protected
   */
  protected function setActivityTypes() {
    $jsonFile = $this->_resourcesPath.'activity_types.json';
    if (!file_exists($jsonFile)) {
      throw new Exception(ts('Could not load activity_types configuration file for extension,
      contact your system administrator!'));
    }
    $activityTypesJson = file_get_contents($jsonFile);
    $activityTypes = json_decode($activityTypesJson, true);
    foreach ($activityTypes as $name => $params) {
      $activityType = new CRM_Wpcivi_ActivityType();
      $activityType->create($params);
    }
  }

  /**
   * Method to set the custom data groups and fields
   *
   * @throws Exception when config json could not be loaded
   * @access protected
   */
  protected function setCustomData() {
    // read all json files from custom_data dir
    $customDataPath = $this->_resourcesPath.'/custom_data';
    if (file_exists($customDataPath) && is_dir($customDataPath)) {
      $cdDir = opendir($this->_resourcesPath.'/custom_data');
      while (($fileName = readdir($cdDir)) != FALSE) {
        $extName = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($extName == 'json') {
          $customDataJson = file_get_contents($fileName);
          $customData = json_decode($customDataJson, true);
          foreach ($customData as $customGroupName => $customGroupData) {
            $customGroup = new CRM_Wpcivi_CustomGroup();
            $created = $customGroup->create($customGroupData);
            foreach ($customGroupData['fields'] as $customFieldName => $customFieldData) {
              $customFieldData['custom_group_id'] = $created['id'];
              $customField = new CRM_Wpcivi_CustomField();
              $customField->create($customFieldData);
            }
            // remove custom fields that are still on install but no longer in config
            CRM_Wpcivi_CustomField::removeUnwantedCustomFields($created['id'], $customGroupData);
          }
        }
      }
    }
  }
}