<?php

class Model_Collection extends Orm\Model {
	
	protected $_validation_errors = array();
	
	protected static $_belongs_to = array('account');
	protected static $_many_many  = array(
		'tweets' => array(
			'key_from' => 'id',
			'key_through_from' => 'collection_id', 
			'table_through' => 'collections_tweets',
			'key_through_to' => 'tweet_id', 
			'model_to' => 'Model_Tweet',
			'key_to' => 'id',
			'cascade_save' => true,
			'cascade_delete' => false
		),
		'omits' => array(
			'key_from' => 'id',
			'key_through_from' => 'collection_id', 
			'table_through' => 'collections_omits',
			'key_through_to' => 'tag_id', 
			'model_to' => 'Model_Tag',
			'key_to' => 'id',
			'cascade_save' => true,
			'cascade_delete' => false
		),
  );

	public function validate()
	{
		$validation = Validation::factory('model_collection');
		$validation->add('name', 'Collection name')->add_rule('required');
		$validation->add('type', 'Collection type')->add_rule('required')->add_rule('match_pattern', '/^list|search$/');
		
		// hacking the reference
		if ($this->type == 'list')
			$validation->add('reference', 'Twitter list')->add_rule('required');
		elseif ($this->type == 'search')
			$validation->add('reference', 'Search terms(s)')->add_rule('required')->add_rule('match_pattern', '/^(#[a-z0-9]+.*)|(.{5,})$/i');
		
		if ($validation->run($this->to_array()))
			return true;
		
		foreach ($validation->errors() as $key => $error)
		{
			$message = null;
			
			if ($error->callback == 'required')
				$message = ':label is required.';
				
			if ($key == 'type' && $error->callback == 'match_pattern')
				$message = ':label must be list or search.';
			
			if ($key == 'reference' && $error->callback == 'match_pattern')
				$message = ':label must be at least 5 characters long.';
			
			$this->_validation_errors[$key] = $error->get_message($message);
		}
		
		return false;
	}
	
	public function validation_errors()
	{
		return $this->_validation_errors;
	}
	
	public function indexed_tweet_count()
	{
		$count_sql = "SELECT COUNT(*) count
		              FROM collections_tweets ct
		              LEFT JOIN tweets t ON (t.id = ct.tweet_id)
		              WHERE (ct.collection_id = {$this->id} AND t.indexed = 1)";
		
		$result = DB::query($count_sql)->execute();
		return (int) $result[0]['count'];
	}
  
  /**
   * Find top ten tags for a given collection by tag type
   * @param Model_Collection The collection object which contains the ID of the
   *        list we need to access.
   * @param type The tag type we are looking for.
  **/
	public static function findTopTen (Model_Collection $collection, $tag_type, $when, array $filters = array(), array $hidden = array())
	{
		$da = new DataAggregator();
		$da->setTagType($tag_type)
			 ->setCollectionId($collection->id)
			 ->setFilters($filters)
			 ->setHidden($hidden)
			 ->setSince($when.' days ago');
		
		return $da->getTopTags();
	}
  
	/**
	 * Find the $limit tweets for a given collection
	 * @param Model_Collection The collection object which contains the ID of the
	 *        list we need to access.
	 * @param limit The number of tweets to return, defaults to 300
	**/
	public static function findRecentTweets(Model_Collection $collection, $limit = 300, $when, array $filters = array(), array $hidden = array())
	{
		$when = date('Y-m-d H:i:s', strtotime($when.' days ago UTC'));
		$filter_in = $filter_out = '';
		if (!empty($filters))
		{
			foreach ($filters as $fltr)
				$filter_in .= ' AND tweets.text LIKE "%'.mysql_real_escape_string($fltr->content).'%"';
		}
		$recent_tweets_sql = "SELECT
			tweets.text,
			tweets.created_at,
			tweeters.screen_name,
			tweeters.location,
			tweeters.profile_image_url
			FROM collections
			JOIN collections_tweets ON collections_tweets.collection_id = collections.id
			JOIN tweets             ON tweets.id = collections_tweets.tweet_id
			JOIN tweeters           ON tweets.tweeter_id = tweeters.id
			WHERE collections.id = '".$collection->id."'
				AND tweets.created_at >= '$when'
				$filter_in $filter_out
			ORDER BY tweets.created_at DESC
			LIMIT ".$limit;
		
		$recent_tweets = DB::query($recent_tweets_sql)->execute();
		
		// Work-around for bizzare issue with Fuel
		$return = array();
		foreach ($recent_tweets as $tweet)
			$return[] = $tweet;
		return $return;
	}
}

/* End of file collection.php */
