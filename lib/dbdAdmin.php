<?php
/**
 * dbdAdmin.php :: dbdAdmin Class File
 *
 * @package dbdMVC
 * @version 1.0
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2009 by Don't Blink Design
 */

/**
 * Parent controller for CMS administration suite.
 * @package dbdMVC
 * @uses dbdController
 * @uses dbdMVC
 */
class dbdAdmin extends dbdController
{
	const PASS_LENGTH = 8;

	protected function init()
	{
		$this->view->setCompileId(__CLASS__);
		$this->view->setTemplateDir(DBD_TPL_DIR);
	}

	protected function checkLogin()
	{
		if (!$this->session->isLoggedIn() || !$this->user->getUserGroup()->hasDashAccess())
			$this->forward(dbdURI::create(__CLASS__));
	}

	public function doDefault()
	{
		if ($this->session->isLoggedIn())
		{
			if ($this->user->getUserGroup()->hasDashAccess())
				$this->forward(dbdURI::create(__CLASS__, "home"));
			else
				$this->forward();
		}
		$this->setTemplate("index.tpl");
	}

	public function doLogin()
	{
		try
		{
			$this->session->processLogin($this->getParam("email"), $this->getParam("pass"), $this->getParam("remember_me"));
			$this->forward(dbdURI::create(__CLASS__, "home"));
		}
		catch (dbdAdminException $e)
		{
			$this->e($e);
			$this->doDefault();
		}
	}

	public function doLogout()
	{
		$this->session->processLogout();
		$this->forward(dbdURI::create(__CLASS__));
	}

	public function doForgotPass()
	{
		$this->doDefault();
		$this->setTemplate("forgotPass.tpl");
	}

	public function doProcessForgotPass()
	{
		$this->assignAllParams();
		try
		{
			$U = dbdUser::getUserByEmail($this->getParam("email"));
			$pass = $U->setTempPassword();
			$this->view->assign("first_name", $U->getFirstName());
			$this->view->assign("email", $U->getEmail());
			$this->view->assign("temp_password", $pass);
			$this->sendEmail("dbdAdmin", "noreply@dbdmvc.com", $U->getEmail(), "TEMP PASSWORD", "_forgotPass.tpl");
			$this->setTemplate("forgotPassThanks.tpl");
		}
		catch (dbdAdminException $e)
		{
			$this->e($e);
			$this->doForgotPass();
		}
	}

	public function doHome()
	{
		$this->checkLogin();
		$this->setTemplate("home.tpl");
	}

	public function doProfile()
	{
		$this->checkLogin();
		$this->view->assign("user", $this->user->getData());
		$this->setTemplate("userProfile.tpl");
	}

//	public function doUpdateProfile()
//	{
//		$this->checkLogin();
//
//		try
//		{
//			dbdAdminException::ensure($this->user->getPassword() == dbdUser::hash($cur), dbdAdminException::AUTH_MISMATCH);
//			dbdAdminException::ensure($new == $confirm, dbdAdminException::USER_PASS_CONFIRM_MISMATCH);
//			$U->save(array(dbdUser::TABLE_FIELD_EMAIL => $this->getParam("email"), SRUser::TABLE_FIELD_PASS => $this->getParam("pass")));
//			$U = dbdUser::getUserByEmail($this->getParam("email"));
//			$pass = $U->setTempPassword();
//			$this->view->assign("first_name", $U->getFirstName());
//			$this->view->assign("email", $U->getEmail());
//			$this->view->assign("temp_password", $pass);
//			$this->sendEmail("dbdAdmin", "noreply@dbdmvc.com", $U->getEmail(), "TEMP PASSWORD", "_forgotPass.tpl");
//			$this->setTemplate("forgotPassThanks.tpl");
//		}
//		catch (dbdAdminException $e)
//		{
//			$this->e($e);
//			$this->doForgotPass();
//		}
//		$this->view->assign("user", $this->user->getData());
//		$this->setTemplate("userProfile.tpl");
//	}

	public function doHash()
	{
		$this->noRender();
		echo dbdUser::hash($this->getParam("str"));
	}

//	public function doAddUser()
//	{
////		if (dbdMVC::getRequest()->get("REMOTE_ADDR") !== DBDIP)
////			$this->forward();
//		$this->noRender();
//		$U = new dbdUser();
//		$U->save(array(dbdUser::TABLE_FIELD_EMAIL => $this->getParam("email"), SRUser::TABLE_FIELD_PASS => $this->getParam("pass")));
//		echo "done!";
//	}
}
?>