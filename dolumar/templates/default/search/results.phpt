<?php $this->setTextSection ('results', 'search'); ?>

<h2><?=$this->getText ('results'); ?></h2>
<?php $pagelist_loc = 'top'; include (TEMPLATE_DIR.'blocks/pagelist.tpl'); ?>

<?php if (isset ($list_results)) { ?>

	<?php $alternate = true; ?>

	<table>
		<tr>
			<th><?=$this->getText ('nickname')?></th>
			<th class="village-name"><?=$this->getText ('village')?></th>
			<th class="networth"><?=$this->getText ('networth')?></th>
			<th class="distance-long"><?=$this->getText ('distance')?></th>
		</tr>
	
		<?php foreach ($list_results as $v) { ?>
		
			<?php
				if ($alternate)
				{
					$alternate = false;
					$rowclass = "odd";
				}
				else
				{
					$alternate = true;
					$rowclass = "even";
				}
			?>
		
			<tr class="<?=$rowclass?>">
				<td>
					<!--<a href="javascript:void(0);" onclick="openWindow('playerProfile',{'plid':<?=$v['id']?>});"><?=$v['nickname']?></a>-->
					<?=$v['displayname']?>
				</td>
				<td class="village-name">
					<!--<?=$v['village']?>-->
					<?=$v['displayvillage']?>
				</td>
				<td class="networth"><?=$v['networth']?></td>
				<td class="distance-long"><?=$v['distance']?></td>
			</tr>
		<?php } ?>
	</table>

<?php } else { ?>
	<p><?=$this->getText ('noresults'); ?></p>
<?php } ?>

<?php $pagelist_loc = 'bottom'; include (TEMPLATE_DIR.'blocks/pagelist.tpl'); ?>

<p>
	<a href="javascript:void(0);" onclick="windowAction(this,{'go':'home'});"><?=$this->getText ('return');?></a>
</p>
