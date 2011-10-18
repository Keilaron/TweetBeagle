<?php

/**
 * Description of graphs
 *
 * @author bushra
 */
class Controller_Graphs extends Controller_Rest
{
	/**
	 * An AJAX action that loads a collection graph 
	 * @param int $id The collection ID
	 */
	public function get_collection_terms()
	{
		$graphs = array();
		$graphView = View::factory('graphs/linechart');
		
		$tagType             = 'term';
		$filter_date         = Input::get_post('filter_last_x_days');
		$collection_id       = Input::get_post('collection_id');
		$filter_settings_ids = Input::get_post('filters', array());
		
		$collection = Model_Collection::find($collection_id);
		$hide_ids   = array_keys($collection->omits);
		
		$filter_settings = array();
		foreach ($filter_settings_ids as $tag_id)
			$filter_settings[$tag_id] = Model_Tag::find($tag_id);
		
		$da = new DataAggregator();
		$da->setTagType($tagType)
			 ->setCollectionId($collection_id)
			 ->setFilters($filter_settings_ids)
			 ->setHidden($hide_ids)
			 ->setSince($filter_date.' days ago');

		$chartData = $da->getGoogleVisData();
		
		if ($da->haveData())
		{
			$graphs[] = array(
				'id' => $tagType, 
				'type' => 'line', 
				'tagType' => $tagType, 
				'data' => $chartData
			);
		}
		else
		{
			$graphs[] = array(
				'id' => $tagType, 
				'tagType' => $tagType, 
				'message' => 'There is not enough data for the '.$tagType.' graph; Please choose another time period, less filters, or wait for more tweets to be collected.');
		}
		
		$this->response($graphs);
	}
	
}

?>
