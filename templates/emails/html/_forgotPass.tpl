{* dbdSmarty *}
<div>
	<h2>Hi {$first_name}</h2>
	<p>Here is your new temporary dbdAdmin password: <strong>{$temp_password}</strong></p>
	<p>Please sign in and change your password: <a href="http://{$smarty.server.HTTP_HOST}{dbduri c='dbdAdmin'}">http://{$smarty.server.HTTP_HOST}{dbduri c='dbdAdmin'}</a></p>
	<p>If you did not request a new password, please contact the support team.</p>
	<p>Thanks,<br/>DBD</p>
</div>