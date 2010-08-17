{* dbdSmarty *}
<ul id="mainNav">
{if $session.access.grant}
	<li><a href="{dbduri c='dbdAdmin' a='users'}" title="Users"><span>Users</span></a></li>
{/if}
	<li><a href="{dbduri c='dbdAdmin' a='pages'}" title="Pages"><span>Pages</span></a></li>
</ul>