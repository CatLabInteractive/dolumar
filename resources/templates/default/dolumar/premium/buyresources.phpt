<?php $this->setTextSection ('buyresources', 'premium'); ?>

<div class="fancybox">

	<p><?=$this->getText ('description'); ?></p>	

	<table class="resources">

		<tr>

			<th>&nbsp;</th>
			<th class="number" colspan="2"><?=$this->getText ('stock', 'economics', 'village'); ?></th>
			<th>&nbsp;</th>

		</tr>
	
		<?php foreach ($list_resources as $v) { ?>
		<tr>

		
			<td><span class="resource <?=$v['resource']?>" title="<?=$v[0]?>">&nbsp;<span>&nbsp;</span></span></td>
		
			<td class="right"><span class="increasing amount" title="<?=$v[3]?>/<?=$v[2]?>"><?=$v[1]?></span> /</td>
			<td class="left"><?=$v[2]?></td>
		
			<td>

				<?php if (!$v['full']) { ?>
					<a href="<?=$v['fillup']?>" target="_BLANK" onclick="return !Game.gui.openWindow(this, 450, 190);">
						<?=
							$this->putIntoText
							(
								$this->getText ('fillup'),
								array
								(
									'credits' => '<span class="credits">' . $this->putIntoText ($this->getText ('credits'), array ('credits' => $v['cost'])) . '</span>'
								)
							); ?>
					</a>
				<?php } else { ?>

					<?=$this->getText ('filledup'); ?>

				<?php } ?>
			</td>
		</tr>
		<?php } ?>

	</table>

	<p>
		<a href="<?= $fillup_all ?>" target="_BLANK" onclick="return !Game.gui.openWindow(this, 450, 190);">
			<?=$this->putIntoText
			(
				$this->getText ('fillupall'),
				array
				(
					'credits' => '<span class="credits">' . $this->putIntoText ($this->getText ('credits'), array ('credits' => $fillup_all_cost)) . '</span>'
				)
			); ?>
		</a>
	</p>
</div>

<p><a href="javascript:void(0);" onclick="windowAction(this,{'action':'overview'});"><?=$this->getText ('overview', 'shop'); ?></a></p>
