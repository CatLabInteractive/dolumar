<?php $this->setTextSection ('ranking', 'ranking'); ?>

<ul class="tabs">
	<li class="active"><a href="javascript:void(0);" onclick="windowAction(this,{'ranking':'players'});"><?=$this->getText ('playerRanking')?></a></li>
	<li><a href="javascript:void(0);" onclick="windowAction(this,{'ranking':'villages'});"><?=$this->getText ('villageRanking')?></a></li>
	<li><a href="javascript:void(0);" onclick="windowAction(this,{'ranking':'clans'});"><?=$this->getText ('clanRanking')?></a></li>
</ul>

<?php $pagelist_loc = 'top'; include (TEMPLATE_DIR.'blocks/pagelist.tpl'); ?>

<table class="tlist" style="margin-top: 5px;">
	<tr>
		<th style="text-align: center; width: 16%;">#</th>
		<th><?=$village?></th>
		
		<th style="width: 20%; text-align: center;"><?=$value?></th>
	</tr>
	
	<?php if (isset ($list_ranking)) { ?>
		<?php foreach ($list_ranking as $v) { ?>
	
		<tr <?php if ($v[4]) { echo 'class="current"'; } ?>>
			<td style="text-align: center;"><?=$v[0]?></td>
			<td>
				<a
					href="javascript:void(0);"
					onclick="openWindow('PlayerProfile', {'plid':<?=$v[2]?>});" 
				><?=$v[1]?></a>
			</td>
			<td style="text-align: center;"><?=$v[3]?></td>
		</tr>
		<?php } ?>
	<?php } ?>
</table>

<?php $pagelist_loc = 'bottom'; include (TEMPLATE_DIR.'blocks/pagelist.tpl'); ?>
