<p class="maybe center" style="text-align: center;">
	<?=Neuron_Core_Tools::putIntoText
	(
		$this->getText ('upgrade1', 'producing', 'buildings'),
		array
		(
			'next' => $income_next_level,
			'now' => $income_this_level
		)
	)?><br />
	<?=Neuron_Core_Tools::putIntoText
	(
		$this->getText ('upgrade2', 'producing', 'buildings'),
		array
		(
			'next' => $income_next_level,
			'now' => $income_this_level
		)
	)?>
</p>
