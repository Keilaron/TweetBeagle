<?php
class Controller_Collections extends Controller_Template
{
	protected static $require_authentication = true;

	public function before ()
	{
		if($this->request->action == 'view')
		{
			$user_id = Arr::element(Session::get('access_token'), 'user_id');
			$collection = Model_Collection::find($this->request->method_params[0]);

			if ($collection->public)
			{
				self::$require_authentication = false;
			}
		}
		
		parent::before();
	}

	public function action_index()
	{
		$account = Model_Account::find_current();
		
		$data['collections'] = $account->collections;
		$this->template->title = 'Collections';
		$this->template->content = View::factory('collections/index', $data);
	}
	
	public function action_view($id = null)
	{
		$user_id = Arr::element(Session::get('access_token'), 'user_id');
		$collection = Model_Collection::find($id);
		if (!$collection || ($collection->public == 0 && $collection->account_id != $user_id))
				Response::redirect('404');
		
		$help = array(
			'hide' => 'Permanently hides this tag from the collection. You may re-show hidden tags by editing the collection.',
			'filter' => 'Temporary filter via this tag; Only results where this tag has been seen will be shown, allowing you to "drill down" into your data.',
			'filter_list' => 'Any filters shown here are currently restricting the results you see. Click the icon to remove that filter, or click the filter to remove all fitlers added after it.',
			'weight' => 'Weight is based on several calculations, and essentially indicates how important or how frequent that tag is.',
			); // TODO: Move this to.. i18n?
		$tagTypes = array('hashtag', 'link', 'mention', 'term'); // TODO: Move this to.. config?
		
		$hasEditAccess = ($collection->account_id == $user_id);
		
		// Collect new hidden tags (if any) from POST data
		if (!empty($_POST['hide']) && $hasEditAccess)
		{
			foreach ($_POST['hide'] as $tag_id)
			{
				if ((int)$tag_id)
				{
					$query = Model_Collections_Omit::find()
						->where(array('collection_id' => $id, 'tag_id' => (int)$tag_id))
						->get();
					if (!$query)
					{
						$new_hide = new Model_Collections_Omit();
						$new_hide->collection_id = $id;
						$new_hide->tag_id = (int)$tag_id;
						$new_hide->save();
					}
				}
			}
			unset($new_hide);
		}
		if (!empty($_POST['unhide']) && $hasEditAccess)
		{
			foreach ($_POST['unhide'] as $tag_id)
			{
				if ((int)$tag_id)
				{
					$hidden = Model_Collections_Omit::find()
						->where(array('collection_id' => $id, 'tag_id' => (int)$tag_id))
						->get();
					if ($hidden) 
						foreach ($hidden as $hidden_tag)
							$hidden_tag->delete();
				}
			}
		}
		
		// Collect filter data from POST data
		$filter_settings = array();
		if (!empty($_POST['filters']))
		{
			foreach ($_POST['filters'] as $tag_id)
			{
				if (!(int)$tag_id)
				{
					$tag_type = substr($tag_id, 0, 1);
					switch ($tag_type)
					{
					case '#': $tag_type = 'hashtag'; break;
					case '@': $tag_type = 'mention'; break;
					default:  $tag_type = NULL; break;
					}
					if (!$tag_type)
						continue;
					
					$tag = Model_Tag::find()
						->where(array('content' => substr($tag_id, 1), 'type' => $tag_type))
						->get();
					if ($tag)
					{
						$tag = current($tag);
						$tag_id = $tag->id;
					}
					else
						continue;
				}
				if ((int)$tag_id)
					$filter_settings[] = (int)$tag_id;
			}
		}
		if (!empty($filter_settings))
		{
			$first = array_pop($filter_settings);
			$query = Model_Tag::find()->where('id', $first);
			
			if (!empty($filter_settings))
				foreach ($filter_settings as $filter_id)
					$query->or_where('id', $filter_id);
			
			$filter_settings = $query->get();
		}
		// While the objects are used by the view, the data functions only need the IDs.
		$filter_settings_ids = array_keys($filter_settings);

		$hide_ids = array_keys($collection->omits);
		
		$filter_date = (int)$_POST['filter_last_x_days'];
		if (empty($filter_date)) $filter_date = 21;
		
		// Set available filters
		$filters = array(
			'date' => array(
				1 => 'Last 24 hours',
				7 => 'Last week',
				21 => 'Last three weeks',
				30 => 'Last month',
				365 => 'All time',
			),
		);
		
		$graphs = array();
		$graphView = View::factory('graphs/linechart');
		
		$tagType = 'term';
		$da = new DataAggregator();
		$da->setTagType($tagType)
			 ->setCollectionId($id)
			 ->setFilters($filter_settings_ids)
			 ->setHidden($hide_ids)
			 ->setSince($filter_date.' days ago');

		$topTags = $da->getTopTags();
		
		if (!empty($topTags))
			$graphs[] = array('id' => $tagType, 'type' => 'line', 'tagType' => $tagType, 'data' => $topTags);
		else
			$graphs[] = array('id' => $tagType, 'tagType' => $tagType, 'message' => 'There is not enough data for the '.$tagType.
				' graph; Please choose another time period, less filters, or wait for more tweets to be collected.');
		
		$graphView->help = $help;
		$graphView->hasEditAccess = $hasEditAccess;
		
		$graphView->set('charts', $graphs, false);

		// Collect data for top-ten sections
		$ttmentions = Model_Collection::findTopTen($collection, 'mention', $filter_date, $filter_settings_ids, $hide_ids);
		$ttlinks    = Model_Collection::findTopTen($collection, 'link',    $filter_date, $filter_settings_ids, $hide_ids);
		$tthashtags = Model_Collection::findTopTen($collection, 'hashtag', $filter_date, $filter_settings_ids, $hide_ids);
		
		// Collect data for recent tweets
		$recent_tweets = Model_Collection::findRecentTweets($collection, 300, $filter_date, $filter_settings);
		
		$data['collection'] = $collection;
		$this->template->title = 'Collections &rarr; '.$collection->name;
		$this->template->content = View::factory('collections/view', $data);
		$this->template->content->hasEditAccess = $hasEditAccess;
		$this->template->content->hidden = $collection->omits;
		$this->template->content->filters = $filters;
		$this->template->content->filter_settings = $filter_settings;
		$this->template->content->filter_date = $filter_date;
		$this->template->content->graph = $graphView;
		$this->template->content->help = $help;
		$this->template->content->ttmentions = $ttmentions;
		$this->template->content->ttlinks = $ttlinks;
		$this->template->content->tthashtags = $tthashtags;
		$this->template->content->recent_tweets = $recent_tweets;
		
		Asset::js('https://www.google.com/jsapi', array(), 'assets');
		Asset::js('charts.js', array(), 'assets');
		Asset::js('collections/graph_load.js', array(), 'assets');
		Asset::js('collections/recent_tweets.js', array(), 'assets');
	}

	/**
	 * An AJAX action that loads a collection graph 
	 * @param int $id The collection ID
	 
	public function action_view_graph()
	{
		//var_dump($_REQUEST);
		//die();
		$graphs = array();
		$graphView = View::factory('graphs/linechart');
		
		$tagType             = 'term';
		$collection_id       = Input::get_post('collection_id');
		$filter_settings_ids = unserialize(Input::get_post('filter_settings_ids'));
		$hide_ids            = unserialize(Input::get_post('hide_ids'));
		$filter_date         = Input::get_post('filter_date');
		
		$da = new DataAggregator();
		$da->setTagType($tagType)
			 ->setCollectionId($collection_id)
			 ->setFilters($filter_settings_ids)
			 ->setHidden($hide_ids)
			 ->setSince($filter_date.' days ago');

		$chartData = $da->getGoogleVisData();
		if ($da->haveData())
			$graphs[] = array('id' => $tagType, 'type' => 'line', 'tagType' => $tagType, 'data' => $chartData);
		else
			$graphs[] = array('message' => 'There is not enough data for the '.$tagType.
				' graph; Please choose another time period, less filters, or wait for more tweets to be collected.');

		$graphView->set('charts', $graphs, false);
		//$this->template = '';
		//die($graphView);
		//$this->response->body = $graphView;
		$this->template->content = $graphView;
	}
	*/
	
  /**
   *
   * @param <type> $id 
   */
	public function action_create($id = null)
	{
		if (Input::method() == 'POST')
		{
      $user_id = Arr::element(Session::get('access_token'), 'user_id');
      $collectionType   = Input::post('type');
      $collectionRef    = Input::post('reference_'.Input::post('type'));
      $collectionName   = Input::post('collection_name');
			$collectionPublic = (bool) Input::post('collection_public');

			$collection = Model_Collection::factory(array(
				'name' => $collectionName,
				'account_id' => $user_id,
				'type'       => $collectionType,
				'reference'  => $collectionRef,
				'public'     => $collectionPublic
			));
			
			if ($collection && $collection->validate() && $collection->save())
			{

				// Start initial harvest
				$harvester = new Harvester(array($collection->id));
				$harversterResult = $harvester->harvest();
				
				Log::debug('qqq adding collection '.$collection->id);
				Log::debug('qqq harvester returned: '.$harversterResult);

				Session::set_flash('notice', 'Created collection "' . $collection->name . '".');
				Response::redirect('dashboard');
			}
			else
			{
				Session::set_flash('errors', $collection->validation_errors());
			}
		}

    // get the user's Twitter Lists
    $lists = array();
    $userTwitterLists = TwitterAccount::getCurrentUserAccount()->getLists()->lists;

    if (!empty($userTwitterLists)) {
      foreach ($userTwitterLists as $list) {
        $lists[$list->id_str] = $list->name.' ('.$list->member_count.')';
      }
    }

    $view = View::factory('collections/create');
    $view->lists = $lists;
		$view->types = array_merge(array(''), Config::get('collection.type_options'));

		$this->template->title = "Collections";
		$this->template->content = $view;

    Asset::js('collections/_form.js', array(), 'assets');
	}

  /**
   *
   * @param <type> $id
   */
	public function action_edit($id = null)
	{
		$user_id = Arr::element(Session::get('access_token'), 'user_id');
		$collection = Model_Collection::find($id);
		if (!$collection || $collection->account_id != $user_id)
			Response::redirect('404');

		if (Input::method() == 'POST')
		{
			//$collection->account_id = $user_id;
			//$collection->type = Input::post('type');
			if($collection->type == 'search')
			{
				$collection->reference = Input::post('reference_search');
			}
			
			$collection->name   = Input::post('collection_name');
			$collection->public = (bool) Input::post('collection_public');
			
			$unhide_tag_ids = Input::post('hide');
			if (!empty($unhide_tag_ids))
			{
				$first = array_pop($unhide_tag_ids);
				$query = Model_Collections_Omit::find()->where('collection_id', $id);
				$query->where_open();
				$query->where('tag_id', $first);
				if (!empty($unhide_tag_ids))
					foreach ($unhide_tag_ids as $tag_id)
						$query->or_where('tag_id', $tag_id);
				$hidden = $query->where_close()->get();
				foreach ($hidden as $obj)
					$obj->delete();
			}

			if ($collection->save())
			{
				Session::set_flash('notice', 'Updated collection.');
				Response::redirect('collections/view/'.$id);
			}
			else
			{
				Session::set_flash('notice', 'Could not update collection #' . $id);
			}
		}
		
		$this->template->set_global('collection', $collection);
		
		/**
		$lists = array();
		if ($collection->type == 'List')
		{
			// get the user's Twitter Lists
			$userTwitterLists = TwitterAccount::getCurrentUserAccount()->getLists()->lists;

			if (!empty($userTwitterLists)) {
			  foreach ($userTwitterLists as $list) {
				$lists[$list->id_str] = $list->name.' ('.$list->member_count.')';
			  }
			}
		}
		*/

		$view = View::factory('collections/edit');
		$view->types = Config::get('collection.type_options');

		$this->template->title = 'Collections &rarr; '.$collection->name.' &rarr; Edit';
		$this->template->content = $view;
	}

  /**
   *
   * @param <type> $id
   */
	public function action_delete($id = null)
	{
		$id = (int)$id;
		$user_id = Arr::element(Session::get('access_token'), 'user_id');
		$collection = Model_Collection::find($id);
		if (!$collection || $collection->account_id != $user_id)
			Response::redirect('404');
		
		$colname = $collection->name;
		unset($collection); // Not using the model because it runs out of memory (100MB)!!
		$result = FALSE;
		try
		{
			// If there's an error, an exception is thrown. Otherwise, the number of affected rows is returned.
			// Since it's possible they return zero, the first two are ignored.
			$result_twt = DB::query('DELETE FROM `collections_tweets` WHERE collection_id = '.$id)->execute();
			$result_omt = DB::query('DELETE FROM `collections_omits`  WHERE collection_id = '.$id)->execute();
			$result     = DB::query('DELETE FROM `collections`        WHERE id = '.$id)->execute();
		}
		catch (Exception $ex)
		{
			Log::error((string)$ex);
		}
		if ($result)
			Session::set_flash('notice', 'Deleted collection "'.$colname.'".');
		else
			Session::set_flash('notice', 'Could not delete collection "'.$colname.'".');
		
		Response::redirect('dashboard');
	}
	
	
}

/* End of file collections.php */
