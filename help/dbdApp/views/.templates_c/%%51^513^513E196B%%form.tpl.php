<?php /* Smarty version 2.6.26a, created on 2010-08-16 16:04:51
         compiled from form.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'dbduri', 'form.tpl', 3, false),array('function', 'recaptcha', 'form.tpl', 4, false),array('function', 'inputcss', 'form.tpl', 6, false),)), $this); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "global/_pageHeader.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<form name="captcha" id="captcha" method="post" action="<?php echo smarty_function_dbduri(array('r' => $this->_tpl_vars['page_url'],'a' => 'process'), $this);?>
">
	<?php echo smarty_function_recaptcha(array(), $this);?>

	<input type="submit" name="go" id="go" value="go" />
	<?php echo smarty_function_inputcss(array('type' => 'button','id' => 'upload','name' => 'button','value' => 'Upload'), $this);?>

	<?php echo smarty_function_inputcss(array('type' => 'button','id' => 'upload2','name' => 'button','value' => 'Upload'), $this);?>

</form>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "global/_pageFooter.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>