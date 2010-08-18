{* dbdSmarty *}
<div>
	<!--<img src="http://{$smarty.server.HTTP_HOST}/images/gfx/lbt_logo_message.jpg" border="0" alt="Discount Displays" width="0" height="0" />-->
	<h2>Full Name: {$full_name}</h2>
	<p>Subject: {$email_subject}</p>
	<p>Company: {$company}</p>
	<p>Email Address: {$email_from}</p>
	<p>Telephone: {$phone}</p>
	<p>Regarding: {$regarding}</p>
	<p>Preferred Method of Contact: {$contact_method}</p>
	<p>Preferred Contact Time: {$contact_time}</p>
	<p>{$email_body|nl2pp}</p>
</div>