<!-- Formulaire -->
<div class="container">
	<form role="form" method="post">
		<div class="form-group">
			<label for="query">Query</label>
			<textarea name="query" id="query" class="form-control" rows="8" placeholder="Type your SQL query here..."></textarea>
		</div>
		<button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-cog"></span> Perform analysis</button>
	</form>
	<?php if (isset($explain_results) && is_array($explain_results)) : ?>
		<hr />
		<label>Result</label>
		<table class="table table-striped">
		<?php foreach($explain_results as $explain_result) : ?>
			<tr>
				<?php foreach($explain_result as $column) : ?>
					<td><?php echo $column; ?></td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
		</table>
	<?php endif; ?>
</div>