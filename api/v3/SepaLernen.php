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
  // check if table is there to avoid crashes
  $test = CRM_Core_DAO::executeQuery("SHOW TABLES LIKE 'sepa_lernen';");
  if (!$test->fetch()) {
    return civicrm_api3_create_error("Table 'sepa_lernen' not found.");
  }

  // compile query
  $fields = array('debitor_name', 'remote_information_usage', 'remote_information_reference', 'debitor_iban', 'spendenbetrag');
  $query_clauses = array();
  $query_parameters = array();
  $counter = 1;
  foreach ($fields as $field) {
    if (!empty($params[$field])) {
      $query_clauses[] = "`{$field}` LIKE %{$counter}";
      $query_parameters[$counter] = array($params[$field], 'String');
      $counter += 1;
    }
  }

  if (empty($query_clauses)) {
    return civicrm_api3_create_error("No query parameters given.");
  }

  // execute query
  $where_clause = implode(' AND ', $query_clauses);
  $query = CRM_Core_DAO::executeQuery("SELECT kntsepalearnid, aktionid, personid, vertragid FROM sepa_lernen WHERE {$where_clause} AND status = 1;", $query_parameters);
  $results = array();
  while ($query->fetch()) {
    $results[$query->kntsepalearnid] = "{$query->aktionid}_{$query->personid}_{$query->vertragid}";
  }

  return civicrm_api3_create_success($results);
}

/**
 * specs
 */
function _civicrm_api3_sepa_lernen_find_spec(&$params) {
  $params['debitor_name'] = array(
    'title' => 'Debitor Name',
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
  $params['debitor_iban'] = array(
    'title' => "Debitor's IBAN",
    'type' => CRM_Utils_Type::T_STRING,
  );
  $params['debitor_spendenbetrag'] = array(
    'title' => 'Amount',
    'type' => CRM_Utils_Type::T_STRING,
  );
}