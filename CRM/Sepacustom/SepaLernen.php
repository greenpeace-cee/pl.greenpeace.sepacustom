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
 * Legacy connection to IMB's sepa_lernen table
 */
class CRM_Sepacustom_SepaLernen {
  private static $singleton = NULL;
  private static $parm2cond = array(
    'debitor_name'                 => 'dbtrnm',
    'debitor_iban'                 => 'dbtriban',
    'creditor_iban'                => 'cdtriban',
    'remote_information_usage'     => 'rmtinfustrd',
    'remote_information_reference' => 'rmtinfref',
    'endtoendid'                   => 'endtoendid',
    'debitor_spendenbetrag'        => 'txamt');
  protected $data_available = NULL;
  protected $entry_matcher  = NULL;
  protected $entry_cache    = array();

  public static function singleton() {
    if (self::$singleton === NULL) {
      self::$singleton = new CRM_Sepacustom_SepaLernen();
    }
    return self::$singleton;
  }

  protected function __construct() {
    // first: check if sepa_lernen table exists
    $test = CRM_Core_DAO::executeQuery("SHOW TABLES LIKE 'sepa_lernen';");
    $this->data_available = $test->fetch();
  }

  /**
   * check if the table with the data is present
   */
  public function isDataAvailable() {
    return $this->data_available;
  }

  /**
   * identify an entry and return the derived data
   */
  public function deriveData($params) {
    $derived_data = NULL;
    $derived_attributes = NULL;

    // first: translate the parameters
    $params = $this->param2Condition($params);

    // then iterate through the entries
    $entries = $this->getEntryConditions();
    foreach ($entries as $entry_id => $conditions) {
      $matched = TRUE;
      foreach ($conditions as $key => $value) {
        if ($value && isset($params[$key])) {
          if ($params[$key] != $value) {
            // the parameter differs
            error_log("$params[$key] != $value");
            $matched = FALSE;
            break;
          }
        }
      }

      if ($matched) {
        $entry = $this->getEntry($entry_id);
        if ($derived_data === NULL) {
          $derived_data = $entry;
          $derived_attributes = array_keys($entry);

        } else {
          // merge
          $new_derived_data = array();
          $new_derived_attributes = array();
          foreach ($derived_attributes as $attribute) {
            if (!isset($derived_data[$attribute]) || !isset($entry[$attribute])) {
              // attribute is not set in either set
              continue;
            } elseif ($derived_data[$attribute] != $entry[$attribute]) {
              // derived attributes differ!
              continue;
            } else {
              // all seems to be in order
              $new_derived_data[$attribute] = $derived_attributes[$attribute];
              $new_derived_attributes[] = $attribute;
            }
          }

          // move to the new set
          $derived_data = $new_derived_data;
          $derived_attributes = $new_derived_attributes;
        }
      }
      throw new Exception("STAHP", 1);
    }

    return $derived_data;
  }

  /**
   * translate the API parameters to the
   *  identifiers used in the condition table
   */
  protected function param2Condition($params) {
    $conditions = array();
    foreach (self::$parm2cond as $param_key => $cond_key) {
      if (isset($params[$param_key])) {
        $conditions[$cond_key] = $params[$param_key];
      }
    }
    return $conditions;
  }

  /**
   * load the given entry to derive data
   */
  protected function getEntry($entry_id) {
    if (!isset($this->entry_cache[$entry_id])) {
      $entry_id = (int) $entry_id;
      $query = CRM_Core_DAO::executeQuery("
        SELECT aktionid, personid, vertragid
          FROM sepa_lernen
         WHERE kntsepalearnid = {$entry_id}");
      if ($query->fetch()) {
        $this->entry_cache[$entry_id] = array(
          'aktionid'  => $query->aktionid,
          'personid'  => $query->personid,
          'vertragid' => $query->vertragid);
      } else {
        $this->entry_cache[$entry_id] = array();
      }
    }
    return $this->entry_cache[$entry_id];
  }

  /**
   * Get a mapping entry_id => conditions
   */
  protected function getEntryConditions() {
    if ($this->entry_matcher == NULL) {
      $this->entry_matcher = array();
      $entry = CRM_Core_DAO::executeQuery("
        SELECT kntsepalearnid AS entry_id,
               conditions     AS conditions
          FROM sepa_lernen
         WHERE status = 1");
      while ($entry->fetch()) {
        $conditions = unserialize($entry->conditions);
        if ($conditions && is_array($conditions)) {
          $this->entry_matcher[$entry->entry_id] = $conditions;
        }
      }
    }
    return $this->entry_matcher;
  }
}
