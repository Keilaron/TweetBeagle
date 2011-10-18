<?php
/**
 * Tweet distillery.
 * 
 * Theory:
 * Takes stored tweet entities and tweets and indexes and weighs, herding the tweets
 * - Grab 1000 unindexed tweets_entities
 * - Loop through entities
 *		- If it's a shortened link, find out what the true link is
 *		- If the tag already exists, make an entry into tweets_tags to the existing tag
 *		- If the tag doesn't exist, make a new tags row and a new tweets_tags row pointing to that new reference
 *		- Weigh the reference, based on rules (discussion)
 *		- Preg replace the content from the original tweet and pass its text into the term extractor
 *		- Set the tweets to indexed
 *
 * 
**/

namespace Fuel\Tasks;

include('pid.class.php');

class Distillery
{
	public function run()
	{
		// #&*$!!!
		while (ob_get_contents() !== FALSE)
			ob_end_flush();
	
		// Make sure I'm not already running
		$pid = new pid();
		if ($pid->already_running) die(1);
		
		$output = posix_isatty(STDOUT);
		
		// Grab unindexed tweets and their entities
		$unindexed_sql = "SELECT te.tweet_id, te.entity_body, tw.* from tweets_entities as te JOIN tweets as tw ON te.tweet_id = tw.id WHERE (tw.indexed is NULL OR tw.indexed = 0) ORDER BY te.tweet_id DESC LIMIT 1000";

		$unindexed_results = \DB::query($unindexed_sql)->execute();
		
		// for each result, loop through the entities and pull the data
		foreach($unindexed_results as $tweets_entities)
		{
			if ($output) echo "Working on tweet ",$tweets_entities['tweet_id'],"\n";
			$tweet_text = $tweets_entities['text'];
		
			if($entities = json_decode($tweets_entities['entity_body']))
			{
				// loop through user mentions
				foreach ($entities->user_mentions as $user_mention)
				{
					if($user_mention != "")
					{
						$type = 'mention';
						$content = $user_mention->screen_name;

						// Check if I already exist in the tags table
						$result = \DB::query("SELECT id from tags WHERE content = '".mysql_real_escape_string($content)."'")->execute();
				
						if (!$result->count())
						{
							// It wasn't found, add it to the tweets_tags and tags tables
							$tags_insert = \DB::insert('tags')->set(array(
								'content' => $content,
								'type' => $type,
							))->execute();

						} else {
							$tags_insert = $result;
						}
				
						// Weigh this
						$weight = $this->get_weight($tweets_entities['tweet_id'], $content);
				
						$tt_insert = \DB::insert('tweets_tags')->set(array(
							'tweet_id' => $tweets_entities['tweet_id'],
							'tag_id' => $tags_insert[0],
							'weight' => $weight
						))->execute();
						
						// Remove $content from tweet text for term extraction
						$tweet_text = str_replace($content, '', $tweet_text);
					}
				}
		
				// loop through hashtags
				foreach ($entities->hashtags as $hashtag)
				{
					if($hashtag != "")
					{
						$type = 'hashtag';
						$content = $hashtag->text;
				
						// Check if I already exist in the tags table
						$result = \DB::query("SELECT id from tags WHERE content = '".mysql_real_escape_string($content)."'")->execute();
				
						if (!$result->count())
						{
							// It wasn't found, add it to the tweets_tags and tags tables
							$tags_insert = \DB::insert('tags')->set(array(
								'content' => $content,
								'type' => $type,
							))->execute();

						} else {
							$tags_insert = $result;
						}
							
						// Weigh this
						$weight = $this->get_weight($tweets_entities['tweet_id'], $content);

						$tt_insert = \DB::insert('tweets_tags')->set(array(
							'tweet_id' => $tweets_entities['tweet_id'],
							'tag_id' => $tags_insert[0],
							'weight' => $weight
						))->execute();
						
						// Remove $content from tweet text for term extraction
						$tweet_text = str_replace($content, '', $tweet_text);
					}
				}
			
				// loop through urls
				foreach ($entities->urls as $url)
				{
					if ($url != "")
					{
						$type = 'link';
				
						// Extracts real url
						if(!$content = $this->extract_url($url->url))
							$content = $url->url;
					
						// Check if I already exist in the tags table
						$result = \DB::query("SELECT id from tags WHERE content = '".mysql_real_escape_string($content)."'")->execute();
				
						if (!$result->count())
						{
							// It wasn't found, add it to the tweets_tags and tags tables
							$tags_insert = \DB::insert('tags')->set(array(
								'content' => $content,
								'type' => $type,
							))->execute();

						} else {
							$tags_insert = $result;
						}
				
						// Weigh this
						$weight = $this->get_weight($tweets_entities['tweet_id'], $content);

						$tt_insert = \DB::insert('tweets_tags')->set(array(
							'tweet_id' => $tweets_entities['tweet_id'],
							'tag_id' => $tags_insert[0],
							'weight' => $weight
						))->execute();
						
						// Remove $content from tweet text for term extraction
						$tweet_text = str_replace($url->url, '', $tweet_text);
					}
				}
			}
			$tw_update = \DB::update('tweets')->set(array(
				'indexed' => true
			))->where('id', '=', $tweets_entities['tweet_id'])->execute();
			
			// Extract Terms from tweet_text
			$terms = \Terms::extract(html_entity_decode($tweet_text, ENT_QUOTES, 'UTF-8'));
			foreach ($terms as $term)
			{
				//$term = htmlentities($term, ENT_QUOTES, 'UTF-8');
				// Make sure I'm a valid term
				// Strip special characters
				//$term = preg_replace("/[^a-z0-9 ]/", "", $term);
				
				if (strlen($term) > 3)
				{
					// Check if I already exist
					$result = \DB::query("SELECT id from tags WHERE content = '".mysql_real_escape_string($term)."' AND type = 'term'")->execute();
				
					if (!$result->count())
					{
						// It wasn't found, add it to the tweets_tags and tags tables
						$tags_insert = \DB::insert('tags')->set(array(
							'content' => $term,
							'type' => 'term',
						))->execute();
					} else {
						$tags_insert = $result;
					}
					
					$tt_weight = pow(str_word_count($term), 2);
					// Sometimes too many words are picked up and the term weight goes crazy, limit it to 5^2
					if($tt_weight > 25)
					{
						$tt_weight = 25;
					}
				
					$tt_insert = \DB::insert('tweets_tags')->set(array(
						'tweet_id' => $tweets_entities['tweet_id'],
						'tag_id' => $tags_insert[0],
						'weight' => $tt_weight,
					))->execute();
				}
			}
		}
	}
	
	private function extract_url($url)
	{
		global $tweetbeagle_ua;
		$cl = curl_init();
		curl_setopt($cl, CURLOPT_URL, $url);
		curl_setopt($cl, CURLOPT_RETURNTRANSFER, 1); // Returns fetched page instead of outputting it
		curl_setopt($cl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($cl, CURLOPT_MAXREDIRS, 5); // RFC1945: A user agent should never automatically redirect a request more than 5 times
		curl_setopt($cl, CURLOPT_NOBODY, 1); // Do a HEAD request
		curl_setopt($cl, CURLOPT_FAILONERROR, TRUE); // Make HTTP statuses 400+ result in FALSE instead of a success
		curl_setopt($cl, CURLOPT_SSL_VERIFYPEER, FALSE); // Ignore SSL certs
		curl_setopt($cl, CURLOPT_CONNECTTIMEOUT, 10); // Connection (only) timeout
		curl_setopt($cl, CURLOPT_TIMEOUT, 30); // Transfer (and anything else) timeout
		curl_setopt($cl, CURLOPT_USERAGENT, $tweetbeagle_ua); // Required to not be treated as a spambot
		
		$result = curl_exec($cl); // We don't care about the actual page content
		if (is_string($result))
			$real_url = curl_getinfo($cl, CURLINFO_EFFECTIVE_URL);
		else
			$real_url = $url;
		
		curl_close($cl);
		return $real_url;
		/*
		ini_set('default_socket_timeout', 10);
		$result = @get_headers($url, 1);
		if ($result && array_key_exists('Location', $result))
		{
				if (is_array($result['Location']))
				{
					return $result['Location'][0];
				} else {
					return $result['Location'];
				}
		} else {
			return $url;
		}
		*/
	}
	
	private function get_weight($tweet_id, $content)
	{
		// Calculate weight based upon the following rules
		/**
			first mention for a specific term in the last 24 hrs: 1
			second mention for a specific term in the last 24 hrs: 0.5
			third mention for a specific term in the last 24 hrs: 0.25
			any further mentions in the last 24hrs: 0
			Re-tweet of a specific term: 0.75 (this is based of the fact that the tweet it self is marked as a retweet or a quote, time is not taken into consideration)
			
			// Get the tweeters ID
			$tweeter_id = SELECT tweeter_id FROM tweets where id = $tweet_id
			
			Count the number times this tag shows up from tweets from this user in the last 24 hours
			SELECT count(*) FROM tweets_tags as tt
			JOIN tweets as tw ON tt.tweet_id = tw.id
			WHERE tw.date_created < DATE - 1 day
			AND tw.tweeter_id = $tweeter_id
			
		*/
		
		// Initial weight 0
		$weight = 0;
		
		// Is this tweet a re-tweet?
		$retweet_sql = "SELECT retweet FROM tweets WHERE id = '".$tweet_id."'";
		
		$retweet = \DB::query($retweet_sql);
		
		if (!$retweet)
		{
			// I'm not a retweet, what am I worth based on the rules
		
			$recurring_sql = "SELECT count(*) FROM tweets_tags as tt
				JOIN tweets as tw ON tt.tweet_id = tw.id
				JOIN tags as t ON t.id = tt.tag_id
				WHERE tw.date_created < DATE_SUB(now(), INTERVAL - 1 DAY)
				AND tw.tweeter_id = (SELECT tweeter_id FROM tweets where id = '".$tweet_id."')
				AND t.content = '".mysql_real_escape_string($content)."'";
				
			$recurring = \DB::query($recurring_sql)->execute();
		
			if ($recurring <= 3)
			{
				// Some fun maths for exponential decay
				// 1 * (( inital - decay rate ) ^ (times - 1) )
				$weight += 1*((1-0.5)^($recurring-1)) ;
			}
		} else {
			$weight = 0.75;
		}
			
		return $weight;
	}
}

/* End of distillery.php */
