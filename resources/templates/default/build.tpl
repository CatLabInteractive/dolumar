<?php

//echo '<p><?=$intro</p>';

if (isset ($error))
{
	echo '<p class="' . ($errorV == 'done' ? 'true' : 'false') . '">'.$error.'</p>';
}
else {
	echo '<p>'.$click.'</p>';
}
?>

<p><a href="javascript:void(0);" onclick="openWindow('bonusbuildings', {'village':<?=$village?>});">Bonus Buildings</a></p>

<div>
<?php if (isset ($list_buildings)) { ?>

	<!-- Building list -->
	<?php
	/*
	<div class="list-icon-building">
		<?php foreach ($list_buildings as $v) { ?>
			<a href="javascript:void(0);" title="<?=$v[0]?>" onclick="toggleDivs('build_<?php echo $v[4]; ?>', 'buildLinks', 'buildBuildingList');"><img src="<?=$v[3]?>" class="icon-building" alt="<?=$v[0]?>" /></a>
		<?php } ?>
	</div>
	*/?>

	<?php foreach ($list_buildings as $v) { ?>
	
	<div <?=$v['canBuild'] ? null : 'class="shaded"'?>>
		<table>
			<tr>
				<td colspan="2">
					<p class="sidenote"><?=$v[5]?></p>
					<h3><?=$v[0]?></h3>
					<p class="resources-cost"><?=$v[1]?></p>
				</td>
			</tr>
			
			<tr>
				<td style="width: 50px;">
					<?php if ($v['canBuild']) { ?>
						<a href="javascript:void(0);" onclick="<?=$v['action']->getAction()?>">
					<?php } ?>
						<img src="<?=$v[3]?>" class="icon-building" alt="<?=$v[0]?>" />
					<?php if ($v['canBuild']) { ?>
						</a>
					<?php } ?>
				</td>
				
				<td>
					<?php
						if (!empty ($v[2])) {
							echo '<p>'.$v[2].'</p>';
						}
					?>
					
					<p>
						<?php if ($v['canBuild']) { ?>
							<!--<a href="javascript:void(0);" onclick="selectBuildLocation (this, <?=$v[4]?>, 'building_<?=$v[4]?>', '<?=$v[6]?>', '<?=$selectRune?>');"><?=$construct?></a>-->
							
							<a href="javascript:void(0);" onclick="<?=$v['action']->getAction()?>"><?=$construct?></a>
							
						<?php } else { ?>
							<?=Neuron_Core_Tools::putIntoText ($this->getText ('upgradeFirst', 'build', 'building'), array ('buildings' => $v['myBuildingsName']));?>
						<?php } ?>
					</p>
				</td>
			</tr>
		</table>
	</div>
	<?php } ?>
<?php } ?>
</div>
