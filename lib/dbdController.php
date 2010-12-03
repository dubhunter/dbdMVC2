<?php
/**
 * dbdController.php :: dbdController Class File
 *
 * @package dbdMVC
 * @version 1.12
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2009 by Don't Blink Design
 */

/**
 * Abstract Parent Controller Class
 * All controller classes must extend this class or a child of this class.
 * @todo Use Response object for output.
 * @package dbdMVC
 * @abstract
 * @uses dbdLoader
 * @uses dbdSmarty
 */
abstract class dbdController
{
	const WIN_TYPE_NORMAL = 0;
	const WIN_TYPE_JQUERY = 1;
	const WIN_TYPE_IFRAME = 2;
	const JS_PAGE_REFRESH = 'window.location.reload();';
	const JS_PARENT_PAGE_REFRESH = 'window.parent.location.reload();';
	const JS_MODAL_REFRESH = '$.ajaxModal.refresh();';
	const TPL_EMAIL_HTML = "emailHTML.tpl";
	const TPL_EMAIL_TEXT = "emailTEXT.tpl";
	protected static $title = "";
	/**
	 * @var dbdSession
	 */
	protected $session = null;
	/**
	 * @var dbdUser
	 */
	protected $user = null;
	protected $win_type = null;
	/**
	 * #@+
	 * @access private
	 */
	/**
	 * Application directory
	 * @var string
	 */
	private $app_dir = null;
	/**
	 * Template filename
	 * @var string
	 */
	private $template = null;
	/**
	 * Should dbdController attempt to render
	 * a template on destruction?
	 * @var boolean
	 */
	private $default_render = true;
	/**#@-*/
	/**
	 * #@+
	 * @access protected
	 */
	/**
	 * Instance of dbdRouter
	 * @var dbdRouter
	 */
	protected $router = null;
	/**
	 * Instance of some model.
	 * <b>Note:</b> May not be used.
	 * @var object
	 */
	protected $model = null;
	/**
	 * Instance of dbdSmarty
	 * @var dbdSmarty
	 */
	protected $view = null;
	/**#@-*/
	/**
	 * Prepare controller for action.
	 * Set the router and app_dir, create the view, and then call init().
	 * <b>Note:</b> The constructor is declared final to prevent corruption of object.
	 * Any further construction needed by child classes
	 * can be accomplished by overriding dbdController::init().
	 * @final
	 * @param dbdRouter $router
	 * @param string $app_dir
	 */
	final public function __construct(dbdRouter $router, $app_dir)
	{
		$this->app_dir = $app_dir;
		$this->router = $router;
		$this->view = new dbdSmarty();
		$this->view->assign("app_name", dbdMVC::getAppName());
		$page_class = $this->getController()." ".($this->getAction() ? $this->getAction() : dbdDispatcher::DEFAULT_ACTION);
		$this->view->assign("page_class", $page_class);
		$this->view->assign("page_url", $this->getURL());
		$this->view->assign("page_url_params", dbdURI::replace($this->getURL(true), null, null, array("_" => null)));
		$this->view->assign("this", array(
			"controller" => $this->getController(),
			"action" => $this->getAction() ? $this->getAction() : dbdDispatcher::DEFAULT_ACTION
		));

//		$this->view->addJSVar("controller", $this->getController());
//		$this->view->addJSVar("action", $this->getAction() ? $this->getAction() : "");
//		$this->view->addJSVar("params", $this->getParams());

		$this->view->assign_by_ref("page_title", self::$title);
//		$this->view->assign_by_ref("tabindex", self::$tabindex);
//		$this->view->assign_by_ref("fid", self::$field_id);
		$this->determineWinType();

//		$this->view->config_load("errorMsgs.conf");
//		dbdException::setMsgArray($this->view->get_config_vars());
//		$this->view->clear_config();
//
//		try
//		{
//			$this->session = new dbdSession(dbdMVC::getRequest()->getQuery("PHPSESSID", null));
//			$session = $this->session->getParams();
//			if ($this->session->isLoggedIn())
//			{
//				$this->user = $this->session->getUser();
//				$session['access'] = $this->user->getAccess();
//			}
//			$this->view->assign("session", $session);
//		}
//		catch (PDOException $e)
//		{
//			dbdLog(__CLASS__.": Cannot start Session (".dbdDB::getInstance()->errorCode().")");
////			dbdLog($e);
//		}

		if (DBD_MVC_CLI) $this->noRender();
		$this->init();
	}
	/**
	 * Conclude the controller action.
	 * Call dnit().
	 * <b>Note:</b> The destructor is declared final to prevent corruption of object.
	 * Any further destruction needed by child classes
	 * can be accomplished by overriding dbdController::dnit().
	 * @final
	 * @param dbdRouter $router
	 * @param string $app_dir
	 */
	final public function __destruct()
	{
		$this->dnit();
	}
	/**
	 * Auto render the view.
	 * Can be disabled by using $this->noRender();
	 * If a template has not been set, it will attempt to convert
	 * the controller name into a template filename.
	 * @final
	 * @throws dbdException
	 */
	final public function autoRender()
	{
		if (!$this->view->wasRendered() && $this->default_render)
		{
			if (!$this->template)
			{
				$this->template = preg_replace("/^([A-Z]{1})(.*)$/e", "strtolower('$1').$2", $this->getController()).".tpl";
				if (!$this->view->template_exists($this->template))
					throw new dbdException("Page (".$this->getController().") could not be found!", 404);
			}
			if (!$this->view->template_exists($this->template))
				throw new dbdException("View (".$this->template.") could not be found!", 404);
			$this->view->display($this->template);
		}
	}
	/**
	 * Auto execute method to be executed after the action.
	 * Can be overridden for additional automatic functionality
	 * (maybe assign some variable to the view).
	 * <b>Note:</b> If overriden, parent::autoExec(); must be
	 * called to maintain current functionality!
	 */
	public function autoExec()
	{
		$this->autoRender();
		if ($this->win_type != self::WIN_TYPE_IFRAME && $this->session !== null)
			$this->session->logLanding($this->getController(), $this->getAction(), $this->getParams(), self::$title, $this->win_type);
	}
	/**
	 * Disable auto render on destruction.
	 */
	public function noRender()
	{
		$this->default_render = false;
	}
	/**
	 * Default init method.
	 * This method is to be overridden for additional
	 * object construction and initialization
	 * (maybe setting the $model property).
	 * @access protected
	 */
	protected function init()
	{}
	/**
	 * Default dnit method.
	 * This method is to be overridden for additional
	 * object destruction and de-initialization
	 * (maybe cleaning up the $model property).
	 * @access protected
	 */
	protected function dnit()
	{}
	/**
	 * Default action method.
	 * Can be preformed without the presense of a child controller.
	 */
	public function doDefault()
	{}
	/**
	 * #@+
	 * @access protected
	 */
	/**
	 * Set template filename.
	 * @param string $tpl
	 */
	protected function setTemplate($tpl)
	{
		$this->template = $tpl;
	}
	/**
	 * Get template filename.
	 * @return string
	 */
	protected function getTemplate()
	{
		return $this->template;
	}
	/**
	 * Assign all request parameters to the view as an associative array.
	 * <b>Note:</b> Slashes are stripped.
	 */
	protected function assignAllParams()
	{
		$this->view->assign($this->getParams());
	}

	protected function e(dbdException $e)
	{
		$errors = array();
		if ($e instanceof dbdHoldableException)
		{
			foreach ($e->getHeld() as $he)
				$errors[] = $he->getMessage();
		}
		if (empty($errors))
			$errors[] = $e->getMessage();
		$this->view->assign("errors", $errors);
	}

	protected function determineWinType()
	{
		switch (true)
		{
			case ($this->router->getParam("HTTP_X_REQUESTED_BY") == "jqueryAjax"):
				$this->win_type = self::WIN_TYPE_JQUERY;
				break;
			case $this->getParam("iframe"):
				$this->win_type = self::WIN_TYPE_IFRAME;
				break;
			default:
				$this->win_type = self::WIN_TYPE_NORMAL;
		}
		$this->view->assign("win_type", $this->win_type);
	}

	protected function ensureWinType($type = self::WIN_TYPE_NORMAL)
	{
		if (!is_array($type))
			$type = array($type);
		if (!in_array($this->win_type, $type))
			$this->forward();
		return true;
	}

	protected function script($script)
	{
		echo "<script type=\"text/javascript\">".$script."</script>";
	}
	/**
	 * Preform an HTTP Location change.
	 * Disables auto render.
	 * @param string $url
	 */
	protected function forward($url = "/", $noRender = true)
	{
		switch ($this->win_type)
		{
			case self::WIN_TYPE_IFRAME:
				$this->script("parent.location.assign('".$url."');");
				if ($noRender)
					$this->noRender();
				break;
			case self::WIN_TYPE_JQUERY:
				$this->script("window.location.assign('".$url."');");
				if ($noRender)
					$this->noRender();
				break;
			default:
				header("Location: ".$url);
				$this->noRender();
				exit;
		}
	}
	/**
	 * Get application directory.
	 * @return string
	 */
	protected function getAppDir()
	{
		return $this->app_dir;
	}
	/**
	 * Get controller name.
	 * @return string
	 */
	protected function getController()
	{
		return $this->router->getController();
	}
	/**
	 * Get action name.
	 * @return string
	 */
	protected function getAction()
	{
		return $this->router->getAction();
	}
	/**
	 * Set request parameter.
	 * @param string $name
	 * @param mixed value
	 */
	protected function setParam($name, $value)
	{
		return $this->router->setParam($name, $value);
	}
	/**
	 * Unset request parameter.
	 * @param string $name
	 */
	protected function unsetParam($name)
	{
		return $this->router->unsetParam($name);
	}
	/**
	 * Get request parameter.
	 * @param string $name
	 * @return mixed
	 */
	protected function getParam($name)
	{
		return $this->router->getParam($name);
	}
	/**
	 * Get all parameters.
	 * @see dbdRouter::getParams()
	 * @return array
	 */
	protected function getParams()
	{
		return $this->router->getParams();
	}
	/**
	 * Get current request url.
	 * If optional flag is passed, parameters are included.
	 * @param boolean $get_params
	 * @param boolean $host
	 * @return string
	 */
	protected function getURL($get_params = false, $host = false)
	{
		return ($host ? "http://".$this->router->getParam("HTTP_HOST") : "").$this->router->getURL($get_params);
	}

	protected function downloadFile($file, $name = null, $type = null)
	{
		$this->noRender();
		$path = DBD_ASSET_DL_DIR.$file;
		header("Pragma: public"); // required
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false); // required for certain browsers
		header("Content-Type: ".($type ? $type : wmFileSystem::mimeType($path)));
		header("Content-Length: ".filesize($path));
		header("Content-Disposition: attatchment; filename=".($name ? $name : $file));
		readfile($path);
	}

	protected function sendEmail($from_name, $from_address, $to_address, $subject, $tpl, $attachments = array(), $cc_address = false, $hdrs = array())
	{
		$this->view->assign("tpl", $tpl);
		$hdrs['From'] = "\"".$from_name."\" <".$from_address.">";
		$to_address = preg_replace("/[;, \n]+/", ", ", $to_address);
		$hdrs['To'] = $to_address;
		if ($cc_address)
			$hdrs['Cc'] = $cc_address;
		$hdrs['Subject'] = $subject;
		dbdLoader::load(PEAR_DIR.DBD_DS."Mail".DBD_DS."mail.php");
		dbdLoader::load(PEAR_DIR.DBD_DS."Mail".DBD_DS."mime.php");
		$mime = new Mail_mime("\n");
		$this->view->assign("tpl", $tpl);
		$mime->setHTMLBody($this->view->fetch(self::TPL_EMAIL_HTML, null, null, false, true));
		$mime->setTXTBody($this->view->fetch(self::TPL_EMAIL_TEXT, null, null, false, true, true));
		if (is_array($attachments))
		{
			foreach($attachments as $attachment)
				$mime->addAttachment($attachment, wmFileSystem::mimeType($attachment), basename($attachment));
		}
		$body = $mime->get();
		$hdrs = $mime->headers($hdrs);
		$mail =& Mail::factory("sendmail");
		$mail->send($to_address, $hdrs, $body);
		if ($cc_address)
			$mail->send($cc_address, $hdrs, $body);
	}
	/**
	 * Get the progress information on a current upload via APC.
	 * @uses php-pecl-apc
	 * @link http://us.php.net/manual/en/apc.configuration.php#ini.apc.rfc1867
	 * @param string $id
	 * @return array
	 */
	protected function getUploadInfo($id)
	{
		if (function_exists("apc_fetch") && ini_get("apc.rfc1867"))
		{
			$a = apc_fetch("upload_".$id);
			if ($a)
			{
				$a['rate'] = $a['current'] / (microtime(true) - $a['start_time']);
				$a['est_sec'] = round(($a['total'] - $a['current']) / $a['rate']);
			}
			return $a;
		}
		else if (function_exists("uploadprogress_get_info"))
		{
			return uploadprogress_get_info($id);
		}
		throw new dbdException("APC not installed or misconfigured! Cannot get upload progress!");
	}
	/**#@-*/
}
?>