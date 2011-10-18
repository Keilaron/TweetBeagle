<h2>Listing collections</h2>

<table class="list_collections">
	<tr>
		<th>Name</th>
		<th>Type</th>
		<th></th>
		<th></th>
		<th></th>
	</tr>

	<?php foreach ($collections as $collection): ?>	<tr>

		<td><?php echo $collection->name; ?></td>
		<td><?php echo $collection->type; ?></td>
		<td><?php echo Html::anchor('collections/view/'.$collection->id, 'View'); ?></td>
		<td><?php echo Html::anchor('collections/edit/'.$collection->id, 'Edit'); ?></td>
		<td><?php echo Html::anchor('collections/delete/'.$collection->id, 'Delete', array('onclick' => "return confirm('Are you sure you want to delete the collection \'".$collection->name."\'? This cannot be undone.')")); ?></td>
	</tr>
	<?php endforeach; ?></table>

<br />

<?php echo Html::anchor('collections/create', 'Add new Collection'); ?>
