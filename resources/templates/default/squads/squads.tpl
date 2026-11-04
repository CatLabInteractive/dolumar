<?php $this->setTextSection ('overview', 'squads'); ?>

<?php if (isset ($notice)) { ?>
	<p class="true"><?=$notice?></p>
<?php } ?>

<p class="squad-new">
	<a href="javascript:void(0);" class="action-button primary" onclick="windowAction (this, {'page':'add'});">+ <?=$this->getText ('addsquad'); ?></a>
</p>

<?php if (isset ($list_squads)) { ?>
	<div class="squad-list">
		<?php foreach ($list_squads as $v) { ?>
			<div class="squad-card squad-<?=$v['status']?>">
				<?php if ($v['isMine']) { ?>
					<a href="javascript:void(0);" class="squad-card-main" onclick="windowAction (this, {'page':'squad', 'id':<?=$v['id']?>});">
				<?php } else { ?>
					<div class="squad-card-main">
				<?php } ?>

					<?php if (isset ($v['image'])) { ?>
						<img class="squad-portrait" src="<?=$v['image']?>" alt="" />
					<?php } else { ?>
						<span class="squad-portrait squad-portrait-empty"></span>
					<?php } ?>

					<span class="squad-card-body">
						<span class="squad-name"><?=$v['name']?></span>
						<span class="squad-meta">
							<?php if ($v['amount'] > 0) { ?>
								<?=$v['amount']?> <?=$v['unit']?>
							<?php } else { ?>
								<?=$this->getText ('noUnits')?>
							<?php } ?>
						</span>
						<span class="squad-chips">
							<?php foreach ($list_itemTypes as $type) { ?>
								<?php if (isset ($v['equipment'][$type['type']])) { ?>
									<span class="squad-chip squad-chip-<?=$type['type']?>"><?=$v['equipment'][$type['type']]?></span>
								<?php } else { ?>
									<span class="squad-chip squad-chip-<?=$type['type']?> squad-chip-empty"><?=$this->getText ('noItem')?></span>
								<?php } ?>
							<?php } ?>
						</span>
					</span>

				<?php if ($v['isMine']) { ?>
					<span class="squad-chevron">›</span>
					</a>
				<?php } else { ?>
					</div>
				<?php } ?>

				<?php if (isset ($v['statusText'])) { ?>
					<p class="squad-status">
						<?=$v['statusText']?>
						<?php if ($v['canRecall']) { ?>
							· <a href="javascript:void(0);" onclick="confirmAction (this, {'recall':<?=$v['id']?>}, '<?=str_replace ("'", "\'", $this->getText ($v['status'].'_return_con'))?>');"><?=$this->getText ($v['status'].'_return')?></a>
						<?php } ?>
					</p>
				<?php } ?>
			</div>
		<?php } ?>
	</div>
<?php } else { ?>
	<p><?=$this->getText ('noSquads')?></p>
<?php } ?>

<?php if (isset ($list_free)) { ?>
	<p class="squad-free">
		<?=$this->getText ('free')?>:
		<?php $first = true; foreach ($list_free as $v) { ?><?=$first ? '' : ', '?><?=$v['amount']?> <?=$v['name']?><?php $first = false; } ?>
	</p>
<?php } ?>

<ul class="actions">
	<li>
		<a href="javascript:void(0);" onclick="openWindow ('units', {'vid':'<?=$vid?>'});"><?=$this->getText ('showunits'); ?></a>
	</li>
</ul>
