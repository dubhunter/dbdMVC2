<?php
class Contact extends XYZController
{
	public function doDefault()
	{
//		$this->registerSitemap();
		$this->setTemplate("contact.tpl");
	}

	public function doSend()
	{
		$this->assignAllParams();
		try
		{
			XYZException::hold();
			XYZException::ensure($this->getParam("name") != "", XYZException::CONTACT_NAME);
			XYZException::ensure($this->getParam("email") != "" && wmString::emailValid($this->getParam("email")), XYZException::CONTACT_EMAIL);
			XYZException::ensure($this->getParam("subject") != "", XYZException::CONTACT_SUBJECT);
			XYZException::ensure($this->getParam("message") != "", XYZException::CONTACT_MESSAGE);
			XYZException::release();
			$this->sendEmail($this->getParam("name"), $this->getParam("email"), self::EMAIL_CONTACT, "XYZ Contact: ".$this->getParam("subject"), "_contact.tpl");
			$this->setTemplate("contactThanks.tpl");
		}
		catch (XYZException $e)
		{
			$this->e($e);
			$this->setTemplate("contact.tpl");
		}
	}
}
?>
