<?php if (Session::get_flash('errors')): ?>
	<ul class="errors">
		<?php foreach (Session::get_flash('errors') as $error) : ?>
		<li><?php echo $error; ?></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
<?php echo Form::open(); ?>
<table id="collection-form">
	<tr>
		<td class="label-column"><label for="collection-name">Collection name:</label></td>
		<td class="input-column"><?php echo Form::input('collection_name', Input::get_post('collection_name', isset($collection) ? $collection->name : ''), array('id' => 'collection-name')); ?></td>
	</tr>
	<?php if (!isset($collection)) : ?>
	<tr>
		<td class="label-column"><label for="collection-type">Collection type:</label></td>
		<td class="input-column"><?php echo Form::select('type', Input::get_post('type', isset($collection) ? $collection->type : ''), $types, array('id' => 'collection-type')); ?></td>
	</tr>
  <?php endif; ?>
	<?php if (!isset($collection) || $collection->type == 'list') : ?>
	<tr class="reference-field" id="collection-reference-list">
		<td class="label-column"><label for="reference-list">Twitter list:</label></td>
		<td class="input-column">
		<?php
			if (empty($collection))
				echo Form::select('reference_list', Input::post('reference_list', isset($collection) ? $collection->reference : ''), $lists, array('id' => 'reference-list'));
			else
			{
				$tweeter = Model_Tweeter::find_by_id($collection->account_id);
				echo '<span class="no-edit">' . $collection->reference . ' (<a href="http://twitter.com/'.$tweeter->screen_name.'/lists">edit your list on Twitter</a>)</span>';
			}
		?>
		</td>
	</tr>
	<?php endif; ?>
  <?php if (!isset($collection) || ($collection->type == 'search')) : ?>
	<tr class="reference-field" id="collection-reference-search">
		<td class="label-column"><label for="reference-search">Search term(s):</label></td>
		<td class="input-column"><?php echo Form::input('reference_search', Input::get_post('reference_search', isset($collection) ? $collection->reference : ''), array('id' => 'reference-search')); ?><div class="note">See <a href="http://search.twitter.com/operators" target="_blank">Twitter's search operators page</a> for tips on improving your results.</div><?php if (!empty($collection)) : ?><div class="note">Note: Search term changes are not retroactive.</div><?php endif; ?></td>
	</tr>
	<?php endif; ?>
	<tr>
		<td class="label-column"><label for="collection-public">Make this collection public?</label></td>
		<?php $checkbox_options = array('id' => 'collection-public'); if (isset($collection) && $collection->public) $checkbox_options['checked'] = 'checked'; ?>
		<td class="input-column"><?php echo Form::checkbox('collection_public', 'public', $checkbox_options); ?></td>
	</tr>
</table>
<div class="extra">
	<?php if (!isset($collection)) : ?>
	<p>Note: If the list you wish to add is not here, you may <a href="<?php URI::Create('/collections/create') ?>">reload the lists</a>.
	Sometimes, Twitter does not return all the lists.</p>
	<?php endif; ?>
	<?php if (!empty($addforminsecure)) echo html_entity_decode($addforminsecure); ?>
</div>
<div class="actions">
	<?php echo Form::submit(); ?>
</div>
<?php echo Form::close(); ?>
