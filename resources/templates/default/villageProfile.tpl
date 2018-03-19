<?php $this->setTextSection ('profile', 'village'); ?>

<?php if (isset ($notActive)) { ?>
	<p class="false"><?=$notActive?></p>
<?php } ?>

<div class="fancybox">

	<table class="tlist" style="margin-top: 5px;">

		<tr>
			<th colspan="2"><?=$village?></th>
		</tr>

		<tr>
			<td style="width: 33%;"><?=$location?></td>
			<td><?=$location_value?> <a href="javascript:void(0);" onclick="mapIsoJump('<?=$locX?>','<?=$locY?>');"><img src="<?=IMAGE_URL?>jump.png" style="border: none;" /></a></td>
		</tr>

		<tr>
			<td style="width: 33%;"><?=$rank?></td>
			<td><?=$rank_value?></td>
		</tr>
	
		<tr>
			<td><?=$this->getText ('networth'); ?></td>
			<td><?=$score?></td>
		</tr>

		<tr>
			<td><?=$race?></td>
			<td><?=$race_value?></td>
		</tr>
	
		<tr>
			<td><?=$this->getText ('honour')?></td>
			<td><?=$honour_value?></td>
		</tr>
	
		<?php if (isset ($list_distances)) { foreach ($list_distances as $v) { ?>
			<tr>
				<td colspan="2">
					<?=Neuron_Core_Tools::putIntoText
					(
						$this->getText ('distance'),
						array
						(
							'name' => '<a href="javascript:void(0);" '.
								'onclick="openWindow(\'villageProfile\',{\'village\':'.
								$v['id'].'});">'.$v['name'].'</a>',
							'distance' => $v['distance']
						)
					);?>
				</td>
			</tr>
		<?php } } ?>

		<?php if (isset ($list_challenges)) { foreach ($list_challenges as $v) { ?>
			<tr>
				<td colspan="2">
					<a href="javascript:void(0);" onclick="openWindow('battle',<?=$v[1]?>);"><?=$v[0]?></a>
				</td>
			</tr>
		<?php } } ?>

	</table>

</div>
