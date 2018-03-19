<h2>User management: <?=$username?></h2>

<h3>User details</h3>
<table>
	<tr>
		<td style="width: 150px;">Nickname:</td>
		<td><?=$username?></td>
	</tr>
	
	<tr class="diff">
		<td>Email:</td>
		<td><?=$email?></td>
	</tr>
	
	<tr>
		<td>Registration date:</td>
		<td><?=$registration?></td>
	</tr>
	
	<tr class="diff">
		<td>Last online:</td>
		<td><?=$lastrefresh?></td>
	</tr>
	
	<tr>
		<td>Premium end date:</td>
		<td><?=$premiumend?></td>
	</tr>
	
	<tr class="diff">
		<td>Admin level:</td>
		<td><?=$adminmodestring?></td>
	</tr>
</table>

<?php if (isset ($list_openids)) { ?>
	<h3>OpenID accounts</h3>
	<ul>
		<?php foreach ($list_openids as $v) { ?>
			<li>
				<a href="<?=$v['url']?>"><?=$v['url']?></a>
			</li>
		<?php } ?>
	</ul>
<?php } ?>

<?php if (isset ($list_villages)) { ?>
	<h3>Villages</h3>
	<?php foreach ($list_villages as $v) { ?>
		<ul>
			<li><a href="<?=$v['url']?>"><?=$v['village']?></a></li>
		</ul>
	<?php } ?>
<?php } ?>

<h3>Bans</h3>
<table>
	<?php foreach ($bans as $k => $v) { ?>
		<tr>
			<td class="log"><?=$v['channel']?></thd>
			<td class="date"><?=$v['duration']?></td>
			<td class="bans">
				<form method="post" action="<?=$v['url']?>" onsubmit="Game.admin.askReason(this);">
					<select name="duration">
						<?php foreach ($banoptions as $kb => $vb) { ?>
							<option value="<?=$kb?>"><?=$vb?></option>
						<?php } ?>
					</select>
		
					<button name="action" type="submit" value="ban">Ban</button>
					<button name="action" type="submit" value="unban">Unban</button>
				</form>
			</td>
		</tr>
	<?php } ?>
</table>

<?php if (isset ($list_history)) { ?>
	<h3>Moderator action history</h3>
	<table id="mod_action_history">
	<tr>
		<th>Date</th>
		<th class="action">Action</th>
		<th class="player">Admin</th>
		<th class="status">Status</th>
	</tr>

	<?php foreach ($list_history as $v) { ?>
		<tr class="<?=$v['status']?> <?php if (empty ($v['reason'])) { ?>nextrecord<?php } ?>">
			<td class="date"><?=$v['date']?></td>
			<td class="action"><?=$v['action']?></td>
			<td class="player"><?=$v['admin']?></td>
			<td class="status"><?=$v['status']?></td>
		</tr>
		
		<?php if (!empty ($v['reason'])) { ?>
			<tr class="<?=$v['status']?> nextrecord">
				<td class="reason" colspan="4">
					<?=$v['reason']?>
				</td>
			</tr>
		<?php } ?>
	<?php } ?>
	</table>
<?php } ?>

<h3>Actions</h3>
<ul class="actions">
	<li><a href="<?=$contact_url?>">Contact player</a></li>
	<?php if (isset ($logs_url)) { ?>
		<li><a href="<?=$logs_url?>">Show player logs</a></li>
	<?php } ?>
	
	<?php if (isset ($reset_url)) { ?>
		<li><a href="<?=$reset_url?>">Reset player account</a></li>
	<?php } ?>
</ul>

<?php if (isset ($admin_modes)) { ?>
	<h3>Admin status</h3>
	
	<form action="<?=$admin_action?>" method="post">
		<fieldset>
			<legend>Change modus</legend>
		
			<ol>
				<li>
					<select name="admin_status">
						<?php foreach ($admin_modes as $k => $v) { ?>
							<option value="<?=$k?>" <?php if ($k == $adminmode) { echo 'selected="selected"'; } ?>><?=$v?></option>
						<?php } ?>
					</select>
				</li>
				
				<li>
					<button type="submit"><span>Change</span></button>
				</li>
			</ol>
		</fieldset>
	</form>
<?php } ?>

<?php if (isset ($refundcredits)) { ?>
	<h3>Credits</h3>

	<?php if (isset ($refunddone)) { ?>
		<p class="true">
			Successfully refunded credits.
		</p>
	<?php } ?>

	<form method="post">
		<fieldset>
			<legend>Refund credits</legend>
		
			<ol>
				<li>
					<label for="refundcredits">Credits</label>
					<input type="text" name="refundcredits" id="refundcredits" value="0" />
				</li>

				<li>
					<label for="refundreason">Reason</label>
					<input type="text" name="refundreason" id="refundreason" />
				</li>
				
				<li>
					<button type="submit"><span>Refund credits</span></button>
				</li>
			</ol>
		</fieldset>
	</form>
<?php } ?>