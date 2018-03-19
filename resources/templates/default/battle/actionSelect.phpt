<form>
	<fieldset>
		<label><?=$this->getText ('select', 'challenge')?></label>

		<select onchange="submitForm(this.form);" name="command">
			<option value="attack" <?=$action == 'attack' ? 'selected="selected"' : null?>><?=Neuron_Core_Tools::putIntoText ($this->getText ('s_attack', 'challenge'), array ('target' => $target))?></option>
			<option value="support" <?=$action == 'support' ? 'selected="selected"' : null?>><?=Neuron_Core_Tools::putIntoText ($this->getText ('s_support', 'challenge'), array ('target' => $target))?></option>
		</select>
	</fieldset>
</form>

<p>
	<?=$distance?>
</p>
