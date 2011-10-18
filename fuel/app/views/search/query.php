<script type="text/javascript">
jQuery(function ($) {
  /* fetch elements and stop form event */
  $("form.list-form").submit(function (e) {
    /* stop event */
    e.preventDefault();
    form = $(this);
    /* set spinner */
    $(this).parent().parent().find('span').removeClass('ready');
    $(this).parent().parent().find('span').addClass('active');
    /* send ajax request */
    $.post(BASE_URL+'twitter/add_to_list', {
		twitter_id: $(this).find('input').val(),
		list_id: $(this).find('select').val()
    }, function (data) {
    	form.parent().find('select').hide();
    	form.parent().find('span').removeClass('active');
    	form.parent().find('span').addClass('added');
    	form.parent().find('span').html('Added To List');
    	// Wait
    	setTimeout(function(){
    		form.parent().find('span').removeClass('added');
    		form.parent().find('span').addClass('ready');
    		form.parent().find('span').html('Add To List');
    	}, 2000);
    });
  });

$(".add-to-list-btn").click(function(e) {
	e.preventDefault();
	if ($(this).find('span').html() == 'Hide')
	{
		$(this).find('span').html('Add To List');
	} else {
		$(this).find('span').html('Hide');
	}
	$(this).parent().find('div').find('select').toggle();
});

$(".add_list").change(function() {
	$(this).closest("form").submit();
});

});

</script>

<?php 
echo Html::anchor('collections/create/?type=search&reference_search='.Input::get('query').'&collection_name=Search%20'.Input::get('query'), 'Create Collection From Search', array('class' => 'search-createcol'));
?>

<div class='text-search-div'>
<?php
echo Form::open(array('action' => 'search/query/1', 'method' => 'get')); ?>

<?php
echo Form::input('query', Input::get('query'), array('size' => '50', 'style' => '', 'class' => 'search'));

echo Form::submit('submit', 'Search', array('class' => 'search-result'));
echo "<a href='http://search.twitter.com/operators' target='_blank' style='margin-left: 5px;'>Search Tips</a>";
echo Form::close();

?>
</div>
 <div class="clear"></div>

<div class='pagination'>
<?php
	if($results->results)
	{
		echo "Page #".$page."&nbsp&nbsp";
		$nextpage = $page + 1;
		$prevpage = $page - 1;
		if ($page > 1)
		{
			echo Html::anchor('search/query/'.$prevpage."?query=".Input::get('query'), '< Previous');
			echo "&nbsp&nbsp";
			echo Html::anchor('search/query/'.$nextpage."?query=".Input::get('query'), 'Next >');
		} else {
			echo '<a href="#">&lt Previous</a>&nbsp&nbsp';
			echo Html::anchor('search/query/'.$nextpage."?query=".Input::get('query'), 'Next >');
		}
	}
?>
</div>
<div class="clear"></div>
<div class="stream" style="padding-top: 25px;">
<?php
	if($results->results)
	{
		$user_id = Arr::element(Session::get('access_token'), 'user_id');
		$collections = Model_Collection::find()->where(array(
			array('account_id', '=',$user_id),
			array('type', '=', 'list')
			)
			)->get();
		foreach ($results->results as $result)
		{
			echo "<div class='stream-item'>".
					"<div class='stream-item-content'>".
						"<div class='tweet-image'>".

							"<a href='http://twitter.com/".$result->from_user."' target='_blank'><img height='48' width='48' src='".$result->profile_image_url."'></a>".
						"</div>".
							"<div class='tweet-content'>".
								"<div class='stream-user-buttons'>";
								echo Form::open(array('action' => 'twitter/add_to_list', 'method' => 'post', 'class' => 'list-form'), array('twitter_id' => $result->from_user));
								echo "<button value='Actions' class='btn list add-to-list-btn' id='add-to-list'><span class='ready'>Add To List</span></button><div id='select-div'><select class='add_list' id='addList' style='display:none'>";
							echo "<option value='#' selected='selected'>Select a list</option>";
            		foreach ($collections as $thiscollection)
            		{
            			echo "<option value='".$thiscollection->reference."'>".$thiscollection->name."</option>";
                	}
            	echo "</select></div></div>";
				echo Form::close().
								"<div class='tweet-row'>".
									"<h5><a href='http://twitter.com/".$result->from_user."' target='_blank'>".$result->from_user."</a><br>".
									"<h5>".
								"</div>".
								"<div class='tweet-row'>".
									"<div class='tweet-text'>";
									$linked_text = preg_replace("/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/", "<a href=\"\\0\" rel=\"nofollow\" target='_blank'>\\0</a>", $result->text);
									$hashed_text = preg_replace('/(^|\s)#(\w*[a-zA-Z_]+\w*)/', "<a href='1?query=\\2' rel=\"nofollow\">\\0</a>", $linked_text);
									$text = preg_replace("/(?<=\A|[^A-Za-z0-9_])@([A-Za-z0-9_]+)(?=\Z|[^A-Za-z0-9_])/", '<a href="1?query=\\1" class="mention_filter" rel="$0">$0</a>', $hashed_text);
									
									
									echo $text."<br>".
									"</div>".
								"</div>".
			
								"<div class='tweet-row'>".
								"<small title='".$tweet['created_at']." UTC'>".Date::time_ago(strtotime($result->created_at))."</small>".
								"</div>".
							"</div>".
						"</div>".
					"</div>";
		}
	} else {
		echo "Your search returned no results";
	}
?>
</div>
<div class='pagination'>
<?php
	if($results->results)
	{
		if ($page > 1)
		{
			echo Html::anchor('search/query/'.$prevpage."?query=".Input::get('query'), '< Previous');
			echo "&nbsp&nbsp";
			echo Html::anchor('search/query/'.$nextpage."?query=".Input::get('query'), 'Next >');
		} else {
			echo '<a href="#">&lt Previous</a>&nbsp&nbsp';
			echo Html::anchor('search/query/'.$nextpage."?query=".Input::get('query'), 'Next >');
		}
	}
?>
</div>
