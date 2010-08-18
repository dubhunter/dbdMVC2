<?php /* Smarty version 2.6.26a, created on 2010-08-16 16:04:51
         compiled from global/_pageHeader.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'default', 'global/_pageHeader.tpl', 11, false),)), $this); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="author" content="Don't Blink Design, Inc." />
	<meta name="keywords" content="dbdMVC, model-view-controller" />
	<meta name="description" content="<?php echo $this->_tpl_vars['title']; ?>
" />
	<link rel="icon" href="/favicon.ico" type="image/x-icon" />
	<title><?php echo $this->_tpl_vars['app_name']; ?>
 - <?php echo ((is_array($_tmp=@$this->_tpl_vars['page_title'])) ? $this->_run_mod_handler('default', true, $_tmp, 'Home') : smarty_modifier_default($_tmp, 'Home')); ?>
</title>
</head>
<body id="www-dbdmvc-com">
<div id="pageAll" class="<?php echo ((is_array($_tmp=@$this->_tpl_vars['page_class'])) ? $this->_run_mod_handler('default', true, $_tmp, 'index default') : smarty_modifier_default($_tmp, 'index default')); ?>
">
	<div id="pageHead">
	<?php if ($this->_tpl_vars['session']['user_logged_in']): ?>
		LOGGED IN!
	<?php endif; ?>
		<h1><a href="/" title="<?php echo $this->_tpl_vars['app_name']; ?>
"><span><?php echo $this->_tpl_vars['app_name']; ?>
</span></a></h1>
	</div>
	<div id="pageBody">