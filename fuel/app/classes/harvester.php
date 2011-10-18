<?php

/**
 * Tweet harvester.
 * 
 * Theory:
 * - Load the collections handed by the manager
 * - Iterate through collections:
 * - - Do API call to Twitter using that user's ID -- if there's no last update, get X number
 * - - - If that fails due to access being revoked, disable all that user's collections
 * - - - If that fails for any other reason, skip this collection
 * - - Insert tweets into DB
 * - - - If ID is duplicate, ignore it
 * - - - If this fails for any reason, update last success based on last inserted tweet
 * - Update collection's last update time
**/
class Harvester
{
	const DEBUG_MODE        = FALSE;
	
	const EXCODE_UNKNOWN          = 0; // Actually, an error code of 0 is the default.
	const EXCODE_UNKNOWN_RESPONSE = 1; // Unexpected response from Twitter
	
	const LEVEL_DEBUG       = 0;
	const LEVEL_NOTICE      = 1;
	const LEVEL_WARNING     = 2;
	const LEVEL_ERROR       = 3;
	
	const MAX_RETRIES       = 2; // +1 = 3 attempts total per collection
	const REQUIRED_RLS      = 10; // Required number of Rate Limited Requests
	const USER_LOOKUP_LIMIT = 100; // @see http://dev.twitter.com/doc/get/users/lookup
	
	const MYSQL_ERR_DUPLICATE = 1062;
	const MYSQL_DATE = 'Y-m-d';
	const MYSQL_TIME = 'H:i:s';
	const MYSQL_DT   = 'Y-m-d H:i:s';
	const MYSQL_TS   = 'Y-m-d H:i:s';
	
	const REGEX_HASHTAG     = '/#[[:word:]]+/';
	const REGEX_MENTION     = '/@[[:word:]]+/';
	const REGEX_URL         = "/((?#
		the scheme:
	)(?:https?:\\/\\/)(?#
		second level domains and beyond:
	)(?:[\S]+\.)+((?#
		top level domains:
	)MUSEUM|TRAVEL|AERO|ARPA|ASIA|EDU|GOV|MIL|MOBI|(?#
	)COOP|INFO|NAME|BIZ|CAT|COM|INT|JOBS|NET|ORG|PRO|TEL|(?#
	)A[CDEFGILMNOQRSTUWXZ]|B[ABDEFGHIJLMNORSTVWYZ]|(?#
	)C[ACDFGHIKLMNORUVXYZ]|D[EJKMOZ]|(?#
	)E[CEGHRSTU]|F[IJKMOR]|G[ABDEFGHILMNPQRSTUWY]|(?#
	)H[KMNRTU]|I[DELMNOQRST]|J[EMOP]|(?#
	)K[EGHIMNPRWYZ]|L[ABCIKRSTUVY]|M[ACDEFGHKLMNOPQRSTUVWXYZ]|(?#
	)N[ACEFGILOPRUZ]|OM|P[AEFGHKLMNRSTWY]|QA|R[EOSUW]|(?#
	)S[ABCDEGHIJKLMNORTUVYZ]|T[CDFGHJKLMNOPRTVWZ]|(?#
	)U[AGKMSYZ]|V[ACEGINU]|W[FS]|Y[ETU]|Z[AMW])(?#
		the path, can be there or not:
	)(\\/[a-z0-9\._\\/~%\-\+&\#\?!=\(\)@]*)?)/i";
	
	const RETURN_NO_QUEUE   = -1; // No collections given or none could be loaded
	const RETURN_NO_ERROR   =  0; // A-OK
	const RETURN_FATAL      =  1; // Fatal issue e.g. Twitter is down
	const RETURN_ERRORS     =  2; // Some collections failed
	const RETURN_ALL_ERRORS =  3; // All collections failed
	
	/** Array of collections to fetch */
	protected $cols     = array();
	protected $colIDs   = array();
	protected $errored  = 0;
	/** Cache of Aggregator users */
	protected $accounts = array();
	/** Aggregator users that have gone over their limit */
	protected $autoSkip = array();
	/** Cache of Twitter users */
	protected $tweeters = array();
	/** Error handling */
	protected static $messages = array();
	protected $handler  = NULL;
	protected static $instances = 0;
	
	/**
	 * Constructor.
	 * @see add
	 */
	public function __construct(array $collections = array())
	{
		self::$instances++;
		$this->handler = set_error_handler(array(get_class(), 'handle'));
		if (self::$instances == 1)
			register_shutdown_function(array(get_class(), 'handleDeath'));
		if ($collections)
			$this->add($collections);
	}
	
	public function __destruct()
	{
		self::$instances--;
		if (!self::$instances && $this->handler) set_error_handler($this->handler);
		if (self::$messages) self::sendMail();
	}
	
	/**
	 * Add collections to be fetched from Twitter.
	 * @param mixed collections Can be one of:
		- A collection object or object array.
		It is expected that properties such as the account_id to have been set already.
		This option is useful for creating new collections, as the collections are saved after being fetched.
		- An integer or integer array; The collection objects will be automatically fetched from the database.
		- Important: Do NOT mix integers and objects in the array.
	 */
	public function add($collections)
	{
		if (self::DEBUG_MODE)
			self::out('Adding collections: '.json_encode($collections), self::LEVEL_DEBUG);
		
		if (is_object($collections))
			$this->cols[] = $collections;
		elseif (is_array($collections))
		{
			$peek = current($collections);
			if (is_object($peek))
				$this->cols = array_merge($this->cols, $collections);
			else
				$this->colIDs = array_merge($this->colIDs, $collections);
		}
		else
			$this->colIDs[] = $collections;
	}
	
	/**
	 * Set an account's collections to be updated later,
	 * and add that account to the list of accounts to skip in this run.
	 * @param integer account_id The account ID to affect
	 * @param integer when The time to set the updated_at to. (time() + 3600 is a good bet.)
	 */
	public function burnAccount($account_id, $when)
	{
		$this->autoSkip[] = $account_id;
		$when = self::formatDBDate($when);
		DB::update('collections')->set(array('updated_at' => $when))->where('account_id', '=', $account_id)->execute();
		if (mysql_errno()) // Query may return 0 due to no affected rows
			self::out('Could not update user\'s ('.$account_id.') collections to '.$when.'! '.mysql_error(), self::LEVEL_ERROR);
		else
			self::out('Set user\'s ('.$account_id.') collections to be updated at '.$when, self::LEVEL_DEBUG);
	}
	
	/**
	 * Finds @mentioned usernames for lookup via getTwits() before parseEntities() is used.
	 * @param Tweet[] tweets Tweet objects to search through.
	 * @return string[] Usernames found in @mentions.
	 * @see getTwits
	 * @see parseEntities
	 */
	public static function extractTwits(&$tweets)
	{
		$usernames = array();
		foreach ($tweets as $twt)
		{
			// See parseEntities() for explanation of this code.
			$matches = array();
			if (preg_match_all(self::REGEX_MENTION, $twt->text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE))
				foreach ($matches as $match)
					$usernames[] = substr($match[0][0], 1); // Remove @
		}
		return $usernames;
	}
	
	/**
	 * TODO: document me
	 * Note: users get automatically saved and added to cache
	 * expected that $usernames is an array(search_id => screen_name) unless useIDs = false
	 */
	public static function getTwits($twitter, array $usernames, &$cache = array(), $useIDs = TRUE, $noRecursion = FALSE)
	{
		$results = array(
			'users' => array(),
			'raw_tweets' => array(),
		);
		
		try
		{
			while (count($usernames) > self::USER_LOOKUP_LIMIT)
			{
				$nested_lookup = array_slice($usernames, 0, self::USER_LOOKUP_LIMIT - 1, TRUE);
				foreach ($nested_lookup as $which => $dummy)
					unset($usernames[$which]);
				$results = array_merge_recursive($results, self::getTwits($twitter, $nested_lookup, $cache, $useIDs));
				sleep(1); // Don't do this too fast/too often.
			}
			if (empty($usernames))
				return $results;
			self::out('Mass-getting users from search: '.json_encode($usernames), self::LEVEL_DEBUG);
			$response = $twitter->getUsersDetails(NULL, $usernames);
			
			if ($response)
			{
				// Check if we got an error reponse
				// They can manifest in these forms:
				// { "errors": [ { "code": int, "message": str } ... ] }
				// { "error": str, "request": str }
				if (is_object($response))
				{
					if (!empty($response->errors))
					{
						if ($response->errors[0]->code == 17) // "No user matches for specified terms"
							return array();
					}
					$ex = new UnexpectedValueException('Unexpected response from Twitter: '.json_encode($response), self::EXCODE_UNKNOWN_RESPONSE);
					throw $ex;
				}
				// We're okay, no error
				foreach ($response as $twit)
				{
					if (!$twit || !is_object($twit))
					{
						self::out('No tweeters? '.json_encode($twit), self::LEVEL_ERROR);
						continue;
					}
					$user = NULL;
					self::parseUser($twit, $user, $cache, $useIDs ? array_search(strtolower($twit->screen_name), array_map('strtolower', $usernames)) : NULL);
					$results['users'][$user->id] = $user;
					if (!empty($twit->status)) // Never tweeted or is protected
						$results['raw_tweets'][$twit->status->id] = $twit->status;
					
					// Count them in.
					unset($usernames[array_search($twit->screen_name, $usernames)]);
				}
				// Did we miss any? Try again, once.
				// .. sadly, this doesn't seem to really work.
				//if (!empty($usernames) && !$noRecursion)
				//	$results = array_merge_recursive($results, self::getTwits($twitter, $usernames, $cache, $useIDs, TRUE));
			}
			elseif (!$noRecursion)
			{
				// We've probably been hammering Twitter. Take a catnap, then try again.
				sleep(5);
				$results = array_merge_recursive($results, self::getTwits($twitter, $usernames, $cache, $useIDs, TRUE));
			}
			else
				throw new UnexpectedValueException('Response is empty: '.json_encode($response), self::EXCODE_UNKNOWN_RESPONSE);
		}
		catch (Exception $ex)
		{
			$msg = 'Failed to mass-get users! ('.$ex->getCode().'@'.Fuel::clean_path($ex->getFile()).':'.$ex->getLine().': '.$ex->getMessage().')';
			self::out($msg, self::LEVEL_ERROR);
		}
		
		return $results;
	}
	
	public static function formatDBDate($time, $format = self::MYSQL_TS)
	{
		return date($format, $time);
	}
	
	public static function handle($errno, $errstr, $errfile = NULL, $errline = NULL)
	{
		// If this error is supposed to be ignored, .. well, ignore it.
		if (($errno & error_reporting()) == 0) return;
		// What kind of error is this?
		switch ($errno)
		{
		case E_ERROR:
		case E_USER_ERROR:
		case E_RECOVERABLE_ERROR:
			$type = self::LEVEL_ERROR;
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$type = self::LEVEL_WARNING;
			break;
		case E_NOTICE:
		case E_USER_NOTICE:
		case E_STRICT:
		case E_DEPRECATED:
		case E_USER_DEPRECATED:
			$type = self::LEVEL_NOTICE;
			break;
		}
		// Handle the output of this error
		self::out("PHP: $errstr ($errline:$errfile)", $type, $errno == E_ERROR);
	}
	
	public static function handleDeath()
	{
		$error = error_get_last();
		// FIXME: One of these below does not work as expected.
		if ($error && ($error['type'] == E_ERROR) && self::$instances)
			self::handle($error['type'], $error['message'], $error['file'], $error['line']);
	}
	
	/**
	 * Harvests the collections given in the constructor or using ->add().
	 * @return int See the class constants. One important note is that a SUCCESS will return ZERO.
	 */
	public function harvest()
	{
		// Load any queued IDs
		if (!empty($this->colIDs))
		{
			$col_models = Model_Collection::find($this->colIDs);
			// ::find may return a single collection
			if (is_object($col_models) && $col_models instanceof Model_Collection)
				$col_models = array($col_models);
			// Now merge what we got with what we should work on
			if ($col_models && is_array($col_models))
				$this->cols = array_merge($this->cols, $col_models);
			elseif (!is_array($col_models))
				self::out('Got '.gettype($col_models).' from Collection::find instead of array. ('.var_export($col_models, TRUE).')', self::LEVEL_ERROR);
			$this->colIDs = array(); // On the off chance harvest() is called again.
			unset($col_models);
		}
		if (empty($this->cols))
			return self::RETURN_NO_QUEUE;
		$return = self::RETURN_NO_ERROR;
		
		foreach ($this->cols as $cl)
		{
			// Did they already go over their limit? Let's try to not piss off Twitter.
			if (in_array($cl->account_id, $this->autoSkip))
			{
				self::out('Skipping burned account '.$cl->account_id, self::LEVEL_DEBUG);
				$this->errored++;
				continue;
			}
			else
				self::out('Beginning work on collection '.$cl->id, self::LEVEL_DEBUG);
			
			// Get the user's key
			if (empty($this->accounts[$cl->account_id]))
				$this->accounts[$cl->account_id] = Model_Account::find($cl->account_id);
			$user = $this->accounts[$cl->account_id];
			
			if (!$user)
			{
				self::out('No user '.$cl->account_id.' for collection '.$cl->id, self::LEVEL_WARNING);
				$return = self::RETURN_ERRORS;
				$this->errored++;
				continue;
			}
			
			// Load user & connect to Twitter
			self::out('Loading user '.$user->id, self::LEVEL_DEBUG);
			$twitter = TwitterAccount::getUserAccount($user->oauth_key, $user->oauth_secret);
			
			// Check user's API rate limit
			$rls = $twitter->rate_limit_status();
			if (empty($rls) || empty($rls->remaining_hits) || empty($rls->reset_time_in_seconds))
			{
				self::out('Cannot get rate limit status for '.$cl->account_id.'!', self::LEVEL_ERROR);
				return self::RETURN_FATAL;
			}
			elseif ($rls->remaining_hits < self::REQUIRED_RLS)
			{
				self::out($cl->account_id.' hit rate limit ('.$rls->remaining_hits.' < '.self::REQUIRED_RLS.').', self::LEVEL_NOTICE);
				$this->burnAccount($cl->account_id, $rls->reset_time_in_seconds);
				continue;
			}
			else
				self::out($cl->account_id.' has '.$rls->remaining_hits.' hits left.', self::LEVEL_DEBUG);
			
			// Get the tweets -- if there's no last update, get X number
			$params = array();
			if (!empty($cl->since_id))
				$params['since_id'] = $cl->since_id;
			// TEST: Does default count get in the way of since_id? Is count even valid?
			$isSearch = FALSE;
			if ($cl->type == 'list')
			{
				self::out('Requesting list "'.$cl->reference.'" for user '.$user->id.' ('.json_encode($params).')', self::LEVEL_DEBUG);
				$tweets = $twitter->getListStatuses($user->id, $cl->reference, $params); // This already has a default count
			}
			else
			{
				$isSearch = TRUE;
				self::out('Requesting search "'.$cl->reference.'" for user '.$user->id.' ('.json_encode($params).')', self::LEVEL_DEBUG);
				$tweets = $twitter->search($cl->reference, $params);
			}
			
			// If this fails due to a timeout, wait a bit, then try again. If it still fails, consider it an error.
			$retries = 0;
			while ($twitter->connection->http_code === 0)
			{
				if ($retries == self::MAX_RETRIES)
					break;
				sleep(5);
				if (!$isSearch)
					$tweets = $twitter->getListStatuses($user->id, $cl->reference, $params);
				else
					$tweets = $twitter->search($cl->reference, $params);
				$retries++;
			}
			if (($twitter->connection->http_code === 0) && ($retries == self::MAX_RETRIES))
			{
				self::out('Cannot connect to Twitter! (Made '.($retries + 1).' attempts.)', self::LEVEL_ERROR);
				return self::RETURN_FATAL;
			}
			
			// If that fails due to being told to stop, either:
			// - set all of this user's collections to be updated at the end of this hour
			// - if this is a search, look at the Retry-After header to know when to resume
			if (($twitter->connection->http_code >= 400) && ($twitter->connection->http_code < 500) && ($twitter->connection->http_code != 404))
			{
				$resp = (empty($tweets) || empty($tweets->error)) ? '(unknown error)' : $tweets->error;
				self::out('Collection '.$cl->id.': User '.$cl->account_id.' received HTTP '.$twitter->connection->http_code.': '.$resp, self::LEVEL_WARNING);
				$when = empty($twitter->connection->http_header['retry_after']) ? (time() + 3600) : strtotime($twitter->connection->http_header['retry_after']);
				$this->burnAccount($cl->account_id, $when);
				$return = self::RETURN_ERRORS;
				$this->errored++;
				continue;
			}
			// If that fails due to failwhale, ...?
			elseif (($twitter->connection->http_code >= 500) && ($twitter->connection->http_code < 600))
			{
				if ($twitter->connection->http_code != 502) // Ignore Twitter down/upgrade code, it'll happen
					self::out('Twitter is not working properly! (Got '.$twitter->connection->http_code.' response)', self::LEVEL_WARNING);
				return self::RETURN_FATAL;
			}
			// If we get a 404 that means there's nothing since then.
			elseif ($twitter->connection->http_code == 404)
			{
				self::out('No new tweets in this collection.', self::LEVEL_DEBUG);
				$tweets = array();
			}
			// If that fails for any other reason, skip this collection
			elseif ($twitter->connection->http_code != 200)
				self::out('HTTP code '.$twitter->connection->http_code.' was returned by Twitter on collection '.$cl->id, self::LEVEL_NOTICE);
			
			if (!is_array($tweets) && !is_object($tweets))
			{
				self::out('Cannot iterate through $tweets for collection '.$cl->id.': '.json_encode($tweets), self::LEVEL_ERROR);
				$return = self::RETURN_ERRORS;
				$this->errored++;
				continue;
			}
			
			# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
			# Begin actual work on collection
			# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
			
			if ($isSearch)
			{
				// We only need the search results
				$tweets = $tweets->results;
				
				// We also need to know who all these people are, first.
				$lookup = $sid_relation = $raw_tweets = array();
				// Get all from_user_id and to_user_id fields
				foreach ($tweets as $twt)
				{
					if (empty($lookup[$twt->from_user_id]))
					{
						// tweeter_id => $twt->from_user_id
						$tweeter = Model_Tweeter::find()
							->where('screen_name', $twt->from_user)
							->or_where('search_id', $twt->from_user_id)
							->get();
						
						if ($tweeter)
						{
							$tweeter = current($tweeter);
							$sid_relation[$twt->from_user_id] = $tweeter->id;
						}
						else
							$lookup[$twt->from_user_id] = $twt->from_user;
					}
					
					// Second verse, same as the first
					if (!empty($twt->to_user_id) && empty($lookup[$twt->to_user_id]))
					{
						// in_reply_to_user_id => $twt->to_user_id
						$tweeter = Model_Tweeter::find()
							->where('screen_name', $twt->to_user)
							->or_where('search_id', $twt->to_user_id)
							->get();
						
						if ($tweeter)
						{
							$tweeter = current($tweeter);
							$sid_relation[$twt->to_user_id] = $tweeter->id;
						}
						else
							$lookup[$twt->to_user_id] = $twt->to_user;
					}
				}
				if (!empty($lookup))
				{
					$new_data = self::getTwits($twitter, $lookup, $this->tweeters);
					$raw_tweets = $new_data['raw_tweets'];
					foreach ($new_data['users'] as $orm)
					{
						if ($orm->search_id)
							$sid_relation[$orm->search_id] = $orm->id;
					}
				}
				// Now look for all usernames within the tweet text (searches do not include tweet entities)
				$lookup = self::extractTwits($tweets);
				if (!empty($lookup))
				{
					foreach ($lookup as $which => $uname)
					{
						$tweeter = Model_Tweeter::find()
							->where('screen_name', $uname)
							->get();
						if ($tweeter)
						{
							$tweeter = current($tweeter);
							$this->tweeters[$tweeter->id] = $tweeter;
							unset($lookup[$which]);
						}
					}
					if (!empty($lookup))
						$new_users = self::getTwits($twitter, $lookup, $this->tweeters, FALSE);
				}
				unset($lookup, $new_data, $new_users);
			}
			
			// Insert tweets into DB
			$last_id = NULL;
			foreach ($tweets as $twt)
			{
				$tweet_id = (empty($twt->id_str) ? $twt->id : $twt->id_str);
				// Store tweet
				$orm = new Model_Tweet(array('id' => $tweet_id)); // API sez use id_str and not id. id seems to be inaccurate/incorrect anyway.
				$orm->created_at               = self::formatDBDate(strtotime($twt->created_at));
				$orm->text                     = $twt->text;
				$orm->source                   = $twt->source;
				$orm->retweet                  = !empty($twt->retweeted_status) or (bool)preg_match('(^RT | RT )', $twt->text);
				
				// Did we get lucky? (These come from the user ID lookup above.)
				if (!empty($raw_tweets) && !empty($raw_tweets[$orm->id]))
				{
					$twt = $raw_tweets[$orm->id];
					$haveRawTweet = TRUE;
					self::out('Score! Worth implementing raw tweet support for searches. (Tweet '.$orm->id.')', self::LEVEL_DEBUG);
				}
				else
					$haveRawTweet = FALSE;
				
				if ($isSearch && !$haveRawTweet)
				{
					// Twitter sez: Warning: The user ids in the Search API are different from those in the REST API.
					// ... This defect is being tracked by Issue 214. This means that the to_user_id and from_user_id field vary from the actual user id on Twitter.com.
					// ... Applications will have to perform a screen name-based lookup with the users/show method to get the correct user id if necessary.
					// This is done right before the $tweets loop.
					
					if (!empty($sid_relation[$twt->from_user_id]))
						$orm->tweeter_id = $sid_relation[$twt->from_user_id];
					else
						self::out('No idea who '.$twt->from_user.' (from, '.$twt->from_user_id.') is for tweet '.$orm->id, self::LEVEL_NOTICE);
					
					if (!empty($twt->to_user_id))
					{
						if (!empty($sid_relation[$twt->to_user_id]))
							$orm->in_reply_to_user_id = $sid_relation[$twt->to_user_id];
						else
							self::out('No idea who '.$twt->to_user.' (to, '.$twt->to_user_id.') is for tweet '.$orm->id, self::LEVEL_NOTICE);
						$orm->in_reply_to_screen_name = $twt->to_user;
					}
					
					/*
					We have no substitutes for:
					$orm->favorited                = $twt->favorited;
					$orm->in_reply_to_status_id    = $twt->in_reply_to_status_id;
					$orm->place_name               = $twt->place->name;
					$orm->place_country_code       = $twt->place->country_code;
					$orm->user_location            = $twt->user->location;
					$orm->user_followers_count     = $twt->user->followers_count;
					$orm->user_friends_count       = $twt->user->friends_count;
					$orm->user_verified            = $twt->user->verified;
					$orm->coord_type  = $twt->coordinates->type;
					$orm->coord_coord = json_encode($twt->coordinates->coordinates);
					$orm->geo_type  = $twt->geo->type;
					$orm->geo_coord = json_encode($twt->geo->coordinates);
					*/
				}
				else
				{
					$orm->favorited                = $twt->favorited;
					$orm->in_reply_to_status_id    = $twt->in_reply_to_status_id;
					$orm->in_reply_to_user_id      = $twt->in_reply_to_user_id;
					$orm->in_reply_to_screen_name  = $twt->in_reply_to_screen_name;
					if (!empty($twt->place))
					{
						$orm->place_name               = $twt->place->name;
						$orm->place_country_code       = $twt->place->country_code;
					}
					if (!$isSearch)
					{
						$orm->tweeter_id               = $twt->user->id_str;
						$orm->user_location            = $twt->user->location;
						$orm->user_followers_count     = $twt->user->followers_count;
						$orm->user_friends_count       = $twt->user->friends_count;
						$orm->user_verified            = $twt->user->verified;
					}
					
					if (!empty($twt->coordinates))
					{
						// Ignore these if there's geo points. These are reversed for some reason...
						// 'type' => 'Point', 'coordinates' =>  array (0 => lat, 1 => long)
						if (($twt->coordinates->type != 'Point') || empty($twt->geo))
						{
							$orm->coord_type  = $twt->coordinates->type;
							$orm->coord_coord = json_encode($twt->coordinates->coordinates);
							self::out('$twt->coordinates present: '.json_encode($twt->coordinates), self::LEVEL_NOTICE);
						}
					}
					if (!empty($twt->geo))
					{
						// Examples seen:
						// 'type' => 'Point', 'coordinates' =>  array (0 => long, 1 => lat)
						$orm->geo_type  = $twt->geo->type;
						$orm->geo_coord = json_encode($twt->geo->coordinates);
					}
				}
				
				// Remember the newest tweet
				if (bccomp($last_id, $tweet_id) === -1) // Arbitrary large number comparison
					$last_id = $tweet_id;
				self::out('Saving tweet: '.$orm->id, self::LEVEL_DEBUG);
				$tweet_exists = FALSE;
				try
				{
					$orm->save();
				}
				catch (Database_Exception $ex)
				{
					if ($ex->getCode() == self::MYSQL_ERR_DUPLICATE)
					{
						// Ignore duplicate tweets
						self::out('Collection '.$cl->id.'\'s tweet already exists: '.$orm->id, self::LEVEL_DEBUG);
						$tweet_exists = TRUE;
					}
					else
						throw $ex;
				}
				if (!$tweet_exists && mysql_errno()) // TODO: Bug report: $orm->save() will not return properly due to lack of AUTO_INCREMENT field
				{
					self::out('MySQL error while saving: #'.mysql_errno().': '.mysql_error(), self::LEVEL_ERROR);
					self::out('Collection '.$cl->id.': Lost processed tweet: '.json_ecode($orm), self::LEVEL_ERROR);
					$return = self::RETURN_ERRORS;
				}
				
				// Associate tweet to this collection
				if ($tweet_exists) // Make sure it doesn't exist already
					$colTweet = Model_Collections_Tweets::find()->where('collection_id', $cl->id)->where('tweet_id', $tweet_id)->get();
				if (empty($colTweet))
				{
					$colTweet = new Model_Collections_Tweets(array(
						'collection_id' => $cl->id,
						'tweet_id'      => $tweet_id,
					));
					$colTweet->save();
					if (mysql_errno()) // $orm->save() will not return properly due to lack of AUTO_INCREMENT field
					{
						self::out('MySQL error while saving: #'.mysql_errno().': '.mysql_error(), self::LEVEL_ERROR);
						self::out('Collection '.$cl->id.'<->tweet '.$tweet_id.' relation lost.', self::LEVEL_ERROR);
						$return = self::RETURN_ERRORS;
					}
					//else
					//	self::out('Stored col<->tweet relation.', self::LEVEL_DEBUG);
				}
				unset($colTweet);
				
				# # # The rest of this has been done if the tweet already exists. # # #
				if ($tweet_exists)
					continue;
				
				// Store entities (mentions, hash tags, URLs) in a separate table
				$orm = new Model_Tweets_Entities();
				$orm->tweet_id    = $tweet_id;
				if (empty($twt->entities))
				{
					self::out('Figuring out entities manually for tweet '.$orm->tweet_id, self::LEVEL_DEBUG);
					// Parse out our own entities
					$orm->entity_body = json_encode(self::parseEntities($tweet_id, $twt->text, $this->tweeters));
				}
				else
					$orm->entity_body = json_encode($twt->entities);
				if (!$orm->save())
				{
					self::out('Collection '.$cl->id.': Lost tweet entities: '.json_encode($orm), self::LEVEL_ERROR);
					$return = self::RETURN_ERRORS;
				}
				
				// Don't store half-tweets, they're useless!
				if (!$isSearch || $haveRawTweet)
				{
					// Store json-encoded tweet in separate table
					$orm = new Model_Tweets_History();
					$orm->tweet_id    = $tweet_id;
					$orm->whole_tweet = json_encode($twt); // And nothing but the tweet
					$orm->save();
					if (!$orm->save())
					{
						self::out('Collection '.$cl->id.': Lost encoded tweet: '.json_encode($orm), self::LEVEL_ERROR);
						$return = self::RETURN_ERRORS;
					}
				}
				
				// Even the raw tweets won't have this (but the users are already saved, so that's OK).
				if (!empty($twt->user))
				{
					// Save Twitter user data too
					if (!self::parseUser($twt->user, $tweeter = NULL, $this->tweeters))
						$return = self::RETURN_ERRORS;
				}
			}
			
			// On success, update collection's last update time
			$cl->updated_at = self::formatDBDate(time());
			if (!empty($last_id))
				$cl->since_id = $last_id;
			self::out('Saving completion of collection '.$cl->id, self::LEVEL_DEBUG);
			if (!$cl->save())
			{
				self::out('Could not update collection: '.json_encode($cl), self::LEVEL_ERROR);
				$return = self::RETURN_ERRORS;
			}
		}
		
		if ($this->errored == count($this->cols))
		{
			self::out('All collections ('.$this->errored.') failed!', self::LEVEL_WARNING);
			$return = self::RETURN_ALL_ERRORS;
		}
		return $return;
	}
	
	/**
	 * Creates an entities array (like Twitter would) out of the given tweet text.
	 * @param string tweet_text Tweet text to parse.
	 * @param Tweeter[] tweeters Optional. Tweeter object cache.
	 * @return string[][] Entities: hashtags => array, user_mentions => array, urls => array().
	 */
	public static function parseEntities($tweet_id = NULL, $tweet_text, array &$tweeters = array())
	{
		// Look for #hashtags, @mentions and URLs.
		$entities = array(
			'hashtags'      => array(),
			'user_mentions' => array(),
			'urls'          => array(),
		);
		foreach ($entities as $type => $dummy)
		{
			switch ($type)
			{
			case 'hashtags':      $regex = self::REGEX_HASHTAG; $matchName = 'text'; break;
			case 'user_mentions': $regex = self::REGEX_MENTION; $matchName = 'screen_name'; break;
			case 'urls':          $regex = self::REGEX_URL;     $matchName = 'url'; break;
			}
			$matches = array();
			if (preg_match_all($regex, $tweet_text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE))
			{
				// $matches structure with these flags is:
				// i => 0 => array (text, position)
				// Note that i => 1+ aren't terribly useful here.
				foreach ($matches as $match)
				{
					$end = $match[0][1] + strlen($match[0][0]);
					$entity = array(
						'indices' => array($match[0][1], $end),
						$matchName => ($type == 'urls') ? $match[0][0] : substr($match[0][0], 1), // Remove #/@
					);
					switch ($type)
					{
					case 'hashtags':
						if (!$entity[$matchName])
						{
							self::out('Hashtag is null in tweet '.$tweet_id.'!', self::LEVEL_ERROR);
							continue;
						}
						break;
					case 'user_mentions':
						$user_id_str = $real_name = NULL;
						$screen_name = $entity[$matchName];
						foreach ($tweeters as $tweeter)
							if (strtolower($tweeter->screen_name) == strtolower($screen_name))
								break;
						
						if (strtolower($tweeter->screen_name) != strtolower($screen_name))
						{
							$tweeter = Model_Tweeter::find()->where('screen_name', $match[0][0])->get();
							if ($tweeter)
							{
								$tweeter = current($tweeter); // because ->get() should return NULL or array()
								$tweeters[$tweeter->id] = $tweeter;
							}
						}
						
						if (!$tweeter && (strtolower($screen_name) == 'me'))
							continue;
						
						if ($tweeter)
						{
							$user_id_str = $tweeter->id;
							$screen_name = $tweeter->screen_name;
							$real_name   = $tweeter->name;
						}
						else
							self::out('Tweeter mentioned in tweet '.$tweet_id.' is unknown: '.$screen_name, self::LEVEL_NOTICE);
						$entity['id_str']      = $user_id_str;
						$entity['id']          = $user_id_str;
						$entity['screen_name'] = $screen_name;
						$entity['name']        = $real_name;
						break;
					case 'urls':
						$entity['expanded_url'] = NULL;
						break;
					}
					
					$entities[$type][] = $entity;
				}
			}
		}
		return $entities;
	}
	
	/**
	 * TODO: document me
	 * Note: Saves user after parse
	 * @param Model_Tweeter tweeter Optional, in-out (reference). Tweeter object.
	 */
	public static function parseUser(&$twitterUser, &$tweeter = NULL, &$cache = array(), $search_id = NULL)
	{
		if (!$tweeter)
		{
			if (empty($cache[$twitterUser->id_str]))
			{
				$newTweeter = TRUE;
				$cache[$twitterUser->id_str] = Model_Tweeter::find($twitterUser->id_str);
			}
			$tweeter = $cache[$twitterUser->id_str];
			if (!$tweeter)
			{
				$tweeter = new Model_Tweeter(array('id' => $twitterUser->id_str));
				$tweeter->created_at = self::formatDBDate(strtotime($twitterUser->created_at));
			}
		}
		$tweeter->screen_name           = $twitterUser->screen_name;
		if (!empty($search_id))
			$tweeter->search_id = $search_id;
		$tweeter->name                  = $twitterUser->name;
		$tweeter->location              = $twitterUser->location;
		$tweeter->description           = $twitterUser->description;
		$tweeter->profile_image_url     = $twitterUser->profile_image_url;
		$tweeter->url                   = $twitterUser->url;
		$tweeter->followers_count       = $twitterUser->followers_count;
		$tweeter->friends_count         = $twitterUser->friends_count;
		$tweeter->verified              = $twitterUser->verified;
		$tweeter->protected             = !empty($twitterUser->protected);
		
		self::out('Saving tweeter: '.$tweeter->id, self::LEVEL_DEBUG);
		$tweeter->save();
		$cache[$twitterUser->id_str] = $tweeter;
		
		if (mysql_errno()) // $orm->save() will not return properly due to lack of AUTO_INCREMENT field
		{
			self::out('MySQL error while saving: #'.mysql_errno().': '.mysql_error(), self::LEVEL_ERROR);
			self::out('Lost tweeter: '.json_encode($tweeter), self::LEVEL_ERROR);
			return FALSE;
		}
		return TRUE;
	}
	
	/**
	 * Sends any delayed messages.
	 */
	protected static function sendMail($subject = 'Notices or warnings during harvesting')
	{
		if (Fuel::$env == Fuel::PRODUCTION)
		{
			$app = Config::load('app');
			mail($app['report_email'], $subject, implode("\n", self::$messages));
		}
		else
		{
			Debug::dump(self::$messages);
		}
		self::$messages = array();
	}
	
	/**
	 * Function for handling output, debugging, and notifications.
	 * @param string msg Message to send.
	 * @param int level Severity of the message; See class constants.
	 */
	protected static function out($msg, $level = self::LEVEL_DEBUG, $forbidDelay = FALSE)
	{
		$output = TRUE;
		$is_tty = posix_isatty(STDOUT);
		switch ($level)
		{
		case self::LEVEL_DEBUG:   $lname = 'Debug';   $loglevel = Fuel::L_DEBUG; $output = self::DEBUG_MODE || $is_tty; break;
		case self::LEVEL_NOTICE:  $lname = 'Notice';  $loglevel = $lname;        $output = self::DEBUG_MODE || $is_tty; break;
		case self::LEVEL_WARNING: $lname = 'Warning'; $loglevel = $lname;        $output = TRUE;  break;
		case self::LEVEL_ERROR:   $lname = 'Error';   $loglevel = Fuel::L_ERROR; $output = TRUE;  break;
		}
		Log::write($loglevel, 'Harvester: '.$msg);
		
		if ($output)
		{
			if ($is_tty)
				echo $lname,': ',$msg,"\n";
			else
			{
				self::$messages[] = $msg;
				
				if ($forbidDelay)
				{
					$app = Config::load('app');
					$shortMsg = str_replace(array("\n","\r","\t",'  '), ' ', substr($msg, 0, 20));
					if (strlen($msg) > 20) $shortMsg .= '...';
					$subject = $app['name'].' harvester'.(self::DEBUG_MODE ? ' (debug mode)' : '').': '.$lname.': '.$shortMsg;
					
					self::sendMail($subject);
				}
			}
		}
	}
}
