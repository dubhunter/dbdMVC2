<?php /* Smarty version 2.6.26a, created on 2010-08-16 16:04:51
         compiled from global/_pageFooter.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'date', 'global/_pageFooter.tpl', 5, false),)), $this); ?>
		</div>
	</div>
	<div id="pageFoot">
		<p class="legal">&copy; <?php echo ((is_array($_tmp=time())) ? $this->_run_mod_handler('date', true, $_tmp, 'Y') : smarty_modifier_date($_tmp, 'Y')); ?>
 Don't Blink Design, Inc. All Rights Reserved</p>
	</div>
</div>
</body>
</html>