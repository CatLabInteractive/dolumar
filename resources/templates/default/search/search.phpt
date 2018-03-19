<?php $this->setTextSection ('form', 'search'); ?>

<?php $nopremium =  !$premium ?  '<span class="premium-only"><span>'.$this->getText ('premiumonly').'</span></span>' : null; ?>

<?php if (!$premium) { ?>
	<p class="information">
		<span class="info-icon"></span>
		<?=Neuron_Core_Tools::putIntoText
		(
			$this->getText ('nopremium'),
			array
			(
				'here' => '<a href="javascript:void(0);" onclick="openWindow(\'Premium\');">'.$this->getText ('here').'</a>'
			)
		);?>
	</p>
<?php } ?>

<div class="searchbox giantcrown">
	<form method="post" class="searchbox" onsubmit="return submitForm(this);">
	
		<input type="hidden" class="hidden" name="search" value="result" />
	
		<fieldset>
			<legend><?=$this->getText ('searchbox'); ?></legend>
	
			<ol>
				<li class="name">
					<label for="search_name"><?=$this->getText ('name'); ?></label>
					<input type="text" id="search_name" name="search_name" />
				</li>
				
				<li class="village">
					<label for="search_village"><?=$this->getText ('village'); ?></label>
					<input type="text" id="search_village" name="search_village" />
				</li>
			
				<li class="race">
					<label for="search_race"><?=$this->getText ('race'); ?> <?=$nopremium?></label>
				
					<select id="search_race" name="search_race" <?php if (!$premium) { ?>disabled="disabled"<?php } ?>>
						<option value="0">&nbsp;</option>
						<option value="1">Humans</option>
						<option value="2">Dark elves</option>
					</select>
				</li>
				
				<li class="online">
					<label for="search_online"><?=$this->getText ('online'); ?> <?=$nopremium?></label>
				
					<select id="search_online" name="search_online" <?php if (!$premium) { ?>disabled="disabled"<?php } ?>>
						<option value="0">&nbsp;</option>
						<option value="60">Online now</option>
					</select>
				</li>
			</ol>
		</fieldset>
	
		<fieldset>
			<legend><?=$this->getText ('distance')?> <?=$nopremium?></legend>
			<ol>
			
				<li class="distance minimum-distance">
					<label for="search_distance_min"><?=$this->getText ('mindistance'); ?></label>
					<input type="text" name="search_distance_min" id="search_distance_min" <?php if (!$premium) { ?>disabled="disabled"<?php } ?> />
				</li>
			
				<li class="distance maximum-distance">
					<label for="search_distance_max"><?=$this->getText ('maxdistance'); ?></label>
					<input type="text" name="search_distance_max" id="search_distance_max" <?php if (!$premium) { ?>disabled="disabled"<?php } ?> />
				</li>
			
				<li class="ankerpoint">
					<label for="search_ankerpoint"><?=$this->getText ('ankerpoint'); ?></label>
				
					<select for="search_ankerpoint" name="search_ankerpoint" <?php if (!$premium) { ?>disabled="disabled"<?php } ?>>
						<?php if (isset ($list_villages)) { ?>
							<?php foreach ($list_villages as $v) { ?>
								<option value="<?=$v['location']?>"><?=$v['name']?></option>
							<?php } ?>
						<?php } else { ?>
							<option value="0">&nbsp;</option>
						<?php } ?>
					</select>
				</li>
			</ol>
		</fieldset>
		
		<fieldset>
			<legend><?=$this->getText ('networth')?> <?=$nopremium?></legend>
			<ol>
			
				<li class="networth minimum-networth">
					<label for="search_networth_min"><?=$this->getText ('minnetworth'); ?></label>
					<input type="text" name="search_networth_min" id="search_networth_min" <?php if (!$premium) { ?>disabled="disabled"<?php } ?> />
				</li>
			
				<li class="networth maximum-networth">
					<label for="search_distance"><?=$this->getText ('maxnetworth'); ?></label>
					<input type="text" name="search_networth_max" id="search_networth_max" <?php if (!$premium) { ?>disabled="disabled"<?php } ?> />
				</li>
			</ol>
		</fieldset>
		
		<fieldset>
			<legend><?=$this->getText ('ordering');?> <?=$nopremium?></legend>
		
			<ol>
				<li>
					<label for="search_order"><?=$this->getText ('order')?></label>
					<select id="search_order" name="search_order" <?php if (!$premium) { ?>disabled="disabled"<?php } ?>>
						<option value="nickname"><?=$this->getText ('nickname', 'order'); ?></option>
						<option value="villagename"><?=$this->getText ('villagename', 'order'); ?></option>
						<option value="lastonline"><?=$this->getText ('lastonline', 'order'); ?></option>
						<option value="distance"><?=$this->getText ('distance', 'order'); ?></option>
					</select>
				</li>
				
				<li>
					<label for="search_order_dir"><?=$this->getText ('order_dir')?></label>
					<select id="search_order_dir" name="search_order_dir" <?php if (!$premium) { ?>disabled="disabled"<?php } ?>>
						<option value="asc"><?=$this->getText ('ascending'); ?></option>
						<option value="desc"><?=$this->getText ('descending'); ?></option>
					</select>
				</li>
			</ol>
		</fieldset>
		
		<fieldset>
			<ol>
				<li>
					<button type="submit" name="search_submit" value="search">
						<span><?=$this->getText ('search'); ?></span>
					</button>
				</li>
			</ol>
		</fieldset>
	</form>
</div>
