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


/**
 * Provides lookup functions for the legacy sepa_lernen data
 *
 * @param debitor_name
 * @param remote_information_usage
 * @param remote_information_reference
 * @param debitor_iban
 * @param spendenbetrag
 */
function civicrm_api3_sepa_lernen_find($params) {
  $sepa_lernen = CRM_Sepacustom_SepaLernen::singleton();
  if (!$sepa_lernen->isDataAvailable()) {
    return civicrm_api3_create_error("Table 'sepa_lernen' not found.");
  }

  $result = $sepa_lernen->deriveData($params);
  if ($result) {
    $result['reference'] = "{$result['aktionid']}_{$result['personid']}_{$result['vertragid']}";
    return civicrm_api3_create_success($result);
  } else {
    return civicrm_api3_create_error("No match");
  }
}

/**
 * specs
 */
function _civicrm_api3_sepa_lernen_find_spec(&$params) {
  $params['debitor_name'] = array(
    'title' => 'Debitor Name',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['debitor_iban'] = array(
    'title' => "Debitor's IBAN",
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['creditor_iban'] = array(
    'title' => "Creditor's IBAN",
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['remote_information_usage'] = array(
    'title' => 'Assignment',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['remote_information_reference'] = array(
    'title' => 'Reference',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['endtoendid'] = array(
    'title' => 'EndToEndId',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['debitor_spendenbetrag'] = array(
    'title' => 'Amount',
    'type' => CRM_Utils_Type::T_STRING,
  );
}