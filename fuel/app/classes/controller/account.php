<?php

class Controller_Account extends Controller_Template {

  /**
   * This action simply guides unauthenticated users to login to their Twitter
   * accounts. Autheticated users will be redirected to their dashboard.
   */
	public function action_index()
	{
    $view = View::factory('account/index');
    // first, check if we have an access token in the session
    
    if (TwitterAccount::isLoggedIn())
    {
      $accessToken = TwitterAccount::accessToken();
      $ta = TwitterAccount::getUserAccount($accessToken['oauth_token'], $accessToken['oauth_token_secret']);
      $view->screenName = $accessToken['screen_name'];
    }
    else
    {
      Session::destroy();
    }
    $pub_collections = Model_Collection::find('all', array('where' => array(array('public', '=', '1'))));

	$collections = array();

	foreach ($pub_collections as $collection)
	{
		$collections[$collection->id] = $collection->name;
	}

    $view->public_collections = $collections;

    $this->template->title = 'Welcome to TweetBeagle';
    $this->template->content = $view;
	}

	/**
	 * This action simply guides unauthenticated users to login to their Twitter
	 * accounts.
	 */
	public function action_signin()
	{
		$ta = TwitterAccount::getDefaultAccount();
		$requestToken = $ta->getRequestToken();
		
		Log::debug(var_export($requestToken, true));
		
		if ($requestToken)
		{	
			Session::set('oauth_token', $requestToken['oauth_token']);
			Session::set('oauth_token_secret', $requestToken['oauth_token_secret']);
						
			$params = '';
			// Are they trying to switch accounts?
			if (!is_null(input::get_post('force')))
				$params .= '&force_login=true';
			
			$url = $ta->getAuthorizeUrl($requestToken['oauth_token']);
			$this->response->redirect($url.$params);
		}
		else
		{
			$this->template->title = 'Account &raquo; Sign in';
			$this->template->content = 'Error: Unable to connect to Twitter!';
		}
	}

  /**
   * This action destroys the Session and redirects the user to the login page.
   */
	public function action_signout()
	{
		Session::destroy();
    Response::redirect('account');
  }

  /**
   * This action is redirected to from Twitter and is given the request token
   * and a verifier. It must do the final requestToken/accessToken exchange and
   * store stuff in the session.
   */
	public function action_oauth_callback()
	{
    $twitterToken = Input::get_post('oauth_token', FALSE);
    $twitterTokenVerifier = Input::get_post('oauth_verifier', FALSE);
    $sessionToken = Session::get('oauth_token');

    Log::debug('oauth_token from Twitter: '.$twitterToken);
    Log::debug('oauth_token from Session: '.$sessionToken);
    Log::debug('oauth_verifier from Twitter: '.Input::get_post('oauth_verifier'));

    // check if the request token we got from Twitter is an old one
    if ($twitterToken && ($sessionToken !== $twitterToken)) {
      Session::set('oauth_status', 'oldtoken');
      Response::redirect('account/index');
    }

    $ta = TwitterAccount::getUserAccount($sessionToken['oauth_token'], 
                                         $sessionToken['oauth_token_secret']);

    $accessToken = $ta->getAccessToken($twitterToken, $twitterTokenVerifier);
    $user = $ta->verifyCredentials();

    Log::debug(var_export($accessToken, true));

    Session::set('access_token', $accessToken);

    // clean up unnecessary session variables
    Session::delete('oauth_token');
    Session::delete('oauth_token_secret');

    // depending on the last response code, redirect the user to the dashboard
    // or the login page
    if ($ta->isLastRequestSuccessful())
    {
      $this->createUserAccount($user);
      Session::set('oauth_status', 'verified');
      Response::redirect('dashboard');
    }
    else
    {
      Response::redirect('account/signout');
    }
  }

	/**
	 * Creates an account for the logged in user if it does not already exist.
	 * It also retrieves their Twitter data.
	 * @param object user Twitter user that just logged in.
	 */
	protected function createUserAccount($user)
	{
		$accessToken = TwitterAccount::accessToken();
		
		// Check if we already know this account's full Twitter data
		// These checks are separate because someone else may have seen them before in a list or search.
		$tweeter = Model_Tweeter::find($accessToken['user_id']);
		
		if (!$tweeter)
			Harvester::parseUser($user, $tweeter, $dummy = array());
		Session::set('screen_name', $tweeter->screen_name);
		
		// If this is the first time the user logs into our system, create an account him/her
		$account = Model_Account::find($accessToken['user_id']);
		
		if (empty($account))
		{
			$account = new Model_Account(array(
			'id'           => $accessToken['user_id'],
			'oauth_key'    => $accessToken['oauth_token'],
			'oauth_secret' => $accessToken['oauth_token_secret'],
			));
			
			$account->save();
		} else {
			$account->oauth_key = $accessToken['oauth_token'];
			$account->oauth_secret = $accessToken['oauth_token_secret'];
			$account->save();
		}
	}
}

/* End of file account.php */
