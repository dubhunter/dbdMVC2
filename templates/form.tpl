{* dbdMVC Smarty *}
{include file="`$smarty.const.DBD_TPL_DIR`global/_pageHeader.tpl"}
<form name="captcha" id="captcha" method="post" action="{dbduri r=$page_url a='process'}">
	{recaptcha}
	<input type="submit" name="go" id="go" value="go" />
	{inputcss type="button" id="upload" name="button" value="Upload"}
	{inputcss type="button" id="upload2" name="button" value="Upload"}
</form>
{include file="`$smarty.const.DBD_TPL_DIR`global/_pageFooter.tpl"}