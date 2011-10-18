<?php

class Controller_Template extends Fuel\Core\Controller_Template
{
	protected $subnav = array();
	protected static $require_authentication = false;
	
	public function before ($data = null) 
  {
    parent::before();
    if (!TwitterAccount::isLoggedIn() && static::$require_authentication)
      Response::redirect('account');
  }
	
	public function after()
	{
		$this->template->app = Config::load('app');
		if (!empty($this->subnav))
			$this->template->subnav = $this->subnav;
		parent::after();
	}
}