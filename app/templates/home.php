<?php $this->layout('layout'); ?>
<form role="form" method="post">
	<div class="form-group">
		<label for="query">Query</label>
		<textarea name="query" id="query" class="form-control" rows="8" placeholder="Type your SQL query here..."><?=$this->query?></textarea>
	</div>
	<button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-cog"></span> Perform analysis</button>
</form>
<?php if (isset($this->explainer)) : ?>
	<hr />
	<label>Result</label>
	<?php if (count($this->explainer->rows)>0): ?>
		<table class="table table-striped">
			<thead>
				<tr>
					<th>#</th>
					<th>select_type</th>
					<th>table</th>
					<th>type</th>
					<th>possible_keys</th>
					<th>key</th>
					<th>key_len</th>
					<th>ref</th>
					<th>rows</th>
					<th>Extra</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($this->explainer->rows as $row) : ?>
					<tr>
						<?php foreach($row->cells as $cell): ?>
							<?php if ($cell->isDanger()): ?>
								<td><span class="label label-danger"><span class="glyphicon glyphicon-fire"></span> <?=$cell->v?></span></td>
							<?php elseif ($cell->isSuccess()): ?>
								<td><span class="label label-success"><span class="glyphicon glyphicon-thumbs-up"></span> <?=$cell->v?></span></td>
							<?php elseif ($cell->isWarning()): ?>
								<td><span class="label label-warning"><?=$cell->v?></span></td>
							<?php else : ?>
								<td><?=$cell->v?></td>
							<?php endif; ?>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p>No result ?!#/p>
	<?php endif; ?>
<?php endif; ?>