<?php $this->setTextSection ('battle', 'underworld'); ?>

<ul class="tabs">
	<li class="active">
		<a href="javascript:void(0);" onclick="windowAction(this,'overview=true');"><?=$this->getText ('reports', 'report', 'battle')?></a>
	</li>
</ul>

<h2><?=$this->getText ('ourbattles'); ?></h2>

<?php if (isset ($list_battles)) { ?>
	<table>
		<?php foreach ($list_battles as $v) { ?>
			<tr class="<?= $v->isVictory ($side) ? 'true' : 'false' ?>">
				<td>
					<?php $date = date ('d/H:i', $v->getStartdate ()); ?>
					<?=$date?>
				</td>

				<td>
					<?php if ($v->isAttack ($side)) { ?>
						<?= $this->putIntoText ($this->getText ('weattack'), array ('attacker' => $v->getAttackerSide ()->getDisplayName (), 'defender' => $v->getDefenderSide ()->getDisplayName ())) ?>
					<?php } else if ($v->isDefense ($side)) { ?>
						<?= $this->putIntoText ($this->getText ('wedefend'), array ('attacker' => $v->getAttackerSide ()->getDisplayName (), 'defender' => $v->getDefenderSide ()->getDisplayName ())) ?>
					<?php } else { ?>
						<?= $this->putIntoText ($this->getText ('theyattack'), array ('attacker' => $v->getAttackerSide ()->getDisplayName (), 'defender' => $v->getDefenderSide ()->getDisplayName ())) ?>
					<?php } ?>
				</td>

				<td>
					<?= $v->getAttackerLocation (); ?> -> <?=$v->getDefenderLocation (); ?>
				</td>

				<td>
					<?= $v->isAttackerWinner () ? 'success' : 'failure' ?>
				</td>

				<td>
					<?= Neuron_URLBuilder::getInstance ()->getUpdateUrl ('Battle', 'Report', array ('report' => $v->getId ())); ?>
				</td>
			</tr>
		<?php } ?>
	</table>
<?php } else { ?>
	<p>We didn't fight yet.</p>
<?php } ?>