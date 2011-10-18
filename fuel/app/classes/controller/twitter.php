<?php 

class Controller_Twitter extends Controller_Template {

	protected static $require_authentication = true;

  public $template = 'template';

  public function before() {
    parent::before();
	
    $this->template->title = 'Twitter API Wrapper test';
  }

	public function action_index()
	{
		View::$auto_encode = false;
		$view = View::factory('twitter/index');
		
		$ta = TwitterAccount::getCurrentUserAccount();
		
		$view->output = array(
			//'userTimeLine'          => $ta->getUserTimeLine(),
			//'lists'                 => $ta->getLists(),
			//'addMembers'            => $ta->addMembersToUserList('43919251', array('20536157')),
			//'membersOfList'         => $ta->getListMembers('43919251'),
			'getUsersDetails' => $ta->getUsersDetails(NULL, array('æ')),
		);
		
		$this->template->content = $view;
	}
	
  /**
   * 
   */
  public function action_test_da($id)
  {
    if (!TwitterAccount::isLoggedIn())
      Response::redirect('account/signout');

    View::$auto_encode = false;
    $view = View::factory('graphs/linechart');

    $da = new DataAggregator();
    $da->setTagType('hashtag')->setCollectionId($id)->setSince('-20 days');

    $view->chartData = $da->getGoogleVisData();

    $this->template->title = 'Top Trends';
    $this->template->content = $view;

  }
  
	public function action_add_to_list()
	{
		if(Input::is_ajax())
		{
			$this->auto_render = False;
		}
	
		// Sends request to twitter API to follow $twitter_id user
		$follow_user = array(0 => Input::post('twitter_id'));
		$list = Input::post('list_id');
		$twitter_account = TwitterAccount::getCurrentUserAccount()->addMembersToUserList($list,'',$follow_user);
	}

}
