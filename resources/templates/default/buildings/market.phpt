<?php $this->setTextSection ('market', 'buildings'); ?>

<!-- TODO: replace this with javascript tabs ey -->
<ul class="tabs">
	<li class="<?=$tab == 'resources' ? 'active' : null?>">
		<?=$tab_resources?>
	</li>
	
	<li class="<?=$tab == 'runes' ? 'active' : null?>">
		<?=$tab_runes?>
	</li>
	
	<li class="<?=$tab == 'equipment' ? 'active' : null?>">
		<?=$tab_equipment?>
	</li>
</ul>

<?php if ($canTrade) { ?>
	<?php if ($tab == 'resources') { ?>	
		<form method="post" onsubmit="return submitForm(this);">

			<input type="hidden" class="hidden" name="target" value="<?=$target?>" />
			<input type="hidden" class="hidden" name="action" value="donate" />
		
			<input type="hidden" class="hidden" name="tab" value="<?=$tab?>" />

			<fieldset class="centered nolegend">
				<legend><?=$this->getText ('resources')?></legend>
				<ol>
					<?php foreach ($list_resources as $v) { ?>
						<li>
							<label><?=ucfirst ($this->getText ($v['name'], 'resources', 'main'))?>:</label>
							<input type="text" value="<?=$v['amount']?>" name="res_<?=$v['name']?>" class="number small" />
							<span class="number available"><?=$v['available']?></span>
						</li>
					<?php } ?>
		
					<li>
						<div class="buttons">
							<button type="submit"><span><?=$this->getText ('donate')?></span></button>
						</div>
					</li>
				</ol>
			</fieldset>
		</form>
	<?php } else if ($tab == 'runes') { ?>

		<form method="post" onsubmit="return submitForm(this);">

			<input type="hidden" class="hidden" name="target" value="<?=$target?>" />
			<input type="hidden" class="hidden" name="action" value="donate" />
		
			<input type="hidden" class="hidden" name="tab" value="<?=$tab?>" />

			<fieldset class="centered nolegend">
				<legend><?=$this->getText ('runes')?></legend>
				<ol>
					<?php foreach ($list_runes as $v) { ?>
						<li>
							<label><?=ucfirst ($this->getText ($v['name'], $v['amount'] > 1 ? 'runeDouble' : 'runeSingle', 'main'))?>:</label>
							<input type="text" value="<?=$v['amount']?>" name="run_<?=$v['name']?>" class="number small" />
							<span class="number available"><?=$v['available']?></span>
						</li>
					<?php } ?>
		
					<li>
						<div class="buttons">
							<button type="submit"><span><?=$this->getText ('sendrunes')?></span></button>
						</div>
					</li>
				</ol>
			</fieldset>
		</form>
	
	<?php } else if ($tab == 'equipment') { ?>

		<form method="post" onsubmit="return submitForm(this);">

			<input type="hidden" class="hidden" name="target" value="<?=$target?>" />
			<input type="hidden" class="hidden" name="action" value="donate" />
		
			<input type="hidden" class="hidden" name="tab" value="<?=$tab?>" />

			<?php foreach ($equipment as $type => $vv) { ?>
				<?php if (count ($vv['equipment']) > 0) { ?>
					<fieldset class="centered long-labels">
						<legend><?=$vv['name']?></legend>
						<ol>
			
							<?php foreach ($vv['equipment'] as $v) { ?>
								<li>
									<label for="<?=$v['formid']?>"><?=$v['name']?></label>
									<input type="text" name="equipment_<?=$v['id']?>" class="number small" value="<?=$v['amount']?>" />
									<span class="number available"><?=$v['available']?></span>
								</li>
							<?php } ?>
						</ol>
					</fieldset>
				<?php } ?>
			<?php } ?>
	
			<fieldset class="centered">
				<ol>
					<li>
						<div class="buttons">
							<button type="submit"><span><?=$this->getText ('sendequipment')?></span></button>
						</div>
					</li>
				</ol>
			</fieldset>
		</form>
	<?php } ?>
<?php } else { ?>
	<p class="false"><?=$tradeerror?></p>
<?php } ?>

<p><a href="javascript:void(0);" onclick="windowAction(this,{'page':'home'});"><?=$this->getText ('overview')?></a></p>
