<?php

class Controller_Search extends Controller_Template {

	protected static $require_authentication = true;


	public function action_index()
	{
		$this->template->title = 'Search Twitter Realtime';
		$this->template->content = View::factory('search/index');
	}
	
	public function action_query($page)
	{
		View::$auto_encode = FALSE;
		$query = Input::get('query');
		
		$search_params = array(
			'rpp' => '50',
			'page' => $page,
		);
		
		$search = TwitterAccount::getCurrentUserAccount()->search($query, $search_params);
		
		$params = array();
		
		$params['results'] = $search;
		$params['page'] = $page;
		
		$this->template->title = 'Realtime Search Results for &raquo; '.htmlentities($query);
		$this->template->content = View::factory('search/query', $params);
	}
}

/* End of file search.php */
