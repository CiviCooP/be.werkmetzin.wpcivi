<?php
/**
 * Class following Singleton pattern for specific extension configuration
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 15 Feb 2016
 * @license AGPL-3.0
 */
class CRM_Wpcivi_Config {

  private static $_singleton;

  protected $_resourcesPath = null;

  /**
   * CRM_CWpcivi_Config constructor.
   */
  function __construct() {

    $settings = civicrm_api3('Setting', 'Getsingle', array());
    $this->resourcesPath = $settings['extensionsDir'].'/be.werkmetzin.wpcivi/resources/';
    $this->setGroups();
    $this->setOptionGroups();
    $this->setActivityTypes();
    // customData as last one because it might need one of the previous ones (option group, relationship types)
    $this->setCustomData();
  }

  /**
   * Singleton method
   *
   * @return CRM_Wpcivi_Config
   * @access public
   * @static
   */
  public static function singleton() {
    if (!self::$_singleton) {
      self::$_singleton = new CRM_Wpcivi_Config();
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
    $jsonFile = $this->resourcesPath.'option_groups.json';
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
    $jsonFile = $this->resourcesPath.'activity_types.json';
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
    $jsonFile = $this->resourcesPath.'custom_data.json';
    if (!file_exists($jsonFile)) {
      throw new Exception(ts('Could not load custom data configuration file for extension, contact your system administrator!'));
    }
    $customDataJson = file_get_contents($jsonFile);
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

  /**
   * Method to create or get groups
   *
   * @throws Exception when resource file could not be loaded
   */
  protected function setGroups() {
    $jsonFile = $this->resourcesPath . 'groups.json';
    if (!file_exists($jsonFile)) {
      throw new Exception('Could not load groups configuration file for extension in '.__METHOD__
        .', contact your system administrator!');
    }
    $groupJson = file_get_contents($jsonFile);
    $groups = json_decode($groupJson, true);
    foreach ($groups as $params) {
      $group = new CRM_Wpcivi_Group();
      $group->create($params);
    }
  }
}