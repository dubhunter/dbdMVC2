<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     resource.db.php
 * Type:     resource
 * Name:     string
 * Purpose:  Useses a string as a template
 * -------------------------------------------------------------
 */
function smarty_resource_string_source($tpl_name, &$tpl_source, &$smarty)
{
    $tpl_source = $tpl_name;
    return true;
}

function smarty_resource_string_timestamp($tpl_name, &$tpl_timestamp, &$smarty)
{
	$tpl_timestamp = time();
	return true;
}

function smarty_resource_string_secure($tpl_name, &$smarty)
{
    // assume all templates are secure
    return true;
}

function smarty_resource_string_trusted($tpl_name, &$smarty)
{
    // not used for templates
}
?>