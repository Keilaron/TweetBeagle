<?php

namespace Fuel\Tasks;

class Farm {
	
	public static function run()
	{
		// #&*$!!!
		while (ob_get_contents() !== FALSE)
			ob_end_flush();
		$collections = \Model_Collection::find()->order_by('updated_at', 'asc')->limit(50)->get();
		$harvester = new \Harvester($collections);
		$harvester->harvest();
	}
	
	/**
	 * API seems to be gone, even though documented...?
	 * php oil r farm:massadd owner_id list_id screenname,screen_name_two,foo,bar
	 */
	public static function massadd($user_id, $list, $who)
	{
		$user = \Model_Account::find($user_id);
		$twitter = \TwitterAccount::getUserAccount($user->oauth_key, $user->oauth_secret);
		$url = $user_id.'/'.$list.'/create_all';
		$params = array(
			'screen_name' => $who
		);
		echo 'Requesting ',$url,"\n";
		
		$twitter->connection->decode_json = FALSE;
		var_dump($twitter->connection->post($url, $params));
		echo 'HTTP status ',$twitter->connection->http_code,"\n";
	}
	
	/**
	 * Rebuilds "manually"-created entities, usually from harvesting searches.
	 * php oil refine farm:repairSearchEntities
	 */
	public static function repairSearchEntities($which = NULL)
	{
		// #&*$!!!
		while (ob_get_contents() !== FALSE)
			ob_end_flush();
		
		if ($which)
		{
			$tweet_ids = explode(',', $which);
			if (empty($tweet_ids))
				die('No search tweets.');
		}
		else
		{
			// The "IS NULL"s here are to select tweets that originate from searches
			$findSearchTweets = 'SELECT id FROM `tweets` WHERE favorited IS NULL AND
				user_location IS NULL AND user_followers_count IS NULL AND
				user_friends_count IS NULL AND user_verified IS NULL';
			
			$results = \DB::query($findSearchTweets)->execute();
			
			$tweet_ids = array();
			foreach ($results as $row)
				$tweet_ids[] = $row['id'];
			if (empty($tweet_ids))
				die('No search tweets.');
		}
		
		// Load tweets, go through tweet texts to determine mentions
		$query = \Model_Tweet::find()->where('id', current($tweet_ids));
		while (next($tweet_ids))
			$query->or_where('id', current($tweet_ids));
		reset($tweet_ids);
		$tweets = $query->get();
		if (empty($tweets))
			die('Found nothing.');
		
		// We need to ask Twitter some things, so connect to it.
		echo 'FIXME: Loading first user instead of applicable user(s). Results may not be optimal.',"\n";
		$user = \Model_Account::find('first');
		$twitter = \TwitterAccount::getUserAccount($user->oauth_key, $user->oauth_secret);
		
		// Load users from mentions
		$tweeters = array();
		$usernames = \Harvester::extractTwits($tweets);
		$query = \Model_Tweeter::find()->where('screen_name', current($usernames));
		while (next($usernames))
			$query->or_where('screen_name', current($usernames));
		reset($usernames);
		$tweeters = $query->get();
		foreach ($tweeters as $twtr)
			unset($usernames[array_search(strtolower($twtr->screen_name), array_map('strtolower', $usernames))]);
		
		if (!empty($usernames))
		{
			$harv_tweeters = \Harvester::getTwits($twitter, $usernames, $dummy = array(), FALSE);
			$harv_tweeters = $harv_tweeters['users']; // The raw_tweets are useless here.
			foreach ($harv_tweeters as $twtr)
			{
				try // FIXME: For some reason, this try-catch block (almost identical to harvester's) doesn't work here.
				{
					$twtr->save(); // Ideally, we'd check first (somehow) if it was modified, but.. point is to have everything saved.
				}
				catch (Database_Exception $ex)
				{
					// Ignore duplicate tweets
					if ($ex->getCode() != \Harvester::MYSQL_ERR_DUPLICATE)
						throw $ex;
				}
				$tweeters[] = $twtr;
			}
			unset($harv_tweeters);
		}
		
		// Parse entities
		foreach ($tweets as $twt)
		{
			$entities = \Model_Tweets_Entities::find()->where('tweet_id', $twt->id)->get();
			if (empty($entities))
			{
				$entities = new \Model_Tweets_Entities();
				$entities->tweet_id = $twt->id;
			}
			else
				$entities = current($entities);
			$entities->entity_body = json_encode($twt->id, \Harvester::parseEntities($twt->text, $tweeters));
			$entities->save();
		}
		
		// Get tweets_tags and delete them
		$query = \Model_Tweets_Tags::find()->where('id', current($tweet_ids));
		while (next($tweet_ids))
			$query->or_where('id', current($tweet_ids));
		reset($tweet_ids);
		$tt = $query->get();
		if (!empty($tt))
			foreach ($tt as $rel)
				$rel->delete();
		
		// Mark tweets as not indexed
		foreach ($tweets as $twt)
		{
			$twt->indexed = NULL;
			$twt->save();
		}
		
		echo 'Done. Recreated entities for '.count($tweets).' tweets.';
	}
}
