<?php $this->setTextSection ('editor', 'squads'); ?>

<p class="squad-back">
	<a href="javascript:void(0);" onclick="windowAction (this, {'page':'overview'});">‹ <?=$this->getText ('back')?></a>
</p>

<?php if (isset ($list_errors)) { foreach ($list_errors as $v) { ?>
	<p class="false"><?=$v?></p>
<?php } } ?>

<?php if (isset ($list_notices)) { foreach ($list_notices as $v) { ?>
	<p class="true"><?=$v?></p>
<?php } } ?>

<?php if (isset ($noUnits)) { ?>
	<p class="false"><?=$noUnits?></p>
<?php } else { ?>

<?php $disabled = $canEdit ? '' : ' disabled="disabled"'; ?>

<form class="squad-editor" onsubmit="return submitForm(this);" data-current="<?=$current?>">
	<?php if ($isNew) { ?>
		<input type="hidden" class="hidden" name="page" value="add" />
		<input type="hidden" class="hidden" name="action" value="create" />

		<div class="squad-field">
			<label class="squad-label"><?=$this->getText ('unit')?></label>
			<div class="squad-unit-tiles">
				<?php foreach ($list_unitTypes as $v) { ?>
					<label class="squad-unit-tile<?=$v['selected'] ? ' selected' : ''?>">
						<input type="radio" name="squadUnit" value="<?=$v['id']?>"
							data-free="<?=$v['free']?>" data-suggestion="<?=$v['suggestion']?>" data-unit="<?=$v['name']?>"
							onclick="Game.dolumar.squads.pickUnit (this);"<?=$v['selected'] ? ' checked="checked"' : ''?> />
						<img src="<?=$v['image']?>" alt="" />
						<span class="squad-unit-name"><?=$v['name']?></span>
						<span class="squad-unit-free"><?=Neuron_Core_Tools::putIntoText ($this->getText ('free'), array ($v['free']))?></span>
					</label>
				<?php } ?>
			</div>
		</div>
	<?php } else { ?>
		<input type="hidden" class="hidden" name="page" value="squad" />
		<input type="hidden" class="hidden" name="action" value="save" />
		<input type="hidden" class="hidden" name="id" value="<?=$squadId?>" />

		<div class="squad-head">
			<?php if (isset ($image)) { ?><img class="squad-portrait" src="<?=$image?>" alt="" /><?php } ?>
			<div class="squad-head-text">
				<span class="squad-meta"><?=$unitName?> · <?=$statusText?></span>
			</div>
		</div>
	<?php } ?>

	<div class="squad-field">
		<label class="squad-label"><?=$this->getText ('name')?></label>
		<input type="text" class="squad-name-input" name="squadName" value="<?=$name?>" maxlength="20"
			data-suggested="<?=$isNew ? '1' : '0'?>" oninput="this.setAttribute ('data-suggested', '0');"<?=$disabled?> />
		<?php if ($isNew) { ?>
			<span class="squad-hint"><?=$this->getText ('nameHint')?></span>
		<?php } ?>
	</div>

	<div class="squad-field">
		<label class="squad-label"><?=$this->getText ('soldiers')?></label>
		<div class="squad-size">
			<span class="squad-stepper">
				<button type="button" class="squad-step" onclick="Game.dolumar.squads.step (this, -1);"<?=$disabled?>>−</button>
				<input type="number" name="size" value="<?=$size?>" min="<?=$isNew ? 1 : 0?>" max="<?=$max?>" step="1"
					oninput="Game.dolumar.squads.refresh (this.form);"<?=$disabled?> />
				<button type="button" class="squad-step" onclick="Game.dolumar.squads.step (this, 1);"<?=$disabled?>>+</button>
			</span>
			<span class="squad-hint squad-size-hint"><?=Neuron_Core_Tools::putIntoText ($this->getText ('sizeHint'), array ('<span class="squad-max">'.$max.'</span>'))?></span>
		</div>
		<?php if ($canEdit) { ?>
			<div class="squad-quick">
				<?php if (!$isNew) { ?>
					<button type="button" onclick="Game.dolumar.squads.setSize (this, 'none');"><?=$this->getText ('sizeNone')?></button>
				<?php } ?>
				<button type="button" onclick="Game.dolumar.squads.setSize (this, 'half');"><?=$this->getText ('sizeHalf')?></button>
				<button type="button" onclick="Game.dolumar.squads.setSize (this, 'all');"><?=$this->getText ('sizeAll')?></button>
			</div>
		<?php } ?>
	</div>

	<div class="squad-equipment">
		<?php foreach ($list_equipment as $type) { ?>
			<div class="squad-field">
				<label class="squad-label"><?=$type['name']?></label>
				<select name="equip_<?=$type['type']?>" class="squad-item squad-item-<?=$type['type']?>" onchange="Game.dolumar.squads.refresh (this.form);"<?=$disabled?>>
					<option value="0"><?=$this->getText ('noItem')?></option>
					<?php foreach ($type['items'] as $item) { ?>
						<option value="<?=$item['id']?>" data-left="<?=$item['left']?>"<?=$item['selected'] ? ' selected="selected"' : ''?><?=(!$item['selected'] && $item['left'] < $size) ? ' disabled="disabled"' : ''?>><?=$item['name']?> (<?=Neuron_Core_Tools::putIntoText ($this->getText ('left'), array ($item['left']))?>)</option>
					<?php } ?>
				</select>
			</div>
		<?php } ?>
	</div>

	<p class="squad-short false" style="display: none;"><?=$this->getText ('short')?></p>

	<?php if ($canEdit) { ?>
		<p class="squad-submit">
			<button type="submit" class="primary"><?=$this->getText ($isNew ? 'create' : 'save')?></button>
		</p>
	<?php } ?>

	<?php if (isset ($stats)) { ?>
		<div class="stats"><?=$stats?></div>
	<?php } ?>

	<?php if (!$isNew && $canDisband) { ?>
		<p class="squad-disband">
			<a href="javascript:void(0);" onclick="confirmAction (this, {'page':'overview', 'remove':<?=$squadId?>}, '<?=$confirm?>');"><?=$this->getText ('disband')?></a>
		</p>
	<?php } ?>
</form>

<?php } ?>
