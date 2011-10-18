<?php

/**
 * Description of Collections_Rest
 *
 * @author bushra
 */
class Controller_CollectionsRest extends Controller_Rest
{
	/**
	 * An AJAX action that loads the recent tweets in the lower pane of the page
	 * @params 
	 */
	public function get_recent_tweets()
	{
		$view = View::factory('collections/recent_tweets');
		
		$filter_date     = Input::get_post('filter_last_x_days');
		$collection      = Model_Collection::find(Input::get_post('collection_id'));
		$filter_settings_ids = Input::get_post('filters', array());
		$filter_settings = array();
		
		foreach ($filter_settings_ids as $tag_id)
			$filter_settings[$tag_id] = Model_Tag::find($tag_id);
		
		$recent_tweets = Model_Collection::findRecentTweets($collection, 300, $filter_date, $filter_settings);
		
		$view->set('recent_tweets', $recent_tweets, false);
		$this->response($view->render());
	}
	
}

?>
