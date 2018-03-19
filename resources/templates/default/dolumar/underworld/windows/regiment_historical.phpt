<?php $this->setTextSection ('squad', 'underworld'); ?>

<h2><?=$this->getText ('history'); ?></h2>
<p>
	<?=$this->putIntoText ($this->getText ('lastEncounter'), array ($date)); ?>
</p>

<h2>Troops</h2>

<?php if (isset ($troops)) { ?>
	<ul>
		<?php foreach ($troops as $v) {  ?>

			<?php $unit = $v['unit']; ?>

			<li>
				<?=$v['amount']?> <?=$v['unit']->getName ($v['amount'] > 1); ?>
			</li>

		<?php } ?>
	</ul>
<?php } else {  ?>
	<p>No information found.</p>
<?php } ?>