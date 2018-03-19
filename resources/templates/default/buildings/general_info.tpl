<?php if (isset ($inactive)) { ?>
	<p class="false"><?=$this->getText ('inactive', 'building', 'building'); ?></p>
<?php } ?>

<?php
if ($desc)
{
	echo '<p class="maybe">'.$desc.'</p>';
}
?>

<div class="fancybox">
	<table class="tlist">
		<?php if (isset ($level_value)) { ?>
	
			<tr>
				<td><?=$level?>:</td>
				<td><?=$level_value?></td>
			</tr>
	
		<?php } ?>

        <?php if ($owner_id) { ?>
            <tr>
                <td><?=$owner?>:</td>
                <td>
                    <?php if (isset ($owner_id)) { ?>
                        <a href="javascript:void(0);" onclick="openWindow('playerProfile', {'plid':<?=$owner_id?>});">
                            <?=$owner_value?>
                        </a>
                    <?php } else { ?>
                        <?=$owner_value?>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
		<tr>
			<td style="width: 30%"><?=$village?>:</td>
			<td>
				<?php if (isset ($village_id)) { ?>
					<a href="javascript:void(0);" onclick="openWindow('villageProfile', {'village':<?=$village_id?>});">
						<?=$village_value?>
					</a>
				<?php } else { ?>
					<?=$village_value?>
				<?php } ?>
			</td>
		</tr>
		<tr>
			<td><?=$location?>:</td>
			<td>
				<?=$location_value?>
			</td>
		</tr>
		<?php if (isset ($buildDate_value)) { ?>
			<tr>
				<td><?=$buildDate?>:</td>
				<td><?=$buildDate_value?></td>
			</tr>
		<?php } ?>

		<?php if (isset ($race_value)) { ?>
			<tr>
				<td><?=$race?>:</td>
				<td><?=$race_value?></td>
			</tr>
		<?php } ?>

		<?php if (isset ($usedRune_value)) { ?>
			<tr>
				<td><?=$usedRune?>:</td>
				<td><?=$usedRune_value?></td>
			</tr>
		<?php } ?>
	
	</table>
</div>

<?php
if (isset ($back))
{
	echo '<p>'.$back[0].'<a href="javascript:void(0)" onclick="windowAction (this, {\'page\':\'home\'});">'.$back[1].'</a>'.$back[2].'</p>';
}
?>
