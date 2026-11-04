<?php $this->setTextSection ('editor', 'squads'); ?>

<p class="squad-back">
	<a href="javascript:void(0);" onclick="windowAction (this, {'page':'overview'});">‹ <?=$this->getText ('back')?></a>
</p>

<?php if (isset ($list_errors)) { foreach ($list_errors as $v) { ?>
	<p class="false ui-msg"><?=$v?></p>
<?php } } ?>

<?php if (isset ($list_notices)) { foreach ($list_notices as $v) { ?>
	<p class="true ui-msg"><?=$v?></p>
<?php } } ?>

<?php if (isset ($noUnits)) { ?>
	<p class="false ui-msg"><?=$noUnits?></p>
<?php } else { ?>

<?php $disabled = $canEdit ? '' : ' disabled="disabled"'; ?>

<form class="squad-editor" onsubmit="return submitForm(this);">
	<?php if ($isNew) { ?>
		<input type="hidden" class="hidden" name="page" value="add" />
		<input type="hidden" class="hidden" name="action" value="create" />

		<h2><?=$this->getText ('newTitle')?></h2>

		<p class="squad-label"><strong><?=$this->getText ('unit')?></strong></p>
		<div class="squad-unit-tiles">
			<?php foreach ($list_unitTypes as $v) { ?>
				<label class="ui-card squad-unit-tile<?=$v['selected'] ? ' selected' : ''?>">
					<input type="radio" name="squadUnit" value="<?=$v['id']?>"
						data-free="<?=$v['free']?>" data-suggestion="<?=$v['suggestion']?>"
						onclick="Game.dolumar.squads.pickUnit (this);"<?=$v['selected'] ? ' checked="checked"' : ''?> />
					<img src="<?=$v['image']?>" alt="" />
					<span>
						<strong><?=$v['name']?></strong><br />
						<span class="ui-muted"><?=Neuron_Core_Tools::putIntoText ($this->getText ('free'), array ($v['free']))?></span>
					</span>
				</label>
			<?php } ?>
		</div>
	<?php } else { ?>
		<input type="hidden" class="hidden" name="page" value="squad" />
		<input type="hidden" class="hidden" name="action" value="save" />
		<input type="hidden" class="hidden" name="id" value="<?=$squadId?>" />

		<div class="squad-head">
			<?php if (isset ($image)) { ?><img src="<?=$image?>" alt="" /><?php } ?>
			<span>
				<strong><?=$name?></strong><br />
				<span class="ui-muted"><?=$unitName?>, <?=$statusText?></span>
			</span>
		</div>
	<?php } ?>

	<div class="ui-field">
		<label><?=$this->getText ('name')?></label>
		<input type="text" class="squad-name-input" name="squadName" value="<?=$name?>" maxlength="20"
			data-suggested="<?=$isNew ? '1' : '0'?>" oninput="this.setAttribute ('data-suggested', '0');"<?=$disabled?> />
		<?php if ($isNew) { ?>
			<span class="ui-muted"><?=$this->getText ('nameHint')?></span>
		<?php } ?>
	</div>

	<div class="ui-field">
		<label><?=$this->getText ('soldiers')?></label>
		<div class="squad-size">
			<button type="button" class="ui-btn squad-step" onclick="Game.dolumar.squads.step (this, -1);"<?=$disabled?>>−</button>
			<input type="number" name="size" value="<?=$size?>" min="<?=$isNew ? 1 : 0?>" max="<?=$max?>" step="1"
				oninput="Game.dolumar.squads.refresh (this.form);"<?=$disabled?> />
			<button type="button" class="ui-btn squad-step" onclick="Game.dolumar.squads.step (this, 1);"<?=$disabled?>>+</button>
			<span class="ui-muted"><?=Neuron_Core_Tools::putIntoText ($this->getText ('sizeHint'), array ('<span class="squad-max">'.$max.'</span>'))?></span>
		</div>
		<?php if ($canEdit) { ?>
			<p class="squad-quick">
				<?php if (!$isNew) { ?>
					<a href="javascript:void(0);" onclick="Game.dolumar.squads.setSize (this, 'none');"><?=$this->getText ('sizeNone')?></a> ·
				<?php } ?>
				<a href="javascript:void(0);" onclick="Game.dolumar.squads.setSize (this, 'half');"><?=$this->getText ('sizeHalf')?></a> ·
				<a href="javascript:void(0);" onclick="Game.dolumar.squads.setSize (this, 'all');"><?=$this->getText ('sizeAll')?></a>
			</p>
		<?php } ?>
	</div>

	<div class="ui-columns">
		<?php foreach ($list_equipment as $type) { ?>
			<div class="ui-field">
				<label><?=$type['name']?></label>
				<select name="equip_<?=$type['type']?>" class="squad-item" onchange="Game.dolumar.squads.refresh (this.form);"<?=$disabled?>>
					<option value="0"><?=$this->getText ('noItem')?></option>
					<?php foreach ($type['items'] as $item) { ?>
						<option value="<?=$item['id']?>" data-left="<?=$item['left']?>"<?=$item['selected'] ? ' selected="selected"' : ''?><?=(!$item['selected'] && $item['left'] < $size) ? ' disabled="disabled"' : ''?>><?=$item['name']?> (<?=Neuron_Core_Tools::putIntoText ($this->getText ('left'), array ($item['left']))?>)</option>
					<?php } ?>
				</select>
			</div>
		<?php } ?>
	</div>

	<p class="squad-short false ui-msg" style="display: none;"><?=$this->getText ('short')?></p>

	<?php if ($canEdit) { ?>
		<div class="ui-btn-row left">
			<button type="submit" class="ui-btn primary"><?=$this->getText ($isNew ? 'create' : 'save')?></button>
		</div>
	<?php } ?>

	<?php if (isset ($stats)) { ?>
		<div class="stats"><?=$stats?></div>
	<?php } ?>

	<?php if (!$isNew && $canDisband) { ?>
		<p>
			<a href="javascript:void(0);" onclick="confirmAction (this, {'page':'overview', 'remove':<?=$squadId?>}, '<?=$confirm?>');"><?=$this->getText ('disband')?></a>
		</p>
	<?php } ?>
</form>

<?php } ?>
