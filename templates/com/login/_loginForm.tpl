{* dbdSmarty *}
<div id="loginForm">
	{include file="`$smarty.const.DBD_TPL_DIR`global/_errorList.tpl"}
	<form name="login_form" id="login_form" method="post" action="{dbduri r=$page_url a='login'}">
		<h2>Please Log In</h2>
		<label for="email">Email Address</label>
		<input type="text" id="email" name="email" value="{$email}" />
		<input type="checkbox" id="remember_me" name="remember_me" value="1" />
		<label for="remember_me">Keep me logged in for two weeks.</label>
		<label for="pass">Password</label>
		<input type="password" id="pass" name="pass" />
		<p><a href="{dbduri r=$page_url a='forgotPass'}" title="Forgot Password">Forgot Password?</a> Would you like to <a href="{dbduri r=$page_url a='register'}" title="Register">Register?</a></p>
		{inputcss type="button" id="login" name="button" value="Login"}
	</form>
</div>