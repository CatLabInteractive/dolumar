<h2><?=$this->getText ('formation')?></h2>

<?php if (isset ($message)) { ?>
	<?php if ($error) { ?>
		<p class="false ui-msg"><?=$this->getText ($message)?></p>
	<?php } else { ?>
		<p class="true ui-msg"><?=$this->getText ($message)?></p>
	<?php } ?>
<?php } ?>

<?php if (isset ($list_units) && count ($list_units) > 0) { ?>
	<p class="formation-help"><?=$this->getText ('help')?></p>

	<form onsubmit="return submitForm(this);" class="formation-form">
		<div class="formation-scroll">
			<div class="formation-field">
				<div class="battlefield-decoration-left">&nbsp;</div>

				<?php foreach ($list_slots as $slot) { ?>
					<div class="battlefield-slot formation-slot <?=$slot['sName']?> rows-<?=$rows?>">
						<span class="formation-terrain"><?=$slot['id']?>. <?=$slot['sType']?></span>

						<?php foreach ($slot['cells'] as $i => $unit) { ?>
							<div class="formation-cell <?=$i == 0 ? 'front' : 'reserve'?>">
								<input type="hidden" name="slot_<?=$slot['id']?>_<?=$i?>" value="<?=$unit?>" />
								<?php if ($i > 0) { ?>
									<span class="formation-cell-label"><?=$this->getText ('reserve')?></span>
								<?php } ?>
							</div>
						<?php } ?>
					</div>
				<?php } ?>

				<div class="battlefield-decoration-right">&nbsp;</div>
			</div>
		</div>

		<?php if ($rows > 1) { ?>
			<p class="ui-muted formation-note"><?=$this->getText ('reserveHelp')?></p>
		<?php } ?>

		<h3><?=$this->getText ('regiments')?></h3>
		<div class="formation-pool">
			<?php foreach ($list_units as $v) { ?>
				<div class="formation-unit" data-unit="<?=$v['key']?>">
					<img src="<?=$v['image']?>" alt="" title="<?=$v['sName']?> (<?=$v['amount']?> <?=$v['sUnit']?>)" />
					<span class="formation-unit-name"><?=$v['sName']?></span>
					<span class="formation-unit-amount ui-muted"><?=$v['amount']?> <?=$v['sUnit']?></span>
				</div>
			<?php } ?>
		</div>

		<p class="ui-muted formation-note"><?=$this->getText ('unplaced')?></p>

		<div class="ui-btn-row left">
			<button type="submit" class="ui-btn primary"><?=$this->getText ('save')?></button>
		</div>

		<span class="formation-clear-text" style="display: none;"><?=$this->getText ('clear')?></span>
	</form>
<?php } else { ?>
	<p><?=$this->getText ('noSquads')?></p>
<?php } ?>
