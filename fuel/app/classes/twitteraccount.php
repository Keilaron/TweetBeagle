<?php

/**
 * Description of Twitter Account
 *
 * @author bushra
 */
class TwitterAccount {

  /**
   * Wrapper for the Twitter connection object
   * @var TwitterOAuth
   */
  public $connection;

  /**
   * The user object to which this TwitterAccount belongs. The data in this
   * object are retrieved from Twitter and may not be up-to-date with the
   * user record in the database.
   * @var stdClass
   */
  public $user;

  /**
   * When the class is instantiated with an access token, then it should be
   * ready to invoke the Twitter API. Otherwise, this should remain false.
   * Note that this variable being TRUE does not guarantee the success of API
   * calls.
   * @var boolean
   */
  public $apiReady = false;

  /**
   * The configuration array holds
   * @var array
   */
  public $config = array();

  /* * Factory Methods * * * * * * * * * * * * * * * * * * * * * * * * * * */

  /**
   * TwitterAccount with only the consumer key
   * @return TwitterAccount
   */
  public static function getDefaultAccount()
  {
    $config = array(
      'consumer_key' => Config::get('twitter.consumer_key'),
      'consumer_secret' => Config::get('twitter.consumer_secret'),
    );

    return new TwitterAccount($config);
  }

  /**
   * TwitterAccount with only the consumer key
   * @return TwitterAccount
   */
  public static function getUserAccount($oauthToken, $oauthTokenSecret)
  {
    $config = array(
      'consumer_key' => Config::get('twitter.consumer_key'),
      'consumer_secret' => Config::get('twitter.consumer_secret'),
      'oauth_token' => $oauthToken,
      'oauth_token_secret' => $oauthTokenSecret
    );

    return new TwitterAccount($config);
  }

  /**
   * TwitterAccount with the logged-in user credentials
   * @return TwitterAccount
   */
  public static function getCurrentUserAccount()
  {
    if (!self::isLoggedIn())
      throw new Exception ('Cannot instantiate an TwitterAccount for an anonymous user');

    $accessToken = self::accessToken();

    $config = array(
      'consumer_key' => Config::get('twitter.consumer_key'),
      'consumer_secret' => Config::get('twitter.consumer_secret'),
      'oauth_token' => $accessToken['oauth_token'],
      'oauth_token_secret' => $accessToken['oauth_token_secret']
    );

    return new TwitterAccount($config);
  }

  /**
   * TwitterAccount with only the consumer key
   * @return TwitterAccount
   */
  public static function getTestUserAccount()
  {
    $config = array(
      'consumer_key' => Config::get('twitter.consumer_key'),
      'consumer_secret' => Config::get('twitter.consumer_secret'),
      'oauth_token' => Config::get('twitter.test_access_token'),
      'oauth_token_secret' => Config::get('twitter.test_access_token_secret'),
    );

    return new TwitterAccount($config);
  }

  /**
   * Check whether the user is logged in (local Session check).
   * @return boolean Returns TRUE 
   */
  public static function isLoggedIn()
  {
    $isLoggedIn  = TRUE;
    $accessToken = Session::get('access_token');
    $userId      = Session::get('user_id');

    if (empty($accessToken) ||
        empty($accessToken['oauth_token']) ||
        empty($accessToken['oauth_token_secret']))
    {
      $isLoggedIn = FALSE;
    }

    return $isLoggedIn;
  }

  /**
   * Retrieves the access_token array from the Session
   * @return array The access_token array
   */
  public static function accessToken()
  {
    $accessToken = Session::get('access_token');

    if (empty($accessToken) ||
        empty($accessToken['oauth_token']) ||
        empty($accessToken['oauth_token_secret']))
    {
      return FALSE;
    }

    return $accessToken;
  }

  /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

  /**
   * 
   */
  public function __construct($config = array())
  {
    $this->config = $config;

    $this->createConnection();
  }

  /**
   * 
   */
  public function createConnection()
  {
    if (!empty($this->config['consumer_key']) && !empty($this->config['consumer_secret']))
    {
      if (!empty($this->config['oauth_token']) && !empty($this->config['oauth_token_secret']))
      {
        $this->connection = new TwitterOAuth($this->config['consumer_key'],
                                             $this->config['consumer_secret'],
                                             $this->config['oauth_token'],
                                             $this->config['oauth_token_secret']);
        $this->apiReady = true;
      }
      else
      {
        $this->connection = new TwitterOAuth($this->config['consumer_key'],
                                             $this->config['consumer_secret']);
      }
    }

    // retrieve the user object and cache it locally
    if (!is_null($this->connection))
    {
      $this->user = $this->verifyCredentials();
    }
  }

  /**
   * @todo write docs
   * @api
   */
  public function verifyCredentials()
  {
    $url = 'account/verify_credentials';

    $this->preApiCall();
    $response = $this->connection->get($url);
    $this->postApiCall();
    return $response;
  }

	/**
	 * @todo write docs
	 * @api
	 */
	public function getUserTimeLine($params = array())
	{
		$url = 'statuses/user_timeline';
		
		$defaultParams = array(
			'include_entities' => 1,
			'include_rts' => 1,
		);
		
		$this->preApiCall();
		$response = $this->connection->get($url, array_merge($defaultParams, $params));
		$this->postApiCall();
		return $response;
	}
	
	/**
	 * Retrieves the Twitter Lists of the account user.
	 * @api
	 */
	public function getLists($params = array())
	{
		$url = 'lists';
		
		$defaultParams = array(
			'user_id' => $this->user->id,
		);
		
		$this->preApiCall();
		$response = $this->connection->get($url, array_merge($defaultParams, $params));
		$this->postApiCall();
		return $response;
	}
	
	/**
	 * @param int The ID of the user who owns the list.
	 * @param mixed The ID of the list. Slug can be used as well, but is disrecommended.
	 * @api
	 */
	public function getListStatuses($userId, $listId, $params = array())
	{
		$url = 'lists/statuses';
		
		$defaultParams = array(
			'user_id' => $userId,
			'include_entities' => 1,
			'include_rts'      => 1,
			'count'            => 100, // Not actually in the API doc, but left in case it works
		);
		if (ctype_digit($listId))
			$defaultParams['list_id'] = $listId;
		else
		{
			$defaultParams['owner_id'] = $userId;
			$defaultParams['list_id']  = $listId;
		}
		
		$this->preApiCall();
		$response = $this->connection->get($url, array_merge($defaultParams, $params));
		$this->postApiCall();
		return $response;
	}
	
	/**
	* Retrieves the members of a single Twitter List of a user.
	* @api
	*/
	public function getListMembers($listId, $userId = null, $params = array())
	{
		if (empty($userId) && self::isLoggedIn())
			$userId = Arr::element (Session::get('access_token'), 'user_id');
		
		$url = 'lists/members';
		
		$defaultParams = array();
		if (ctype_digit($listId))
			$defaultParams['list_id'] = $listId;
		else
		{
			$defaultParams['owner_id'] = $userId;
			$defaultParams['list_id']  = $listId;
		}
		
		$this->preApiCall();
		$listmembers = $this->connection->get($url, array_merge($defaultParams, $params));
		
		$pagevalue = ""; //Set page value for later use
		
		$members = array();

		if($listmembers->next_cursor == 0){ //There is only one page of followers
				return $listmembers->users;
		} else { //There are multiple pages of followers
			while ($pagevalue!=$listmembers->next_cursor){
				foreach ($listmembers->users as $list_user)
				{
			    	$members[] = $list_user;
			    }
				$pagevalue = $listmembers->next_cursor; //Increment the 'Next Page' link
				$defaultParams['cursor'] = $pagevalue;
				$listmembers = $this->connection->get($url, array_merge($defaultParams, $params));
			}
		}
		
		$this->postApiCall();
		return $members;
	}
	
  /**
   * @param mixed (numeric or string) The user's ID
   * @param string The user's screen name
   * @api
   */
  public function getUserDetails($userId = null, $screenName = null, $params = array())
  {
    $url = 'users/show';
    $defaultParams = array();

    if (!empty($userId))
      $defaultParams['user_id'] = $userId;
    elseif (!empty($screenName))
      $defaultParams['screen_name'] = $screenName;
    else
      throw new InvalidArgumentException('Either the user ID or the user screen name must be specified.');

    $this->preApiCall();
    $response = $this->connection->get($url, array_merge($defaultParams, $params));
    $this->postApiCall();
    return $response;
  }

  /**
   * @param mixed userIds (numeric or string) The users' IDs
   * @param string screenNames The users' screen names
   * @api
   */
  public function getUsersDetails($userIds = null, $screenNames = null, $params = array())
  {
    $url = 'users/lookup';
    $defaultParams = array(
    	'include_entities' => 1,
    	);

    if (!empty($userIds) && is_array($userIds))
      $defaultParams['user_id'] = implode(',', $userIds);
    elseif (!empty($screenNames) && is_array($screenNames))
      $defaultParams['screen_name'] = implode(',', $screenNames);
    else
      throw new InvalidArgumentException('Either the user IDs or the user screen names must be specified.');

    $this->preApiCall();
    $response = $this->connection->get($url, array_merge($defaultParams, $params));
    $this->postApiCall();
    return $response;
  }

  /**
   * @param array An array of user IDs or screen names
   * @param string listId Required or optional if second argument is provided. The ID of the list (NOT the slug).
   * @param string[] listSlug Required or optional if first argument is provided. Array:
   	'slug' => Required. The slug of the list (NOT the ID).
   	'owner_id' => Required or optional if owner_screen_name is provided. The owner of the list.
   	'owner_screen_name' => Required or optional if owner_id is provided. The owner of the list.
   * @param string[] users Required. Array of user IDs (not screen names) to add.
   * @api
   */
  public function addMembersToUserList($listId = null, $listSlug = null, array $users, $params = array())
  {
  	  if (empty($listId) && empty($listSlug))
  	  	  throw new InvalidArgumentException('The first or second argument must be provided.');
    if (empty($users))
      throw new InvalidArgumentException('A non-empty array is expected in the third argument.');

    $url = 'lists/members/create_all';

    $defaultParams = array(
      'screen_name' => implode(',', $users)
    );
    if (!empty($listId))
    	$defaultParams['list_id'] = $listId;
    else
    {
    	$defaultParams['slug'] = $listSlug['slug'];
    	if (!empty($listSlug['owner_id']))          $defaultParams['owner_id']          = $listSlug['owner_id'];
    	if (!empty($listSlug['owner_screen_name'])) $defaultParams['owner_screen_name'] = $listSlug['owner_screen_name'];
    }

    $this->preApiCall();
    $response = $this->connection->post($url, array_merge($defaultParams, $params));
    $this->postApiCall();
    return $response;
  }
  
  public function follow($twitter_id, $params = array())
  {
  	if(empty($twitter_id))
  	{
  		throw new InvalidArgumentException('A non-empty value is expected in the first argument.');
  	}
  	
  	if (empty($userId) && self::isLoggedIn())
      $userId = Arr::element (Session::get('access_token'), 'user_id');
      
    $url = 'friendships/create.json?user_id='.$twitter_id;
    
    $this->preApiCall();

    $response = $this->connection->http($url, 'POST');
    $response = !empty($response) ? json_decode($response) : FALSE;
    $results  =& $response->results;
    
    return $response;

  }
  
  /**
   * @param array params Parameters to send. As of this writing there are none for this call.
   * @return object { "remaining_hits": 150, "reset_time_in_seconds": 1277234708, "hourly_limit": 150, "reset_time": "Tue Jun 22 19:25:08 +0000 2010" }
   * @api
   * @see http://dev.twitter.com/doc/get/account/rate_limit_status
   */
	public function rate_limit_status($params = array())
	{
		$url = 'account/rate_limit_status';
		
		$defaultParams = array();
		$this->preApiCall();
		$response = $this->connection->get($url, array_merge($defaultParams, $params));
		$this->postApiCall();
		return $response;
	}

  /**
   * @param string Search query
   * @param array $params An array of additional query parameters to filter search results
	 *        When passed a since_id parameter, the search function will recursively
	 *        paginate and collect search results until it hits the tweet with the
	 *        provided since_id or an earlier one.
   * @param boolean Set this to TRUE in order to have the function append, to
   *        each result, the real ID of tweeter. WARNING: this will add significant
   *        overhead to the search function that raises at a O(N) rate.
   * @api
	 * @see http://dev.twitter.com/doc/get/search for valid elements of $params
   */
  public function search($text, $params = array(), $include_real_from_user_id = FALSE)
  {
		$BaseUrl = Config::get('twitter.search.api_url');

		$defaultParams = array(
			'q'           => $text,
			'result_type' => 'recent',
			'rpp'         => '50'
		);

		$this->preApiCall();

		// paginate/collect if since_id is provided
		if (isset($params['since_id']) && !empty($params['since_id']))
		{
			$searchResultsLimit = Config::get('twitter.search.results_limit');
			$url = $BaseUrl.'?'.http_build_query(array_merge($defaultParams, $params));
			
			$response = new stdClass();
			$response->results = array();
			$response->completed_in = 0.0;
			
			do
			{
				$currentResponse = $this->connection->http($url, 'GET');
				if (!empty($currentResponse))
				{
					$currentResponse = json_decode($currentResponse);
					$results = $currentResponse->results;
					Log::debug("Search executed! Found ".count($results)." results");
					
					if (!empty($results))
					{
						foreach ($results as $result)
						{
							if ($result->id <= (int)$params['since_id']) // manually checking whether we exceeded the since_id
							{
								break 2;
							}
							else if(count($response->results) >= $searchResultsLimit) // checking whether we exceeded the search results threshold
							{
								Log::debug("Breaking out of search results collection loop because results limit ($searchResultsLimit) has been reached. Search query ($text).");
								break 2;
							}
							else // otherwise, append the tweet to the main $response object
							{
								$response->results[$result->id] = $result;
							}
						}
					}
					
					$response->completed_in += $currentResponse->completed_in;					
						
					if (empty($currentResponse->next_page))
					{
						Log::debug("Breaking out of the search results collection loop because no next page has been detected. Search query ($text).");
						break;
					}
					else if(count($response->results) >= $searchResultsLimit)
					{
						Log::debug("Breaking out of search results collection loop because results limit ($searchResultsLimit) has been reached. Search query ($text).");
						break;
					}
					else
					{
						$url = $BaseUrl.$currentResponse->next_page;
					}
				}
				else
				{
					throw new Exception('Search API wrapper returned an empty response object.');
				}
				
			} while(count($response->results) > 0 && count($response->results) < $searchResultsLimit);

		}
		else // normal collection mode; no paging
		{
			$url = $BaseUrl.'?'.http_build_query(array_merge($defaultParams, $params));

			$response = $this->connection->http($url, 'GET');
			$response = !empty($response) ? json_decode($response) : FALSE;
		}
		
		$results  =& $response->results;
		// Applying a bugfix for Twitter's old user IDs issue.
		// @see https://dev.twitter.com/doc/get/search
		if ($include_real_from_user_id && !empty($results))
		{
			foreach ($results as &$result) 
			{
				$fromUserDetails = $this->getUserDetails(null, $result->from_user);
				$result->from_user_id_real = $fromUserDetails->id;
			}
		}

		$this->postApiCall();
		return $response;
  }

  /**
   * Wrapper for TwitterOAuth::getRequestToken()
   * 
   */
  public function getRequestToken()
  {
    // this would produce something like "http://www.twitterag.com/account/oauth_callback"
    $localUrl = Uri::create(Config::get('twitter.callback_url_internal'));

    // @todo: Potential bug - The Uri::create function builds either an absolute
    //        URL or a relative one based on the deployment server setup.
    //$callbackUrl = 'http://'.Input::server('HTTP_HOST').$localUrl;
    
    $requestToken = $this->connection->getRequestToken($localUrl);
    
    return $this->connection->http_code == 200 ? $requestToken : false;
  }
  
  /**
   * Wrapper for TwitterOAuth::getAuthorizeUrl()
   * 
   */
  public function getAuthorizeUrl($requestToken)
  {
    // The `true` parameter here forces TwitterOAuth to fetch Twitter's
    // oauth/authenticate URL instead of oauth/authorize.
    return $this->connection->getAuthorizeUrl($requestToken, true);
  }

  /**
   * Wrapper for TwitterOAuth::getAccessToken()
   *
   */
  public function getAccessToken($requestToken, $requestTokenVerifier)
  {
    return $this->connection->getAccessToken($requestToken, $requestTokenVerifier);
  }

  public function isLastRequestSuccessful()
  {
    return $this->connection->http_code == 200;
  }
  
  /* * Lifecycle methods * * * * * * * * * * * * * * * * * * * * * * * * * * */

  /**
   * This method should be called prior to sending any API request.
   */
  protected function preApiCall()
  {
    //Log::debug(var_export($this->connection->last_api_call, true));
    // perform NULL checks
    if (is_null($this->connection))
    {
      throw new Exception('Un-instatiated or invalid Twitter API connection.');
    }
  }

  /**
   * This method should be called after receiving an API response.
   */
  protected function postApiCall()
  {
    //Log::debug($this->connection->lastAPICall());
  }

}
