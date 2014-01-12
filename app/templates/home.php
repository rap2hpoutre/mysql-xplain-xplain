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
					<?php
					foreach($this->explainer->header_row as $col => $infos) {
					?>
						<th><a class="a-black" href="javascript:;" onclick="$('#infos_text').html('<?=$infos;?>').parent().show();" ><?=$col;?></a></th>
					<?php
					}
					?>
				</tr>
			</thead>
			<tbody>
				<?php foreach($this->explainer->rows as $row) : ?>
					<tr>
						<?php foreach($row->cells as $cell): ?>
							<td id="<?=$cell->id?>">
								<a class="a-black" href="javascript:;" onclick="$('#infos_text').html('<?=$cell->info?>').parent().show();">
									<?php if ($cell->isDanger()): ?>
										<span class="label label-danger"><span class="glyphicon glyphicon-fire"></span> <?=$cell->v?></span>
									<?php elseif ($cell->isSuccess()): ?>
										<span class="label label-success"><span class="glyphicon glyphicon-thumbs-up"></span> <?=$cell->v?></span>
									<?php elseif ($cell->isWarning()): ?>
										<span class="label label-warning"><?=$cell->v?></span>
									<?php else : ?>
										<?=$cell->v?>
									<?php endif; ?>
								</a>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<div id="comp_infos" class="alert alert-info">
			<span id="infos_text"></span>
			<a target="_blank" href="<?= $this->mysql_base_doc_url;?>" class="mysq_doc_link"><span class="glyphicon glyphicon-question-sign" ></span></a>
		</div>
	<?php else : ?>
		<p>No result ?!#/p>
	<?php endif; ?>
<?php endif; ?>