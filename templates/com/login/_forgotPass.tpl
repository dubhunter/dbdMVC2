{* dbdSmarty *}
<div id="forgotPass">
	{include file="`$smarty.const.DBD_TPL_DIR`global/_errorList.tpl"}
	<form name="password_form" id="password_form" method="post" action="{dbduri r=$page_url a='processForgotPass'}">
		<h2>Forgot Your Password?</h2>
		<p>Enter your email and click Reset. You will recieve an email with your new temporary password.</p>
		<label for="email">Email Address</label>
		<input type="text" id="email" name="email" value="{$email}" />
		<p>Would you like to <a href="{dbduri r=$page_url a='register'}" title="Register">Register?</a></p>
		{inputcss type="button" id="reset" name="button" value="Reset"}
	</form>
</div>