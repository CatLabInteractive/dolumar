<p><?=$select?></p>

<?php if (isset ($error)) { echo '<p class="false">'.$error.'</p>'; } ?>

<form onsubmit="return submitForm (this);" id="selectrace">
	<fieldset>
		<legend><?=$this->getText('selectRace');?></legend>
		
		<ol>
			<?php
			$i = 0;
			foreach ($list_races as $v)
			{
			?>
			<li>
				<input 
					<?php if ($i == 0) echo 'checked="checked"'; ?> 
					type="radio" id="race<?= $v[2]?>" name="race" title="<?= $v[0]?>" value="<?=$v[2]?>" class="checkbox" />
		
				<label for="race<?= $v[2]?>" class="checkbox"><strong><?= $v[0]?></strong>
					<?php if ($v[1] != 'null') { echo '<br />' . $v[1]; } ?>
				</label>
			</li>
			<?php
				$i ++;
			}
			?>
		</ol>
	</fieldset>

	<fieldset class="lines">
		<legend><?=$this->getText('selectLocation');?></legend>
	
		<ol>
			<li>
				<label><?=$this->getText ('joinClan')?>:</label>
				<select name="clan">
					<option value="0">&nbsp;</option>
					<?php
						if (isset ($list_clans))
						{
							foreach ($list_clans as $v)
							{
								echo '<option value="'.$v['id'].'">'.$v['name'].' '.($v['isFull'] ? '('.$this->getText ('clanFull').')' : ($v['isLocked'] ? '('.$this->getText ('clanLocked').')' : null )).'</option>';
							}
						}
					?>
				</select>
			</li>

			<li>
				<label><?=$location?>:</label>
				<select name="location">
					<?php
						foreach ($list_directions as $v)
						{
							echo '<option value="'.$v[1].'">'.$v[0].'</option>';
						}
					?>
				</select>
			</li>

			<li>
				<button type="submit" value="<?=$submit?>"><?=$submit?></button>
			</li>
		</ol>
	</fieldset>
</form>

<?php if (isset ($tracker_url)) { ?>
	<iframe src="<?=$tracker_url?>" width="0" height="0" border="0" class="hidden-iframe"></iframe>
<?php } ?>
