{foreach from=$contributions item="contribution"}
{assign var="address_string_len1" value=34}
{assign var="address_string_len2" value=$contribution.street_address|mb_strlen:$settings.encoding_input}
{assign var="address_string_len3" value=$contribution.street_address|mb_strlen}
{assign var="address_string_len4" value=$address_string_len1-$address_string_len2}
{assign var="address_len_final11" value=$address_string_len4+$address_string_len3}
{assign var="address_len_final12" value=$address_len_final11}
{assign var="street_address1" value=$contribution.street_address|replace:'"':' '|replace:'  ':' '|mb_substr:0:$address_len_final11}
{assign var="street_address2" value=$contribution.street_address|replace:'"':' '|replace:'  ':' '|mb_substr:$address_len_final12}
{assign var="street_address3" value=$street_address1|replace:'"':' '|replace:'  ':' '|cat:'//'|cat:$street_address2|cat:'/'|replace:'///':''|replace:' /':'/'|replace:'/ ':'/'}
{assign var="address_string_len12" value=$street_address3|mb_strlen:$settings.encoding_input}
{assign var="address_string_len13" value=$street_address3|mb_strlen}
{assign var="address_string_len14" value=$address_string_len1-$address_string_len12}
{assign var="address_len_final21" value=$address_string_len14+$address_string_len13}
{assign var="address_len_final22" value=$address_len_final21}
210,{$contribution.receive_date|replace:'-':''|truncate:8:""},{$contribution.total_amount|replace:'.':''|replace:',':''|replace:' ':''},{$creditor.iban|regex_replace:'/[A-Z][A-Z][0-9][0-9]/':""|truncate:8:""},0,"{$creditor.iban|regex_replace:'/[A-Z][A-Z]/':""|truncate:26:""}","{$contribution.iban|regex_replace:'/[A-Z][A-Z]/':""|truncate:26:""}","{$settings.creditor_name_prefix|replace:'"':""} {$creditor.name|replace:'"':""}||{$creditor.address|replace:'"':""}|","{$contribution.display_name|replace:'/':' '|replace:'"':' '|replace:"|":' '|truncate:35:""}||{$street_address3|mb_substr:0:$address_len_final21}|{'//'|cat:$street_address3|mb_substr:$address_len_final22|cat:$contribution.city|replace:'"':' '|replace:"|":' '|replace:'  ':' '} {$contribution.postal_code|replace:'/':' '|replace:'"':' '|replace:"|":' '|replace:'  ':' '}",0,{$contribution.iban|regex_replace:'/[A-Z][A-Z][0-9][0-9]/':""|truncate:8:""},"/NIP/{$creditor.identifier}/IDP/{$contribution.membership_id|mb_truncate:20:"":true:false}|/TYT/{'ID/'|cat:$contribution.contribution_id|cat:'/'|cat:$settings.donation_description|mb_truncate:35:"":true:false}|{$settings.statutory_foundation}|{$creditor.name}","","","01"
{/foreach}