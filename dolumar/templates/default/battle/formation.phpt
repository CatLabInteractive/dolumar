<h2><?=$this->getText ('formation')?></h2>

<?php if (isset ($message)) { ?>
	<?php if ($error) { ?>
		<p class="false"><?=$this->getText ($message)?></p>
	<?php } else { ?>
		<p class="true"><?=$this->getText ($message)?></p>
	<?php } ?>
<?php } ?>

<?php if (isset ($list_squads) && count ($list_squads) > 0) { ?>
	<form onsubmit="return submitForm(this);">
		<fieldset>
			<?php foreach ($slots as $slotId => $slot) { ?>
				<div class="challenge-slot">
					<label><?=$this->getText ('slot')?> <?=$slotId?>: <?=$this->getText ($slot->getName (), 'slots', 'battle');?></label>

					<?php for ($i = 0; $i < $rows; $i ++) { ?>
						<select class="unique" onchange="removeDuplicates(this.parentNode.parentNode, this);" name="slot_<?=$slotId?>_<?=$i?>">
							<option value="0">&nbsp;</option>
							<?php foreach ($list_squads as $squad) { ?>
								<?php foreach ($squad['oUnits'] as $v) { ?>
									<option value="<?=$squad['id']?>_<?=$v->getUnitId()?>" <?php if ($v->getDefaultSlot () == $slotId && $v->getSlotPriority () == $i) { ?>selected="selected"<?php } ?>>
										<?=$squad['sName']?> (<?=$v->getName ()?>)
									</option>
								<?php } ?>
							<?php } ?>
						</select>
					<?php } ?>
				</div>
			<?php } ?>

			<button type="submit"><?=$this->getText ('save')?></button>
		</fieldset>
	</form>
<?php } else { ?>
	<p><?=$this->getText ('noSquads')?></p>
<?php } ?>
