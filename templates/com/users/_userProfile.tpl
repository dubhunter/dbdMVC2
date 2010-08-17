{* dbdSmarty *}
<div id="userProfile">
	{include file="`$smarty.const.DBD_TPL_DIR`global/_errorList.tpl"}
	<form name="user_profile_form" id="user_profile_form" method="post" action="{dbduri r=$page_url a='updateProfile'}">
		<h2>{if $user.user_id > 0}Edit{else}Add{/if} User Profile</h2>
		<label for="first_name">First Name</label>
		<input type="text" id="first_name" name="first_name" value="{$user.first_name}" />
		<label for="last_name">Last Name</label>
		<input type="text" id="last_name" name="last_name" value="{$user.last_name}" />
		<label for="nick_name">Nick Name</label>
		<input type="text" id="nick_name" name="nick_name" value="{$user.nick_name}" />
		<label for="email">Email Address</label>
		<input type="text" id="email" name="email" value="{$user.email}" />
		<label for="pass">Password</label>
		<input type="password" id="pass" name="pass" />
		<label for="confirm_pass">Confirm Password</label>
		<input type="password" id="confirm_pass" name="confirm_pass" />
		{inputcss type="button" id="save" name="button" value="Save"}
	</form>
</div>