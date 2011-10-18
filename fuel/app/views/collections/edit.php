<h1>Editing Collection "<?php echo $collection->name; ?>"</h1>

<?php
$addform = '';
if ($collection->omits)
{
	$addform .= '<fieldset><legend>Hidden tags - check to show again</legend>';
	foreach ($collection->omits as $hide)
	{
		$prefix = '';
			if ($hide['type'] == 'mention') $prefix = '@';
		elseif ($hide['type'] == 'hashtag') $prefix = '#';
		$addform .= '<div>'.Form::label(Form::checkbox('hide[]', $hide->id).' '.$prefix.$hide->content).'</div>';
	}
	$addform .= '</fieldset>';
}
else
	$addform .= 'There are currently no tags hidden from this collection.';
?>

<?php $view_data['addforminsecure'] = $addform; echo render('collections/_form', $view_data); ?>
