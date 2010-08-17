{* dbdSmarty *}
{assign var="page_title" value="$code - $name"}
{include file="`$smarty.const.DBD_TPL_DIR`global/_pageHeader.tpl"}
	<div id="errorDiv">
		<h2>{$code} - {$name}</h2>
		<h4>{$msg}</h4>
	</div>
{include file="`$smarty.const.DBD_TPL_DIR`global/_pageFooter.tpl"}