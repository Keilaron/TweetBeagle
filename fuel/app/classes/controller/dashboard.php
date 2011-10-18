<?php

class Controller_Dashboard extends Controller_Template {
	/*
		protected $subnav = array(
		'collections' => 'See your collections',
		'collections/create' => 'Create a collection'
		);
	 */

	protected static $require_authentication = true;

	public function before() {
		parent::before();

		Asset::css('dashboard.css', array(), 'assets');
	}

	public function action_index() {
		$view = View::factory('dashboard/index');
		$this->template->content = $view;

		// grab user collections
		$account = Model_Account::find_current();
		$data['collections'] = $account->collections;
		
		// get public collections
		$pub_collections = Model_Collection::find('all', array('where' => array(array('public', '=', '1'))));

		$collections = array();

		foreach ($pub_collections as $collection)
		{
			$collections[$collection->id] = $collection->name;
		}

		$view->public_collections = $collections;

		// panes
		$view->collections = View::factory('collections/list', $data);
		$view->search = View::factory('search/search_pane');
	}

}

/* End of file dashboard.php */
