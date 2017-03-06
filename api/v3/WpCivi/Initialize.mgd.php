<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'Cron:WpCivi.Initialize',
    'entity' => 'Job',
    'params' => 
    array (
      'version' => 3,
      'name' => 'Call WpCivi.Initialize API',
      'description' => 'Initialize or Update Werk Met Zin WpCivi',
      'run_frequency' => 'Daily',
      'api_entity' => 'WpCivi',
      'api_action' => 'Initialize',
      'parameters' => '',
      'is_active' => 0
    ),
  ),
);