<?php $this->setTextSection ('challenge', 'battle'); ?>

<h2>Battle Simulator</h2>

<form method="post" onsubmit="return submitForm(this);">
	<fieldset class="centered nolegend">
		<ol>
			<li>
				<label for="slots">Battle slots:</label>
				<select id="slots" name="slots" class="slots">
					<?php for ($i = 1; $i <= 7; $i ++) { ?>
						<option <?php if (($i*2+1) == $slots) { ?>selected="selected"<?php } ?>><?=($i*2+1)?></option>
					<?php } ?>
				</select>
			</li>
			
			<li style="display: none;">
				<label for="unit_amount">Amount of units:</label>
				<input type="text int" id="unit_amount" name="unit_amount" value="20" class="unit_amount" />
			</li>
			
			<li>
				<div class="buttons">
					<button type="submit"><span>Change slots</span></button>
				</div>
			</li>
		</ol>
	</fieldset>
</form>

<!-- Battlefield -->
<form onsubmit="return submitForm(this);" class="chooseUnits">

	<input type="hidden" class="hidden" name="action" value="simulate" />

	<?php foreach (array ('def', 'att') as $side) { ?>
		<div class="chooseUnitContainer">
			<fieldset class="chooseUnits">
			
				<legend><?=$side?></legend>

				<?php 
		
					$options = "";
					foreach ($units as $race) 
					{
						$options .= '<option value="0" selected="selected">&nbsp;</option>';
						$options .= '<optgroup label="' . $race['name'].'">';
						foreach ($race['units'] as $unit) 
						{
							$options .= '<option value="'.$race['id'].'_'.$unit['id'].'">' . $unit['name'] . '</option>';
						}
						$options .= '</optgroup>';
					}
		
					$type = 'grass';
			
					/*
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
					*/
	

					// Iterate loops and make select boxes
					echo '<div class="battlefield-decoration-left"> </div>';
		
					$first = true; for ($slot = 1; $slot <= $slots; $slot ++)
					{
						$type = 'grass';
			
						$background = "";
						if ($first)
						{
							echo '<div class="first battlefield-slot '.$type.'" '.$background.'>';
							$first = false;
						}
						else
						{
							echo '<div class="battlefield-slot '.$type.'" '.$background.'>';
						}
		
						echo '<label>'.$this->getText('slot').' '.$slot.' ('.$type.'):</label>';

						echo '<input name="slot_'.$side.'_slot_'.$slot.'" id="slot_'.$side.'_slot_'.$slot.'" value="1" class="hidden" />'; 
						echo '<select name="slot_'.$side.'_unit_'.$slot.'">'.$options.'</select>';
						echo '<input name="slot_'.$side.'_amount_'.$slot.'" id="slot_'.$side.'_amount_'.$slot.'" value="0" class="number selected_units" />';
				
						echo '</div>';
					}
		
					echo '<div class="battlefield-decoration-right"> </div>';
				?>
			</fieldset>
		</div>
	<?php } ?>
	
	<button type="submit" name="submit" value="simulate"><?=$this->getText('simulate')?></button>
	<button type="reset" name="reset" value="clear" class="clear"><?=$this->getText('clear')?></button>
</form>

<h2>Units</h2>
<div class="available-units">
	<?php foreach ($units as $race) { ?>
		<h3 style="clear: left;"><?=$race['name']?></h3>
	
		<?php foreach ($race['units'] as $unit) { ?>
			<div class="<?=$race['id'].'_'.$unit['id']?>">
				<img src="<?=$unit['img']?>" />
			</div>
		<?php } ?>
	<?php } ?>
</div>
