<?php if (count ($equipment) > 0) { ?>
	<?php $alternate = true; ?>

	<?php foreach ($equipment as $types) { ?>

		<h3><?=$types['type']?></h3>

		<table class="tlist">

		<tr>
			<th>&nbsp;</th>
			<th style="text-align: center; width: 20%;"><?=$available?></th>
			<th style="text-align: center; width: 20%;"><?=$total?></th>
		</tr>

		<?php foreach ($types['items'] as $v) { ?>
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
				<th><?=$v[0]?></th>
				<td style="text-align: center;"><?=$v[1]?></td>
				<td style="text-align: center;"><?=$v[2]?></td>
			</tr>

			<?php if (!empty ($v[3])) { ?>
				<tr class="<?=$rowclass?>">
					<td colspan="3"><?=$v[3]?></td>
				</tr>
			<?php } ?>
		<?php } ?>

		</table>
	<?php } ?>
<?php } else { ?>

	<p><?=$noItems?></p>

<?php } ?>
