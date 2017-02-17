<?php
/*-------------------------------------------------------+
| Greenpeace AT CiviSEPA Customisations                  |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

require_once 'sepacustom.civix.php';

/**
 * Generate custom SEPA mandate reference
 * 
 * @see https://redmine.greenpeace.at/issues/460
 *
 * @author B. Endres (endres@systopia.de)
 */
function sepacustom_civicrm_create_mandate(&$mandate_parameters) {
  if (isset($mandate_parameters['reference']) && !empty($mandate_parameters['reference']))
    return;   // user defined mandate

  // GP-1-FRST-2016-Cxxxxxxx-xxx
  $reference_fmt = "GP-%s-%s-%s-C%07d-%03d";
  $creditor_id   = $mandate_parameters['creditor_id'];
  $type          = $mandate_parameters['type'];
  $year          = date('Y');
  $contact_id    = (int) $mandate_parameters['contact_id'];

  // find and set the first unused one
  $serial = 1;
  while ($serial < 1000) {
    $reference_candidate = sprintf($reference_fmt, $creditor_id, $type, $year, $contact_id, $serial);
    if (CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_sdd_mandate WHERE reference = '$reference_candidate';")) {
      // this reference_candidate already exists;
      $serial += 1;
      continue;
    } else {
      // this reference_candidate is available
      $mandate_parameters['reference'] = $reference_candidate;
      return;
    }
  }
  
  error_log("at.greenpeace.sepacustom: Mandate reference generation failed. Please contact SYSTOPIA.");
  CRM_Core_Session::setStatus("Mandate reference generation failed. Please contact SYSTOPIA.", ts('Error'), 'error');
}

/**
 * Connect newly generated SEPA contributions 
 *  to the membership/contract
 *
 * @author B. Endres (endres@systopia.de)
 */
function sepacustom_installment_created($mandate_id, $contribution_recur_id, $contribution_id) {
  try {
    CRM_Core_DAO::executeQuery("
        INSERT IGNORE INTO civicrm_membership_payment (membership_id,contribution_id)
         (SELECT 
           civicrm_value_membership_payment.entity_id AS membership_id,
           %1 AS contribution_id
          FROM civicrm_value_membership_payment
          WHERE membership_recurring_contribution = %2)",
      array( 1 => array($contribution_id, 'Integer'),
             2 => array($contribution_recur_id, 'Integer')));    
  } catch (Exception $e) {
    // TODO: I don't think this will catch all the potential
    //   DB problems, especially if the table's aren't there.
    //   Maybe we need a verification, but *not* for every installment...
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function sepacustom_civicrm_config(&$config) {
  _sepacustom_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function sepacustom_civicrm_xmlMenu(&$files) {
  _sepacustom_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function sepacustom_civicrm_install() {
  _sepacustom_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function sepacustom_civicrm_uninstall() {
  _sepacustom_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function sepacustom_civicrm_enable() {
  _sepacustom_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function sepacustom_civicrm_disable() {
  _sepacustom_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function sepacustom_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _sepacustom_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function sepacustom_civicrm_managed(&$entities) {
  _sepacustom_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * @param array $caseTypes
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function sepacustom_civicrm_caseTypes(&$caseTypes) {
  _sepacustom_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function sepacustom_civicrm_angularModules(&$angularModules) {
_sepacustom_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function sepacustom_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _sepacustom_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
