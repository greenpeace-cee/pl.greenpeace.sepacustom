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

  // this is an a field array. The outer one is AND, the inner one OR,
  //  e.g. (('f1', 'f2'),('f3', 'f4')) results in
  //  SELECT ... FROM ... WHERE ((f1=@val1 OR f2=@val2) AND (f3=@val3 OR f4=@val4))
  $fields = array(
    array('debitor_name', 'debitor_iban'),
    array('remote_information_usage', 'remote_information_reference', 'endtoendid'),
    array('spendenbetrag'),
  );

  // compile query
  $query_parameters = array();
  $counter = 1;
  $ANDclauses = array();
  foreach ($fields as $field_list) {
    $ORclauses = array();
    foreach ($field_list as $field) {
      if (!empty($params[$field])) {
        $query_parameters[$counter] = array($params[$field], 'String');
        $ORclauses[] = "`{$field}` LIKE %{$counter}";
        $counter += 1;
      }
    }
    if (!empty($ORclauses)) {
      // at least one of the fields of the clause is present
      $ANDclauses[] = '(' . implode(') OR (', $ORclauses) .')';
    }
  }

  if (!empty($ANDclauses)) {
    $where_clause = '(' . implode(') AND (', $ANDclauses) . ')';
    $query = CRM_Core_DAO::executeQuery("SELECT kntsepalearnid, aktionid, personid, vertragid FROM sepa_lernen WHERE {$where_clause} AND status = 1;", $query_parameters);
    $results = array();
    while ($query->fetch()) {
      $results[$query->kntsepalearnid] = array(
        'id' => $query->kntsepalearnid,
        'aktionid'  => $query->aktionid,
        'personid'  => $query->personid,
        'vertragid' => $query->vertragid,
        'reference' => "{$query->aktionid}_{$query->personid}_{$query->vertragid}");
    }

    return civicrm_api3_create_success($results);
  } else {
    return civicrm_api3_create_error("Not enough values");
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