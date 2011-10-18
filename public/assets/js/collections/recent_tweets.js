
$(document).ready(function() 
{
	var $form      = $('#form_collection_filter');
	var recent_tweets_action_url = $form.find('input[name="recent_tweets_action_url"]').val();
	var params = $('#form_collection_filter').serialize();

	$('#recent-tweets-pane').load(recent_tweets_action_url, params);
});
