<?php $dialogId = $this->getUniqueId (); ?>
<h2><?=$this->getText ('squads')?></h2>

<form method="post" onsubmit="return submitForm(this);">

	<input type="hidden" name="action" value="split" />

	<?php if (isset ($list_squads)) { ?>
		<?php foreach ($list_squads as $v) { ?>

			<input type="checkbox" name="squad_<?=$v['id']?>" id="squad_<?=$v['id']?>_<?=$dialogId?>" />

			<h3><label for="squad_<?=$v['id']?>_<?=$dialogId?>"><?=$v['name']?></label></h3>

			<label for="squad_<?=$v['id']?>_<?=$dialogId?>">

				<div class="fancybox">
				
					<table>
						<?php foreach ($v['units'] as $unit) { ?>
							<tr>
								<td rowspan="3" style="width: 54px;">
									<img src="<?=$unit['image']?>" />
								</td>
							
								<td>
									<?=$v['owner']?>
								</td>
							</tr>
							
							<tr>
								<td>
									<?=$unit['numberedname']?>
								</td>
							</tr>
							
							<tr>
								<td>
									<?=$this->getText ('morale', 'overview', 'squads')?>: <?=$unit['morale']?>
								</td>
							</tr>
						
						<?php } ?>
					</table>
				</div>
			</label>
		<?php } ?>

		<button type="submit">
			<span>Split up army</span>
		</button>

	<?php } else { ?>
		<p>No regiments.</p>
	<?php } ?>
</form>