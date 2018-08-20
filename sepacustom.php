<?php
/*-------------------------------------------------------+
| Greenpeace PL CiviSEPA Customisations                  |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
|   G. Aicher (georg.aicher -at- aicher-consulingt.at    |
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
 * This hook lets defer the collection date according to your banks preferences.
 * Most banks will only accept collection days that comply with their 'bank days'
 *
 * In this implementation, we only prevent the collection day to be on weekend,
 * but -depending on your bank- you might want to include national holidays as well.
 */
 /*prepared holidays but are not needed as Michal Pisarski*/
 /*
function sepacustom_civicrm_defer_collection_date(&$collection_date, $creditor_id) {
  $at_bank_holidays = array('2018-01-01',
                            '2018-01-06',
                            '2018-04-01',
                            '2018-04-02',
                            '2018-05-01',
                            '2018-05-03',
                            '2018-05-20',
                            '2018-05-31',
                            '2018-08-15',
                            '2018-11-01',
                            '2018-11-11',
                            '2018-12-25',
                            '2018-12-26',
                            '2019-01-01',
                            '2019-01-06',
                            '2019-04-21',
                            '2019-04-22',
                            '2019-05-01',
                            '2019-05-03',
                            '2019-06-09',
                            '2019-06-20',
                            '2019-08-15',
                            '2019-11-01',
                            '2019-11-11',
                            '2019-12-25',
                            '2019-12-26');

  while ( date('N', strtotime($collection_date)) > 5            // if Saturday or Sunday
          || in_array($collection_date, $at_bank_holidays)) {   // ...or bank holiday
    // defer by one day
    $collection_date = date('Y-m-d', strtotime("+1 day", strtotime($collection_date)));
  }
}
*/

/**
 * original for GPA:
 * Generate custom SEPA mandate reference
 *
 * @see https://redmine.greenpeace.at/issues/460
 *
 * @author B. Endres (endres@systopia.de)
 *
 * adapted for GPPL
 *
 * @see
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

  error_log("pl.greenpeace.sepacustom: Mandate reference generation failed. Please contact SYSTOPIA.");
  CRM_Core_Session::setStatus("Mandate reference generation failed. Please contact SYSTOPIA.", ts('Error'), 'error');
}

/**
 * This hook is called when a new transaction group is generated
 *
 * The default implementation is "TXG-${creditor_id}-${mode}-${collection_date}"
 *
 * Be aware the the reference has to be unique. You will have to use suffixes
 *  if your preferred reference is already in use.
 *
 * @param $reference        string  currently proposed reference (max. 35 characters!)
 * @param $collection_date  string  scheduled collection date
 * @param $mode             string  SEPA mode (OOFF, RCUR, FRST)
 * @param $creditor_id      string  SDD creditor ID
 *
 * @access public
 */
function sepacustom_civicrm_modify_txgroup_reference(&$reference, $creditor_id, $mode, $collection_date) {
  $base_reference = substr($mode, 0, 1) . "{$creditor_id}-{$collection_date}";
  $suffix = 0;

  while ($suffix < 1000) {
    // generate new reference
    if ($suffix) {
      $reference = $base_reference . "-{$suffix}";
    } else {
      $reference = $base_reference;
    }

    // check if it exists
    $reference_exists = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_sdd_txgroup WHERE reference = %1",
      array(1 => array($reference, 'String')));
    if (!$reference_exists) {
      // we found it!
      return;
    } else {
      $suffix += 1;
    }
  }
}


/**
 * Connect newly generated SEPA contributions
 *  to the membership/contract
 *
 * @author B. Endres (endres@systopia.de)
 */
function sepacustom_civicrm_installment_created($mandate_id, $contribution_recur_id, $contribution_id) {
  try {
    CRM_Sepacustom_InstallmentProcessor::installmentCreated($mandate_id, $contribution_recur_id, $contribution_id);
  } catch (Exception $e) {
    // TODO: I don't think this will catch all the potential
    //   DB problems, especially if the table's aren't there.
    //   Maybe we need a verification, but *not* for every installment...
  }
}

/**
 * CiviCRM POST hook to adjust a mandate's OOFF contribution
 */
function sepacustom_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($op == 'create' && $objectName == 'SepaMandate') {
    CRM_Sepacustom_InstallmentProcessor::ooffCreated($objectId);
  }
}

/**
 * Generate TXN Message
 *
 * @see https://redmine.greenpeace.at/issues/434
 */
function sepacustom_civicrm_modify_txmessage(&$txmessage, $info, $creditor) {
  $txmessage = "Greenpeace Beitrag Danke - {$info['reference']}";
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

  $customData = new CRM_Sepacustom_CustomData('pl.greenpeace.sepacustom');
  $customData->syncOptionGroup(__DIR__ . '/resources/formats_option_group.json');
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
