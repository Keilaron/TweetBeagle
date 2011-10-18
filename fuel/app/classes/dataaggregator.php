<?php

/**
 * Description of DataAggregator
 *
 * @author bushra
 */
class DataAggregator
{
	
	const TERMS_IN_GRAPH_LIMIT = 10;
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	
  /**
   * @var string The tag type (link, hashtag, term, etc...)
   */
  protected $tagType;
  /**
   * @var int The ID of the target collection
   */
  protected $collectionId;
	/**
	 * Colours to use in the chart.
	 */
	public $colours = array(
		'#3366CC', // blue
		'#DC3912', // red
		'#109618', // green
		'#FF9900', // orange
		'#0099C6', // teal
		'#990099', // purple
		'#DD4477', // dark pink
		'#CC6600', // brown
		'#FFFF00', // yellow
		'silver',
		'black',
	);
  /**
   * @var mixed Any date string representation accepted by strtotime().
   */
  protected $since;
  /**
   * @var string The calcualted date unit of the graph.
   */
  protected $dateUnit;
  /**
   * @var int The cutoff limit of the top found results
	 * Note: The limit value is set in checkRequiredProperties()
   */
  protected $limit = self::TERMS_IN_GRAPH_LIMIT;
  /**
   * @var array The Top tags container used by the getTopTags() query.
   */
  protected $topTags = array();
  /**
   * @var array Tags to filter in in the getTopTags() query.
   */
  protected $filterTags = array();

  /**
   * @var array Tags to filter out in the getTopTags() query.
   */
  protected $hiddenTags = array();

  /**
   * The structure of the $topTagsHits array is:
   * [date][tag_id] => {int}
   *
   * Example:
   * ['2011-04-30'][543] => 3
   * ['2011-05-02'][827] => 1
   * @var array The Top tags-hits container used by the buildDataForChart() query.
   */
  protected $topTagsHits = array();


  /**
   *
   */
  public function __construct()
  {

  }

  /**
   * Retrieves from the database the top (x) tags of a specific collection
   * since the time given by ($since).
   */
  public function getTopTags()
  {
    $this->checkRequiredProperties();

    $user_id = Arr::element(Session::get('access_token'), 'user_id');
    $date    = date('Y-m-d H:i:s', strtotime($this->since));
    //Debug::dump('Getting the tweets since: '.$date);

    // Are there any tags being "permanently" hidden from the results?
    $hidden = '';
    if (!empty($this->hiddenTags))
    	$hidden = 'AND tags.id NOT IN ('.implode(',', $this->hiddenTags).')';

    $filter1 = $filter2 ='';
    if (!empty($this->filterTags))
		{
    	foreach ($this->filterTags as $i => $tag_id)
			{
				$tag_id = (int) $tag_id;
				
				$filter1 .= " LEFT JOIN tweets_tags f{$i} ON (f{$i}.tweet_id = tweets.id AND f{$i}.tag_id = {$tag_id}) ";
				$filter2 .= " AND f{$i}.tweet_id IS NOT NULL ";
			}
		}

    // Grab the top tags to create the summary data view
		$sql = "SELECT
					    tags.id,
					    tags.content,
					    tags.type,
					    SUM(tweets_tags.weight) AS weight
				  	FROM tweets_tags
				  	  LEFT JOIN tweets ON (tweets.id = tweets_tags.tweet_id AND tweets.created_at > '$date')
				  	  LEFT JOIN tags ON (tags.id = tweets_tags.tag_id AND tags.type = '{$this->tagType}' $hidden)
			  		  LEFT JOIN collections_tweets ON (collections_tweets.tweet_id = tweets.id)
			  		  $filter1
			  		WHERE (
				  	  collections_tweets.collection_id = {$this->collectionId}
				  	  AND tweets.id IS NOT NULL
			  		  AND tags.id IS NOT NULL
			  		  $filter2
			  		)
			  		GROUP BY tweets_tags.tag_id
			  		ORDER BY weight DESC
			  		LIMIT {$this->limit}";

    $query = DB::query($sql);
    $result = $query->execute();
    $this->topTags  = array();
    
		reset($this->colours);
    foreach ($result as $r)
    {
			$r['colour'] = current($this->colours);
      $this->topTags[$r['id']] = $r;
			next($this->colours);
    }

		return $this->topTags;
  }

	/**
	 * 
	 */
  public function buildDataForChart()
  {
    $this->checkRequiredProperties();

    $user_id = Arr::element(Session::get('access_token'), 'user_id');
    $date    = date('Y-m-d H:i:s', strtotime($this->since));
		
		// ignore tweets which have filtered tags
		$filter1 = $filter2 ='';
    if (!empty($this->filterTags))
		{
    	foreach ($this->filterTags as $i => $tag_id)
			{
				$tag_id = (int) $tag_id;
				
				$filter1 .= " LEFT JOIN tweets_tags f{$i} ON (f{$i}.tweet_id = tweets.id AND f{$i}.tag_id = {$tag_id}) ";
				$filter2 .= " AND f{$i}.tweet_id IS NOT NULL ";
			}
		}

    if (empty($this->topTags))
      $this->getTopTags();

    if (!empty($this->topTags))
      $topTagsIds = Lib_Arr::extractIds($this->topTags);


    if (!empty($topTagsIds))
    {
			$GRAPH_POINTS_LIMIT = 2880;
      // Next we grab the dates of the teweets in which the top tags above have 
      // been mentioned
      $sql = "SELECT
                tweets_tags.tag_id,
                tweets.created_at AS created_at,
                tweets_tags.weight
              FROM tweets_tags 
                LEFT JOIN tags ON tags.id = tweets_tags.tag_id
                LEFT JOIN tweets ON tweets.id = tweets_tags.tweet_id
                LEFT JOIN collections_tweets ON collections_tweets.tweet_id = tweets.id
								$filter1
              WHERE tweets_tags.tag_id IN (".implode(',', $topTagsIds).")
                AND collections_tweets.collection_id = {$this->collectionId}
                AND tweets.created_at > '$date'
								$filter2
              ORDER BY created_at DESC
             ";
			
      $query  = DB::query($sql);
      $result = $query->execute();

			$density   = $GRAPH_POINTS_LIMIT / self::TERMS_IN_GRAPH_LIMIT;
			$interval  = new Interval($this->since);
			$units     = $interval->getDateUnits();
			$unit      = '';
			
			foreach ($units as $unit => $value)
			{
				if ($value <= $density)
				{
					$interval->setDefaultUnit($unit);
					$this->dateUnit = $unit;
					break;
				}
			}
			
			// Populate the graph's data container with empty values using the interval iterator
			$topTagsHits = array();
			$interval->iterate(1, function($nextTimeStamp) use(&$topTagsHits, $unit) 
			{
				// determine the hits key based on the date unit
				switch ($unit)
				{
					case 'hours':	$key = date('D gA', $nextTimeStamp); break; // e.g.: 8:00 PM
					case 'days' :	$key = date('M j, y', $nextTimeStamp); break; // e.g.: Oct 17
					case 'weeks':	$key = DataAggregator::getWeekLabel($nextTimeStamp); break; // e.g.: Week 23
					default     : throw new Exception("Unrecognized date unit ($unit) to be used as a key for tag hits");
				}
				
				$topTagsHits[$key] = array();
			});
			
			//Log::debug(var_export($topTagsHits, true));
			
			$this->topTagsHits = $topTagsHits;
			
      foreach ($result as $r)
      {
				// determine the hits key based on the date unit
				switch ($this->dateUnit)
				{
					case 'hours':	
						$key = date('D gA', strtotime($r['created_at'])); // e.g.: 8:00 PM
						break;
					case 'days':	
						$key = date('M j, y', strtotime($r['created_at'])); // e.g.: Oct 17
						break;
					case 'weeks':	
						$key = self::getWeekLabel(strtotime($r['created_at'])); // e.g.: Week 23
						break;
					default:
						throw new Exception("Unrecognized date unit ({$this->dateUnit}) to be used as a key for tag hits");
				}
				
				$point_count = 0;
				
				// sanity checks to prevent "Undefined index" notice
				if (!isset($this->topTagsHits[$key]))
					$this->topTagsHits[$key] = array();
						
				if ($point_count <= $GRAPH_POINTS_LIMIT)
				{	
					if (!isset($this->topTagsHits[$key][$r['tag_id']]))
					{
						$this->topTagsHits[$key][$r['tag_id']] = 0;
						$point_count++;
					}
				
	        $this->topTagsHits[$key][$r['tag_id']] += $r['weight'];
				}
      }
    }
    else
    {
      Log::debug('The top-tags query returned an empty result set!');
    }
		
  }
	
	/**
	 * Determine the week-of-year in which a particular $date falls and 
	 * calculate the week's boundary dates and build the label from them. 
	 * @param int $date timestamp of the date we seek to locate
	 * @return string The final label of the week
	 */
	public static function getWeekLabel($date)
	{
		list($weekNumber, $year) = explode(',', date('W,Y', $date));
		
		$startDate  = date('M j', strtotime($year.'W'.$weekNumber));
		$endDate    = date('M j', strtotime($year.'W'.$weekNumber.'7'));
		
		return $startDate.' - '.$endDate;
	}
 
	/**
	 * 
	 */
	public function getGoogleVisData()
  {
		if (empty($this->topTagsHits))
      $this->buildDataForChart();

    $json = array();
    $cols = array();
    
    $cols[0] = array(
      'id'    => $this->dateUnit,
      'label' => ucfirst($this->dateUnit),
      'type'  => 'string' // this should be a Date type but the Line Chart requires a string
    );

    reset($this->colours);
		foreach ($this->topTags as $t)
		{
			$cols[] = array(
				'id'    => $t['type'].'_'.$t['id'],
				'label' => $t['content'],
				'type'  => 'number',
				'colour'=> current($this->colours),
			);
			next($this->colours);
		}
    
    $rows = array();
    
    foreach ($this->topTagsHits as $date => $tags)
    {
      $row = array();
      $row[] = array('v' => $date); // append Date column value

      foreach ($this->topTags as $t)
      {
        if (is_array($tags) && in_array($t['id'], array_keys($tags)))
          $row[] = array('v' => (float)$tags[$t['id']]);
        else
          $row[] = null;
      }

      $rows[] = array( 'c' => $row );
    }
    
    $json['cols'] = $cols;
    $json['rows'] = $rows;
    
    return $json;
	}

	/**
	 * Call this after getGoogleVisData() or buildDataForChart() to know if
	 * any results were returned by the database.
	 */
	public function haveData()
	{
		return !empty($this->topTags) && !empty($this->topTagsHits);
	}

  /**
   * Get/Set The tag type (link, hashtag, term, etc...)
   */
  public function getTagType() { return $this->tagType; }
  public function setTagType($tagType) { $this->tagType = $tagType; return $this; }

  /**
   * Get/Set The ID of the target collection
   */
  public function getCollectionId() { return $this->collectionId; }
  public function setCollectionId($collectionId) { $this->collectionId = $collectionId; return $this; }

  /**
   * Get/Set Any string representation of a date that is accepted by PHP's strtotime() function.
   */
  public function getSince() { return $this->since; }
  public function setSince($since) { $this->since = $since; return $this; }

  /**
   * Get/Set string 
   */
  public function getDateUnit() { return $this->dateUnit; }
  //public function setDateUnit($dateUnit) { $this->dateUnit = $dateUnit; return $this; }

  /**
   * Get/Set The cutoff limit of the top found results
   */
  public function getLimit() { return $this->limit; }
  public function setLimit($limit) { $this->limit = $limit; return $this; }
	
	/**
	 * Get/Set Tags to filter in in the getTopTags() query.
	 */
	public function getFilters() { return $this->filterTags; }
	public function setFilters(array $filterTags)
	{
		$this->filterTags = array();
		foreach ($filterTags as $tag)
			if ($tag == (int)$tag)
				$this->filterTags[] = $tag;
		return $this;
	}
	
	/**
	 * Get/Set Tags to filter out in the getTopTags() query.
	 */
	public function getHidden() { return $this->hiddenTags; }
	public function setHidden(array $hiddenTags)
	{
		$this->hiddenTags = array();
		foreach ($hiddenTags as $tag)
			if ($tag == (int)$tag)
				$this->hiddenTags[] = $tag;
		return $this;
	}

  /**
   * Validates that the required properties are set and sets the optional ones
   * if they are not.
   */
  protected function checkRequiredProperties()
  {
    if (empty($this->tagType) ||
        empty($this->collectionId) ||
        empty($this->since)
        )
    {
      throw new InvalidArgumentException(
        'Missing properties (tagType, collectionId, since) must be set before
        invoking any of the action methods');
    }

    $this->limit = $this->limit ?: self::TERMS_IN_GRAPH_LIMIT;
  }
}

/* End of dataaggregator.php */
