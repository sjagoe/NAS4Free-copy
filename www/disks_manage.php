<?php
/*
	disks_manage.php

	Part of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2013 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

	Portions of freenas (http://www.freenas.org).
	Copyright (c) 2005-2011 by Olivier Cochard <olivier@freenas.org>.
	All rights reserved.	

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met: 

	1. Redistributions of source code must retain the above copyright notice, this
	   list of conditions and the following disclaimer. 
	2. Redistributions in binary form must reproduce the above copyright notice,
	   this list of conditions and the following disclaimer in the documentation
	   and/or other materials provided with the distribution. 

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	The views and conclusions contained in the software and documentation are those
	of the authors and should not be interpreted as representing official policies, 
	either expressed or implied, of the NAS4Free Project.
*/
require("auth.inc");
require("guiconfig.inc");

$pgtitle = array(gettext("Disks"),gettext("Management"));

if ($_POST) {
	$pconfig = $_POST;

	$clean_import = false;
	if (!empty($_POST['clear_import']) || !empty($_POST['clear_import_swraid'])) {
		$clean_import = true;
	}
	if (!empty($_POST['import']) || !empty($_POST['clear_import'])) {
		$retval = disks_import_all_disks($clean_import);
		if ($retval == 0) {
			$savemsg = gettext("no new disk found.");
		} else if ($retval > 0) {
			$savemsg = gettext("all disks are imported.");
		} else {
			$input_errors[] = gettext("detected an error while importing.");
		}
		if ($retval >= 0) {
			disks_update_mounts();
		}
		//skip redirect
		//header("Location: disks_manage.php");
		//exit;
	}
	if (!empty($_POST['import_swraid']) || !empty($_POST['clear_import_swraid'])) {
		$retval = disks_import_all_swraid_disks($clean_import);
		if ($retval == 0) {
			$savemsg = gettext("no new software raid disk found.");
		} else if ($retval > 0) {
			$savemsg = gettext("all software raid disks are imported.");
		} else {
			$input_errors[] = gettext("detected an error while importing.");
		}
		if ($retval >= 0) {
			disks_update_mounts();
		}
		//skip redirect
		//header("Location: disks_manage.php");
		//exit;
	}
	if (isset($_POST['apply']) && $_POST['apply']) {
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			$retval |= updatenotify_process("device", "diskmanagement_process_updatenotification");
			config_lock();
			$retval |= rc_update_service("ataidle");
			$retval |= rc_update_service("smartd");
			config_unlock();
		}
		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			updatenotify_delete("device");
		}
		header("Location: disks_manage.php");
		exit;
	}
	if (isset($_POST['disks_rescan']) && $_POST['disks_rescan']) {
		$do_action = true;
		$disks_rescan = true;
	}
}

if (!isset($do_action)) {
	$do_action = false;
}

// Get all physical disks including CDROM.
$a_phy_disk = array_merge((array)get_physical_disks_list(), (array)get_cdrom_list());

if (!isset($config['disks']['disk']) || !is_array($config['disks']['disk']))
	$config['disks']['disk'] = array();

array_sort_key($config['disks']['disk'], "name");
$a_disk_conf = &$config['disks']['disk'];

if (isset($_GET['act']) && $_GET['act'] === "del") {
	updatenotify_set("device", UPDATENOTIFY_MODE_DIRTY, $_GET['uuid']);
	header("Location: disks_manage.php");
	exit;
}

function diskmanagement_process_updatenotification($mode, $data) {
	global $config;

	$retval = 0;

	switch ($mode) {
		case UPDATENOTIFY_MODE_NEW:
		case UPDATENOTIFY_MODE_MODIFIED:
			break;
		case UPDATENOTIFY_MODE_DIRTY:
			if (is_array($config['disks']['disk'])) {
				$index = array_search_ex($data, $config['disks']['disk'], "uuid");
				if (false !== $index) {
					unset($config['disks']['disk'][$index]);
					write_config();
				}
			}
			break;
	}

	return $retval;
}
?>
<?php include("fbegin.inc");?>
<?php
	// make sure detected disks have same ID in config.
	$verify_errors = disks_verify_all_disks();
	if (!empty($verify_errors)) {
		$errormsg .= gettext("There is wrong disk ID in the config. Please remove the disk and re-add it or use 'clear and import'.");
		$errormsg .= "<br />\n";
	}
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
		<td class="tabnavtbl">
  		<ul id="tabnav">
				<li class="tabact"><a href="disks_manage.php" title="<?=gettext("Reload page");?>"><span><?=gettext("Management");?></span></a></li>
				<li class="tabinact"><a href="disks_manage_smart.php"><span><?=gettext("S.M.A.R.T.");?></span></a></li>
				<li class="tabinact"><a href="disks_manage_iscsi.php"><span><?=gettext("iSCSI Initiator");?></span></a></li>
  		</ul>
  	</td>
	</tr>
  <tr>
    <td class="tabcont">
			<form action="disks_manage.php" method="post">
				<?php if (!empty($savemsg)) print_info_box($savemsg); ?>
				<?php if (!empty($errormsg)) print_error_box($errormsg);?>
				<?php if (!empty($input_errors)) print_input_errors($input_errors);?>
				<?php if (updatenotify_exists("device")) print_config_change_box();?>
				<table width="100%" border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td width="5%" class="listhdrlr"><?=gettext("Disk"); ?></td>
						<td width="5%" class="listhdrr"><?=gettext("Size"); ?></td>
						<td width="22%" class="listhdrr"><?=gettext("Description"); ?></td>
						<td width="15%" class="listhdrr"><?=gettext("Device model"); ?></td>
						<td width="15%" class="listhdrr"><?=gettext("Serial number"); ?></td>
						<td width="10%" class="listhdrr"><?=gettext("Standby time"); ?></td>
						<td width="10%" class="listhdrr"><?=gettext("File system"); ?></td>
						<td width="8%" class="listhdrr"><?=gettext("Status"); ?></td>
						<td width="10%" class="list"></td>
					</tr>
					<?php foreach ($a_disk_conf as $disk):?>
					<?php
					$notificationmode = updatenotify_get_mode("device", $disk['uuid']);
					switch ($notificationmode) {
						case UPDATENOTIFY_MODE_NEW:
							$status = gettext("Initializing");
							break;
						case UPDATENOTIFY_MODE_MODIFIED:
							$status = gettext("Modifying");
							break;
						case UPDATENOTIFY_MODE_DIRTY:
							$status = gettext("Deleting");
							break;
						default:
							if ($disk['type'] == 'HAST') {
								$role = $a_phy_disk[$disk['name']]['role'];
								$status = sprintf("%s (%s)", (0 == disks_exists($disk['devicespecialfile'])) ? gettext("ONLINE") : gettext("MISSING"), $role);
								$disk['size'] = $a_phy_disk[$disk['name']]['size'];
							} else {
								$status = (0 == disks_exists($disk['devicespecialfile'])) ? gettext("ONLINE") : sprintf("%s (%s)", gettext("MISSING"), $disk['devicespecialfile']);
							}
							break;
					}
					$verify_error = !empty($verify_errors[$disk['devicespecialfile']]) ? true : false;
					?>
					<tr>
<?php if (!empty($verify_error)) { ?>
						<td class="listlr"><span style="color: #ff0000;font-weight:bold;"><?=htmlspecialchars($disk['name']);?></span></td>
<?php } else { ?>
						<td class="listlr"><?=htmlspecialchars($disk['name']);?></td>
<?php } ?>
						<td class="listr"><?=htmlspecialchars($disk['size']);?></td>
						<td class="listr"><?=htmlspecialchars($disk['desc']);?>&nbsp;</td>
						<td class="listr"><?=htmlspecialchars(system_get_volume_model($disk['devicespecialfile']));?>&nbsp;</td>
<?php if (!empty($verify_error)) { ?>
						<td class="listr"><span style="color: #ff0000;font-weight:bold;"><?=htmlspecialchars(system_get_volume_serial($disk['devicespecialfile']));?></span>&nbsp;</td>
<?php } else { ?>
						<td class="listr"><?=htmlspecialchars(system_get_volume_serial($disk['devicespecialfile']));?>&nbsp;</td>
<?php } ?>
						<td class="listr"><?php if ($disk['harddiskstandby']) { echo htmlspecialchars($disk['harddiskstandby']); } else { echo htmlspecialchars(gettext("Always on")); }?>&nbsp;</td>
						<td class="listr"><?=(!empty($disk['fstype'])) ? htmlspecialchars(get_fstype_shortdesc($disk['fstype'])) : htmlspecialchars(gettext("Unknown or unformatted"))?>&nbsp;</td>
						<td class="listbg"><?=htmlspecialchars($status);?>&nbsp;</td>
						<?php if (UPDATENOTIFY_MODE_DIRTY != $notificationmode):?>
						<td valign="middle" nowrap="nowrap" class="list">
							<a href="disks_manage_edit.php?uuid=<?=$disk['uuid'];?>"><img src="e.gif" title="<?=gettext("Edit disk");?>" border="0" alt="<?=gettext("Edit disk");?>" /></a>&nbsp;
							<a href="disks_manage.php?act=del&amp;uuid=<?=$disk['uuid'];?>" onclick="return confirm('<?=gettext("Do you really want to delete this disk? All elements that still use it will become invalid (e.g. share)!"); ?>')"><img src="x.gif" title="<?=gettext("Delete disk"); ?>" border="0" alt="<?=gettext("Delete disk"); ?>" /></a>
						</td>
						<?php else:?>
						<td valign="middle" nowrap="nowrap" class="list">
							<img src="del.gif" border="0" alt="" />
						</td>
						<?php endif;?>
					</tr>
					<?php endforeach;?>
					<tr>
						<td class="list" colspan="8"></td>
						<td class="list"> <a href="disks_manage_edit.php"><img src="plus.gif" title="<?=gettext("Add disk"); ?>" border="0" alt="<?=gettext("Add disk"); ?>" /></a></td>
					</tr>
				</table>
				<div id="submit">
					<input name="import" type="submit" class="formbtn" value="<?=gettext("Import disks");?>" onclick="return confirm('<?=gettext("Do you really want to import?\\nThe existing config may be overwritten.");?>');" />
					<input name="clear_import" type="submit" class="formbtn" value="<?=gettext("Clear config and Import disks");?>" onclick="return confirm('<?=gettext("Do you really want to clear and import?\\nThe existing config will be cleared and overwritten.");?>');" />
					<input name="disks_rescan" type="submit" class="formbtn" value="<?=gettext("Rescan disks");?>" />
					<br />
					<br />
					<input name="import_swraid" type="submit" class="formbtn" value="<?=gettext("Import software raid disks");?>" onclick="return confirm('<?=gettext("Do you really want to import?\\nThe existing config may be overwritten.");?>');" />
					<input name="clear_import_swraid" type="submit" class="formbtn" value="<?=gettext("Clear config and Import software raid disks");?>" onclick="return confirm('<?=gettext("Do you really want to clear and import?\\nThe existing config will be cleared and overwritten.");?>');" />
				</div>
				<?php
				if ($do_action) {
					echo(sprintf("<div id='cmdoutput'>%s</div>", gettext("Command output:")));
					echo('<pre class="cmdoutput">');
					//ob_end_flush();
					if (true == $disks_rescan) {
						disks_rescan();
					}
					echo('</pre>');
					echo('<script type="text/javascript">');
					echo('window.location.href="disks_manage.php"');
					echo('</script>');
				}?>
				<?php include("formend.inc");?>
			</form>
		</td>
	</tr>
</table>
<?php include("fend.inc");?>
