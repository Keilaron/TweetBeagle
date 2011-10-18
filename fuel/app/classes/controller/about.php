<?php

/**
 * The About Controller.
 *
 * @package  app
 * @extends  Controller
 */
class Controller_About extends Controller_Template {

	/**
	 * The index action.
	 * 
	 * @access  public
	 * @return  void
	 */
	public function action_index()
	{
		$view = View::factory('about/index');
		$this->template->title = "About Tweet Beagle";
		$this->template->content = $view;
	}
}
