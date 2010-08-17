{* Smarty *}
{assign var="page_title" value="$code - $name"}
{include file="_pageHeader.tpl"}
	<div id="errorDiv">
		<h2>{$code} - {$name}</h2>
		<h4>{$msg}</h4>
	</div>
{include file="_pageFooter.tpl"}