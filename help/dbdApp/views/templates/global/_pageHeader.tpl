{* dbdSmarty *}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="author" content="Don't Blink Design, Inc." />
{if $page_keys}
	<meta name="keywords" content="{$page_keys}" />
{/if}
{if $page_desc}
	<meta name="description" content="{$page_desc}" />
{/if}
	<link rel="icon" href="/favicon.ico" type="image/x-icon" />
	<title>{$app_name} - {$page_title|default:'Home'}</title>
</head>
<body id="www-dbdmvc-com">
<div id="pageAll" class="{$page_class|default:'index default'}">
	<div id="pageHead">
	{if $session.user_logged_in}
		LOGGED IN!
	{/if}
		<h1><a href="/" title="{$app_name}"><span>{$app_name}</span></a></h1>
	</div>
	<div id="pageBody">