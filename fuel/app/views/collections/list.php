<div class="pane">
	<h3>My Collections</h3>
	<?php if (empty($collections)) : ?>
		<div class="no-collections">
			<p>You currently do not have any collections setup!</p>
			<p>Collections allow you to collect data from Twitter. They can help you find and analyze trending terms, hashtags, mentions and links.</p>
			<?php echo Html::anchor('collections/create', 'Create your first collection!', array('id' => 'collection-button')); ?>
		</div>
	<?php else : ?>
	<div class="contents">
		<table class="list_collections">
			<?php foreach ($collections as $collection): ?>	
				<tr>
					<td class="collection-name"><?php echo $collection->name; ?></td>
					<td><?php echo Html::anchor('collections/view/' . $collection->id, 'View'); ?></td>
					<td><?php echo Html::anchor('collections/edit/' . $collection->id, 'Edit'); ?></td>
					<td><?php echo Html::anchor('collections/delete/' . $collection->id, 'Delete', array('onclick' => "return confirm('Are you sure you want to delete the collection \'".addslashes($collection->name)."\'? This cannot be undone.')")); ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>
	<div class="top-controls">
		<span><?php echo Html::anchor('collections/create', 'Create a new collection', array('id' => 'collection-button')); ?></span>
	</div>
	<?php endif; ?>
</div>

