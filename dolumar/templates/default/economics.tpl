<?php $this->setTextSection ('economics', 'village'); ?>

<p class="<?=$honour < 100 ? 'false' : 'true' ?>" style="text-align: center;">
	<?=Neuron_Core_Tools::putIntoText ($this->getText ('honour'), array ('honour' => $honour));?>
</p>

<h2><?=$hourly?></h2>
<div class="fancybox">
	<table class="resources">

		<tr>
			<!--<th class="alignLeft"><?=$resources?></th>-->
			<th>&nbsp;</th>
			<th class="number" colspan="2"><?=$stock?></th>
			<!--<th class="resources"><?=$max?></th>-->
		
			<th class="number"><?=$bruto?></th>
			<th class="number"><?=$consuming?></th>
		
			<th class="number"><?=$income?></th>
		</tr>
	
		<?php foreach ($list_resources as $v) { ?>
		<tr>
			<!--<td><?=$v[0]?>:</td>-->
		
			<td><span class="resource <?=$v['resource']?>" title="<?=$v[0]?>">&nbsp;<span>&nbsp;</span></span></td>
		
			<td class="right"><span class="increasing amount" title="<?=$v[3]?>/<?=$v[2]?>"><?=$v[1]?></span> /</td>
			<td class="left"><?=$v[2]?></td>
		
			<td class="number"><?=$v['bruto']?></td>
			<td class="number"><?=$v['consuming']?></td>
		
			<?php if ($v[3] < 0) { ?>
				<td class="number"><?=$v[3]?></td>
			<?php } else { ?>
				<td class="number"><?=$v[3]?></td>
			<?php } ?>
		</tr>
		<?php } ?>

	</table>
</div>

<h2><?=$runes?></h2>
<div class="fancybox">
	<table>
		<tr>
			<th>&nbsp;</th>
			<th><?=$this->getText ('rune'); ?></th>
			<th class="number"><?=$this->getText ('available'); ?></th>
			<th class="number"><?=$this->getText ('used'); ?></th>
			<th class="number"><?=$this->getText ('usedpercentage'); ?></th>
		</tr>

		<?php if (isset ($list_runes) && is_array ($list_runes)) { ?>
			<?php foreach ($list_runes as $v) { ?>
				<tr>		
					<td>
						<span class="rune <?=$v['key']?>" title="<?=$v['name']?>">&nbsp;<span>&nbsp;</span></span>
					</td>
				
					<td>
						<?=$v['name']?>
					</td>
				
					<td class="number">
						<?=$v['available']?>
					</td>
				
					<td class="number">
						<?=$v['used']?>
					</td>
				
					<td class="number">
						<?=round ($v['used_percentage'])?>%
					</td>
				</tr>
			<?php } ?>
		<?php } else { ?>
		<tr>
			<td colspan="2"><?=$norunes?></td>
		</tr>
		<?php } ?>

	</table>
</div>
