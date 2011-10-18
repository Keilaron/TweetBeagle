<?php if ($collection->indexed_tweet_count() < 100) : ?>
<div class="data-notice">
	<p>There seems to be a lack of data available for this collection. If this is a new collection the data might not have been indexed yet (this can take up to 30 minutes from the time of creation). It is also possible that the list or search is not returning very many results, you might want to consider adding more users to your list or adjusting your search terms.</p>
</div>
<?php endif; ?>
<form id="form_collection_filter" method="post" name="form_collection_filter">

<?php
$hidden_tags = array('mention' => array(), 'hashtag' => array(), 'term' => array(), 'link' => array());
if (!empty($hidden))
{
	foreach ($hidden as $hide)
		$hidden_tags[$hide['type']][] = $hide;
}
?>
<div class="side_select">
	<select id="filter_last_x_days" name="filter_last_x_days">
	<?php
		foreach ($filters['date'] as $val => $label)
		{
			$selected = '';
			if ($val == $filter_date) $selected = ' selected="selected"';
			echo "\t<option value=\"$val\"$selected>$label</option>\n";
		}
	?></select>
	<input type="submit" name="s_filter" value="Refresh">
	<input type="submit" id="s_filters_off" name="s_filters_off" value="Clear all filters">
	<?php
	if ($hasEditAccess)
		echo Html::anchor('collections/edit/'.$collection->id, 'Edit', array('class' => 'link-button'));
	?>
</div>

<script type="text/javascript" src="<?php echo Uri::create('assets/js/collections/filtering.js') ?>"></script>

<form id="form_collection_filter" method="post" name="form_collection_filter">
	<input type="hidden" name="collection_id" value="<?php echo $collection->id ?>" />
	<input type="hidden" name="graph_url" value="<?php echo Uri::create('graphs/collection_terms.json') ?>" />
	<input type="hidden" name="recent_tweets_action_url" value="<?php echo Uri::create('collectionsrest/recent_tweets.html') ?>" />

	<div>
	<ul class="dyn_filters" title="<?php echo $help['filter_list']; ?>"><li><?php echo $collection->name; ?></li><?php
		$filterInputs = '';
		foreach ($filter_settings as $fltr)
		{
			// Note: This code should match the code in public/assets/js/collections/filtering.js
			$className = ' class="'.$fltr->type.'_filter"';
			$classNameClear = ' class="'.$fltr->type.'_filter_clear"';
			echo "\t\t",'<li>',
				'<a rel="'.$fltr->id.'"'.$className.'>',
					Asset::img('minus-small-circle.png'),"</a>",
				'<a rel="'.$fltr->id.'"'.$classNameClear.'>'.$fltr->type.' &quot;'.Str::truncate_mid(htmlentities($fltr->content)),
					"&quot;</a></li>\n";
			$filterInputs .= "\t\t".'<input type="hidden" value="'.$fltr->id.'"'.$className.' name="filters[]">'."\n";
		}
	?></ul>
	</div>
	<?php
	echo $filterInputs;
	
	?>

<div class="filter_trigger_container">
	<?php echo $graph; ?>
	
	<div class="hidden-tags"><?php echo Model_Collections_Omit::show_list($hidden_tags['term'], 'term', $hasEditAccess); ?></div>
	<div class="top-tens">
	<div class="top-mentions">
	<table><tr><th><h3>Top Ten Mentions</h3></th><th width="75"><?php echo Html::help('Weight', $help, 'weight') ?></th><th width="125">Options</th></tr>
		<?php
			foreach ($ttmentions as $mention) {
				echo '<tr><td><a href="#" class="mention_filter" rel="'.$mention['id'].'">@'.$mention['content'].'</a></td>'.
					'<td class="weight">'.$mention['weight'].'</td>'.
					'<td class="options"><a href="#" class="mention_filter">'.Html::help('Filter&nbsp;by', $help, 'filter').'</a> ';
				if ($hasEditAccess)
					echo '| <a href="#" class="mention_hide" rel="'.$mention['id'].'">'.Html::help('Hide', $help, 'hide').'</a>';
				echo '</td></tr>',"\n";
			}
		?>
		</table>
		<div class="hidden-tags"><?php echo Model_Collections_Omit::show_list($hidden_tags['mention'], 'mention', $hasEditAccess); ?></div>
	</div>

	<div class="top-hashtags">
		<table><tr><th><h3>Top Ten Hashtags</h3></th><th width="75"><?php echo Html::help('Weight', $help, 'weight') ?></th><th width="125">Options</th></tr>
		<?php
			foreach ($tthashtags as $hashtag) {
				echo '<tr><td><a href="#" class="hashtag_filter" rel="'.$hashtag['id'].'">#'.$hashtag['content'].'</a></td>'.
					'<td class="weight">'.$hashtag['weight'].'</td>'.
					'<td class="options"><a href="#" class="hashtag_filter">'.Html::help('Filter&nbsp;by', $help, 'filter').'</a> ';
				if ($hasEditAccess)
					echo '| <a href="#" class="hashtag_hide" rel="'.$hashtag['id'].'">'.Html::help('Hide', $help, 'hide').'</a>';
				echo '</td></tr>',"\n";
			}
		?>
		</table>
		<div class="hidden-tags"><?php echo Model_Collections_Omit::show_list($hidden_tags['hashtag'], 'hashtag', $hasEditAccess); ?></div>
	</div>
	<div class='clear'></div>
	
	<div class="top-links">
		<table><tr><th><h3>Top Ten Links</h3></th><th width="75"><?php echo Html::help('Weight', $help, 'weight') ?></th><th width="125">Options</th></tr>
		<?php
			foreach ($ttlinks as $link) {
				$title = '';
				$shortlink = Str::truncate_mid($link['content'], 100);
				if ($shortlink != $link['content']) $title = ' title="'.$link['content'].'"';
				echo '<tr><td><a href="'.$link['content'].'"'.$title.' class="link_link" rel="'.$link['id'].'" target="_blank">'.$shortlink.'</a></td>'.
					'<td class="weight">'.$link['weight'].'</td>'.
					'<td class="options"><a href="#" class="link_filter" rel="'.$link['id'].'">'.Html::help('Filter&nbsp;by', $help, 'filter').'</a> ';
				if ($hasEditAccess)
					echo '| <a href="#" class="link_hide" rel="'.$link['id'].'">'.Html::help('Hide', $help, 'hide').'</a>';
				echo '</td></tr>',"\n";
			}
		?>
		</table>
		<div class="hidden-tags"><?php echo Model_Collections_Omit::show_list($hidden_tags['link'], 'link', $hasEditAccess); ?></div>
	</div>
	
	<div class="recent-tweets">
		<h3>Recent Tweets</h3><br>
		<div id="recent-tweets-pane"></div>
		<?php 
		/*
			foreach ($recent_tweets as $tweet)
			{
				// Regex the text for links, hashtags and mentions
				$text = preg_replace("/((http(s?):\/\/)|(www\.))([\w\.]+)([a-zA-Z0-9?&%.;:\/=+_-]+)/i", '<a href="http$3://$4$5$6" target="_blank">$2$4$5$6</a>', $tweet['text']);
				$text = preg_replace("/(?<=\A|[^A-Za-z0-9_])@([A-Za-z0-9_]+)(?=\Z|[^A-Za-z0-9_])/", '<a href="#" class="mention_filter" rel="$0">$0</a>', $text);
				$text = preg_replace("/(?<=\A|[^A-Za-z0-9_])#([A-Za-z0-9_]+)(?=\Z|[^A-Za-z0-9_])/", '<a href="#" class="hashtag_filter" rel="$0">$0</a>', $text);
				
				echo 				
				"<div style='padding-top: 0px;' class='stream'><div class='stream-item'><div class='stream-item-content'><div class='tweet-image'>".
						"<a href='http://twitter.com/".$tweet['screen_name']."' target='_blank'><img height='48' width='48' src='".$tweet['profile_image_url']."'></a>".
						"</div><div class='tweet-content'>";
				
				echo '<a href="http://twitter.com/'.$tweet['screen_name'].'" target="_blank"><h5>'.$tweet['screen_name'].'</h5></a>'.$text.
				'<br><small title="'.$tweet['created_at'].' UTC">'.Date::time_ago(strtotime($tweet['created_at'])).'</small><br><br></div></div></div></div>';
			}
		 * */
		?>
	</div>
	<div class="clear"></div>

</div>
</form>
