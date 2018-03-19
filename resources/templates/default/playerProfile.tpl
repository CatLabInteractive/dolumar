<!--<h2><?=$playerProfile?></h2>-->

<div class="fancybox">
	<table class="tlist" style="margin-top: 5px;">

		<tr>
			<th colspan="2"><?=$player?></th>
		</tr>

		<tr>
			<td colspan="2">
				<a href="javascript:void(0);" onclick="openWindow ('PrivateChat', {'id' : <?=$id?>});">Open private chat</a>
			</td>
		</tr>

		<?php if (isset ($creation_value)) { ?>
			<tr>
				<td style="width: 33%;"><?=$creation?>:</td>
				<td><?=$creation_value?></td>
			</tr>
		<?php } ?>

		<?php if (isset ($lastRef_value)) { ?>
			<tr>
				<td><?=$lastRef?>:</td>
				<td><?=$lastRef_value?></td>
			</tr>
		<?php } ?>

		<?php if (isset ($removal_value)) { ?>
			<tr>
				<td><?=$removal?>:</td>
				<td><?=$removal_value?></td>
			</tr>
		<?php } ?>
	
		<?php if (isset ($status_value)) { ?>
			<tr>
				<td><?=$status?></td>
				<td><?=$status_value?></td>
			</tr>
		<?php } ?>
	
		<?php if (isset ($list_clans)) { ?>
		<tr>
			<td rowspan="<?php echo count ($list_clans); ?>" style="vertical-align: top;"><?=$clans?>:</td>

			<?php $first = true; foreach ($list_clans as $v) { ?>
				<?php if (!$first) { echo '<tr>'; } $first = false; ?>
					<td><a href="javascript:void(0);" onclick="openWindow('clan', {'id':<?=$v['id']?>});"><?=$v['name']?></a></td>
				</tr>
			<?php } ?>
		<?php } ?>
	
		<?php if (isset ($admin_url)) { ?>
			<tr>
				<td>&nbsp;</td>
		
				<td>
					<a href="<?=$admin_url?>" target="_BLANK">Moderate user</a>
				</td>
			</tr>
		<?php } ?>
	</table>
</div>
