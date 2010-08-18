{* dbdSmarty *}
{include file="global/_pageHeader.tpl"}
<a href="{dbduri c='dbdAdmin'}" title="Admin">Admin</a>
<pre>{$count}
{$data|escape:'html'}</pre>
{include file="global/_pageFooter.tpl"}