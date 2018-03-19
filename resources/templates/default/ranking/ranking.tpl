<?php $this->setTextSection ('ranking', 'ranking'); ?>

<ul class="tabs">
	<li><a href="javascript:void(0);" onclick="windowAction(this,{'ranking':'players'});"><?=$this->getText ('playerRanking')?></a></li>
	<li class="active"><a href="javascript:void(0);" onclick="windowAction(this,{'ranking':'villages'});"><?=$this->getText ('villageRanking')?></a></li>
	<li><a href="javascript:void(0);" onclick="windowAction(this,{'ranking':'clans'});"><?=$this->getText ('clanRanking')?></a></li>
</ul>

<?php $pagelist_loc = 'top'; include (TEMPLATE_DIR.'blocks/pagelist.tpl'); ?>

<table class="tlist" style="margin-top: 5px;">
	<tr>
		<th style="text-align: center; width: 16%;">#</th>
		<th><?=$village?></th>		
		<?php $showCounter = count ($distances) > 1; ?>
		
		<?php foreach ($distances as $k => $v) { ?>
			<th style="text-align: right;">D<?=$showCounter ? $k+1 : null?></th>
		<?php } ?>
		
		<th style="width: 20%; text-align: center;"><?=$value?></th>
	</tr>
	
	<?php if (isset ($list_ranking)) { ?>
		<?php foreach ($list_ranking as $v) { ?>
	
		<tr <?php if ($v[4]) { echo 'class="current"'; } ?>>
			<td style="text-align: center;"><?=$v[0]?></td>
			<td>
				<a
					href="javascript:void(0);"
					onclick="openWindow('villageProfile', {'village':<?=$v[2]?>});" 
				><?=$v[1]?></a>
			</td>
		
			<?php foreach ($distances as $dist => $foo) { ?>
				<td style="text-align: right;"><?=$v[5][$dist]?></td>
			<?php } ?>
		
			<td style="text-align: center;"><?=$v[3]?></td>
		</tr>
		<?php } ?>
	<?php } ?>
</table>

<?php $pagelist_loc = 'bottom'; include (TEMPLATE_DIR.'blocks/pagelist.tpl'); ?>
<?php if (count ($distances) > 0) { ?>
	<ul>
		<?php foreach ($distances as $v) { ?>
			<li>D<?=$showCounter ? $k+1 : null?>: <?=Neuron_Core_Tools::putIntoText ($this->getText ('distance'), array ('name' => $v))?></li>
		<?php } ?>
	</ul>
<?php } ?>
