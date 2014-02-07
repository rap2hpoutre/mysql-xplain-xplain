<?php $this->layout('layout'); ?>
<form role="form" method="post">
	<div class="form-group" id="form-content">
		<label for="query">Query</label>
		<div class="pull-right">
			<a href="#" class="btn btn-default btn-sm" data-action="addContext" id="addContext"><span class="glyphicon glyphicon-plus">&nbsp;</span>Add contextal queries</a>
		</div>
		<textarea name="context_queries" id="context_queries" class="form-control" rows="8" placeholder="Add contextual queries here... You can add multiple queries separated by semicolon"></textarea>
		<textarea name="query" id="query" class="form-control colorizeIt" rows="8" placeholder="Type your SQL query here..."><?=$this->query?></textarea>
	</div>

	<button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-cog"></span> Perform analysis</button>
</form>
<?php if (isset($this->explainer)) : ?>
	<hr />
	<label>Result </label><small class="text-muted pull-right"><i> Click on each cell to understand result</i></small>
	<?php if (count($this->explainer->rows)>0): ?>
		<table class="table table-striped">
			<thead>
				<tr>
					<?php foreach($this->explainer->header_row as $col => $infos) : ?>
						<th><a class="a-black" href="#" data-action="showInfos" data-params="<?=$this->e(json_encode(array("infos" => $infos, "link" => $this->mysql_base_doc_url . "#explain_$col")));?>" ><?=$col;?></a></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach($this->explainer->rows as $row) : ?>
					<tr>
						<?php foreach($row->cells as $cell): ?>
							<td id="<?=$cell->id?>">
								<a class="a-black" href="#" data-action="showInfos" data-params="<?=$this->e(json_encode(array("infos" => $cell->info, "link" => $this->mysql_base_doc_url)));?>">
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
			<a id="mysql_doc_link" class="pull-right" target="_blank" href="#" class="mysq_doc_link"><span class="glyphicon glyphicon-question-sign" ></span></a>
			<span id="infos_text"></span>
		</div>
		<?php if (count($this->explainer->hints)) : ?>
			<hr />
			<label>Hints</label>
			<ol>
				<?php foreach($this->explainer->hints as $hint) : ?>
					<li><?=$hint?></li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	<?php else : ?>
		<p>No result ?!#/p>
	<?php endif; ?>
<?php endif; ?>
