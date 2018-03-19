<h3><?=$this->getText ('afterUpgrade', 'silo', 'buildings')?></h3>

<table class="tlist">
	<tr>
		<th><?=$this->getText ('resource', 'silo', 'buildings')?></th>
		<th style="text-align: center;"><?=Neuron_Core_Tools::putIntoText ($this->getText ('level', 'silo', 'buildings'), array ($current_level)); ?></th>
		<th style="text-align: center;" class="highlight"><?=Neuron_Core_Tools::putIntoText ($this->getText ('level', 'silo', 'buildings'), array ($next_level)); ?></th>
	</tr>

	<?php foreach ($list_resources as $v) { ?>	
		<tr>
			<td style="width: 33%;"><?=$v['name']?></td>
			<td style="width: 34%; text-align: center;"><?=$v['now']?></td>
			<td style="width: 33%; text-align: center;" class="highlight"><?=$v['later']?></td>
		</tr>
	<?php } ?>
</table>
