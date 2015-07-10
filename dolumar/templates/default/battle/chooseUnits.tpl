<h2><?=$challenge?></h2>

<?php include ('actionSelect.phpt'); ?>

<?php if (isset ($error)) { ?>
	<p class="false"><?=$error?></p>
<?php } else { ?>

	<?php if (isset ($error)) { echo '<p class="false">'.$error.'</p>'; } ?>

	<?php if ($showForm) { ?>
	<?php if (isset ($list_squads)) { ?>
		<form onsubmit="return submitForm(this);" class="chooseUnits">
			<div class="chooseUnitContainer">
				<fieldset class="chooseUnits">
		
					<input type="hidden" class="hidden" name="target" value="<?=$target_id?>" />
					<input type="hidden" class="hidden" name="selected_troops" value="1" />
		
					<?php 
						$sOptions = '<option value="0">&nbsp;</option>';
						$sAvailable = '<div class="available-units">';
				
						foreach ($list_squads as $v) 
						{
							foreach ($v['oUnits'] as $unit) 
							{
								$fullTitle = $v['sName'] . ' (' . $unit->getAvailableAmount () . ' ' .
									Neuron_Core_Tools::output_varchar ($unit->getName ()) . ')';
					
								$sOptions .= '<option value="'.$v['id'].'_'.$unit->getUnitId ().'">'.$v['sName'].'</option>';
						
								// The available units list
								$sAvailable .= '<div class="'.$v['id'].'_'.$unit->getUnitId ().'"><img src="'.$unit->getImageUrl ().'" title="'.$fullTitle.'" /></div>';
							}
						}
						$sAvailable .= '</div>';
				
			
						// Iterate loops and make select boxes
						echo '<div class="battlefield-decoration-left"> </div>';
					
						$first = true;
						foreach ($list_slots as $v)
						{	
							$background = "";
							if ($first)
							{
								echo '<div class="first battlefield-slot '.$v['sName'].'" '.$background.'>';
								$first = false;
							}
							else
							{
								echo '<div class="battlefield-slot '.$v['sName'].'" '.$background.'>';
							}
					
							echo '<label>'.$this->getText('slot').' '.$v['id'].' ('.$v['sType'].'):</label>'; 
							echo '<select name="slot'.$v['id'].'" class="unique" onchange="removeDuplicates(this.parentNode.parentNode, this);">'.$sOptions.'</select>';
							echo '</div>';
						}
					
						echo '<div class="battlefield-decoration-right"> </div>';
					?>
			
				</fieldset>
			</div>
		
			<button type="submit" name="submit" value="attack"><?=$this->getText('attack')?></button>
			<button type="reset" name="reset" value="clear" class="clear"><?=$this->getText('clear')?></button>
		</form>
	
		<h2><?=$this->getText ('available', 'challenge', 'battle'); ?></h2>
		<?=$sAvailable?>
	
	<?php } else { ?><p class="false"><?=$this->getText ('noTroops');?></p><?php } ?>
	<?php } ?>
<?php } ?>
