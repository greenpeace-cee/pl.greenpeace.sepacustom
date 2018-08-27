<?php
/*-------------------------------------------------------+
| Project 60 - SEPA direct debit                         |
| Copyright (C) 2016-2018                                |
| Author: @scardinius                                    |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

class CRM_Sepa_Logic_Format_bankpekaopl extends CRM_Sepa_Logic_Format {

  /**
   * Apply string encoding
   * for more information why we cannot use UTF-8 please see RM #1648
   * 
   * @param string $content
   *
   * @return mixed
   */
   
  public function characterEncode($content) {
    return iconv('UTF-8', 'CP852', $content);
  }
  
  /**
   * gives the option of setting extra variables to the template
   * encodings are needed to calculate the right length of the output with mb_strlen
   */
  
  public function assignExtraVariables($template) {
    $template->assign('settings', array(
      'donation_description' => 'Darowizna na cele',
      'creditor_name_prefix' => 'Fundacja',
      'statutory_foundation' => 'statutowe Fundacji',
      'encoding_input' => 'UTF-8',
      'encoding_output' => 'CP852',
    ));
  }
  
  
  /**
   * Lets the format add extra information to each individual
   *  transaction (contribution + extra data)
   */
  public function extendTransaction(&$txn, $creditor_id) {
    $contribution_recur_id = $txn["contribution_recur_id"];
    $result = CRM_Core_DAO::executeQuery("select m.entity_id as membership_id from civicrm_value_membership_payment m where m.membership_recurring_contribution = " . $contribution_recur_id);
    while ($result->fetch()){
      $txn['membership_id'] = $result->membership_id;
    }
    // set display_name in original encoding
    $result = CRM_Core_DAO::executeQuery("select display_name from civicrm_contact where id = %0", [[$txn['contact_id'], 'Integer']]);
    while ($result->fetch()) {
      $txn['display_name'] = $result->display_name;
    }
    return $txn;
  }
  

  public function getDDFilePrefix() {
    return 'DD';
  }
  
  /**
   * The System allows importing a file containing alphanumeric characters and the following
   * characters ( ).,/:;\+!@#$&*{}[]?=' "
   */
  public function getFilename($variable_string) {
    return preg_replace("/[^ 0-9a-zA-Z().,+!@#$&*{}[\]?='\"]/",'',$variable_string).'.pld';
  }
}
