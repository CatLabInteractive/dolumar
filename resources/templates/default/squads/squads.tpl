<?php $this->setTextSection ('overview', 'squads'); ?>

<?php if (isset ($notice)) { ?>
	<p class="true ui-msg"><?=$notice?></p>
<?php } ?>

<div class="ui-btn-row left">
	<a href="javascript:void(0);" class="ui-btn primary" onclick="windowAction (this, {'page':'add'});"><?=$this->getText ('addsquad'); ?></a>
</div>

<?php if (isset ($list_squads)) { ?>
	<div class="squad-list">
		<?php foreach ($list_squads as $v) { ?>
			<?php
				$details = array ();
				foreach ($list_itemTypes as $type)
				{
					if (isset ($v['equipment'][$type['type']]))
					{
						$details[] = $v['equipment'][$type['type']];
					}
				}
			?>

			<?php if ($v['isMine']) { ?>
				<a href="javascript:void(0);" class="ui-card" onclick="windowAction (this, {'page':'squad', 'id':<?=$v['id']?>});">
			<?php } else { ?>
				<div class="ui-card">
			<?php } ?>

				<?php if (isset ($v['image'])) { ?>
					<img src="<?=$v['image']?>" alt="" />
				<?php } ?>

				<span class="squad-card-body">
					<span class="ui-card-title"><?=$v['name']?></span><br />
					<?php if ($v['amount'] > 0) { ?>
						<?=$v['amount']?> <?=$v['unit']?>
					<?php } else { ?>
						<?=$this->getText ('noUnits')?>
					<?php } ?>
					<br />
					<span class="ui-muted">
						<?=count ($details) > 0 ? implode (', ', $details) : $this->getText ('noEquipment')?>
					</span>
					<?php if (isset ($v['statusText'])) { ?>
						<br />
						<span class="ui-muted"><?=$v['statusText']?></span>
						<?php if ($v['canRecall']) { ?>
							(<a href="javascript:void(0);" onclick="confirmAction (this, {'recall':<?=$v['id']?>}, '<?=str_replace ("'", "\'", $this->getText ($v['status'].'_return_con'))?>');"><?=$this->getText ($v['status'].'_return')?></a>)
						<?php } ?>
					<?php } ?>
				</span>

			<?php if ($v['isMine']) { ?>
					<span class="ui-card-go">›</span>
				</a>
			<?php } else { ?>
				</div>
			<?php } ?>
		<?php } ?>
	</div>
<?php } else { ?>
	<p><?=$this->getText ('noSquads')?></p>
<?php } ?>

<?php if (isset ($list_free)) { ?>
	<p class="ui-muted">
		<?=$this->getText ('free')?>:
		<?php $first = true; foreach ($list_free as $v) { ?><?=$first ? '' : ', '?><?=$v['amount']?> <?=$v['name']?><?php $first = false; } ?>
	</p>
<?php } ?>

<ul class="actions">
	<li>
		<a href="javascript:void(0);" onclick="openWindow ('units', {'vid':'<?=$vid?>'});"><?=$this->getText ('showunits'); ?></a>
	</li>
</ul>
