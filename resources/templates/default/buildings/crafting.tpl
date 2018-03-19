<?php $this->setTextSection ('crafting', 'buildings'); ?>

<h3><?=$title?></h3>

<?php if ($section == 'overview') { ?>

	<p><?=$about?></p>
	<?php if (isset ($list_equipment)) { ?>

		<ul class="regular">
			<?php foreach ($list_equipment as $v) { ?>
				<li><a href="javascript:void(0)" onclick="windowAction (this, {'action':'craft','crafting':'<?=$v[1]?>'})"><?=$v[0]?></a></li>
			<?php } ?>
		</ul>

	<?php } ?>
	
	<!-- Upgrades -->
	<?php if ($unused > 0) { ?>
	
		<h3><?=$this->getText ('upgrades'); ?></h3>
		<p><?=$this->putIntoText ($this->getText ('aboutupgrades'), array ('unused' => $unused)); ?></p>
		
		<ul class="regular">
			<?php foreach ($list_upgrade_equipment as $v) { ?>
				<li>
					<a href="javascript:void(0)" onclick="windowAction (this, {'action':'upgrade','crafting':'<?=$v[1]?>'})">
						<?=$this->putIntoText ($this->getText ('selectupgrade'), array ('equipment' => $v[0]))?>
					</a>
				</li>
			<?php } ?>
		</ul>
	
	<?php } ?>

<?php } elseif ($section == 'crafting') { ?>

	<p><?=$about?></p>

	<?php if (isset ($error)) { ?>
		<p class="false"><?=$error?></p>
	<?php } ?>

	<div class="weapon-stats">
		<?=$stats?>
	</div>

	<p class="maybe">
		<?=$cost?><br />
		<?=$duration?>
	</p>
	
	<?php if (isset ($maxcraftable)) { ?>
		<p class="information">
			<span class="info-icon"><?=$maxcraftable?></span>
		</p>
	<?php } ?>

	<form onsubmit="return submitForm(this, {'action':'craft'});">
		<fieldset>
			<legend><?=$title?></legend>
			
			<input type="hidden" name="action" value="craft" style="display: none;" />
			<input type="hidden" name="crafting" value="<?=$itemId?>" style="display: none;" />
		
			<ol>
				<li>
					<label><?=$amount?>:</label>
					<input type="text" name="amount" style="width: 50px;" />
				</li>
				
				<li>
					<div class="buttons">
						<button type="submit"><span><?=$submit?></span></button>
					</div>
				</li>
			</ol>
		</fieldset>

	</form>

	<p><?=$return[0]?><a href="javascript:void(0);" onclick="windowAction (this, {'action':'overview'});"><?=$return[1]?></a><?=$return[2]?></p>

<?php } ?>
