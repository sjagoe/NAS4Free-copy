<?php
/*
	disks_raid_graid5_tools.php

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

$pgtitle = array(gettext("Disks"), gettext("Software RAID"), gettext("RAID5"), gettext("Tools"));

if (!isset($config['graid5']['vdisk']) || !is_array($config['graid5']['vdisk']))
	$config['graid5']['vdisk'] = array();

array_sort_key($config['graid5']['vdisk'], "name");
$a_raid = &$config['graid5']['vdisk'];

if ($_POST) {
	unset($input_errors);
	unset($do_action);

	/* input validation */
	$reqdfields = explode(" ", "action raid disk");
	$reqdfieldsn = array(gettext("Command"),gettext("Volume Name"),gettext("Disk"));
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	if (empty($input_errors)) {
		$do_action = true;
		$action = $_POST['action'];
		$raid = $_POST['raid'];
		$disk = $_POST['disk'];
	}
}

if (!isset($do_action)) {
	$do_action = false;
	$action = '';
	$object = '';
	$raid = '';
	$disk = '';
}
?>
<?php include("fbegin.inc");?>
<script type="text/javascript">
<!--
function raid_change() {
	var next = null;
	// Remove all entries from partition combobox.
	document.iform.disk.length = 0;
	// Insert entries for disk combobox.
	switch(document.iform.raid.value) {
		<?php foreach ($a_raid as $raidv): ?>
    case "<?=$raidv['name'];?>":
      <?php foreach($raidv['device'] as $devicen => $devicev): ?>
				<?php $name = str_replace("/dev/","",$devicev);?>
				if(document.all) // MS IE workaround.
					next = document.iform.disk.length;
				document.iform.disk.add(new Option("<?=$name;?>","<?=$name;?>",false,<?php if($name === $disk){echo "true";}else{echo "false";};?>), next);
				<?php endforeach; ?>
				break;
     <?php endforeach;?>
	}
}
// -->
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
		  <ul id="tabnav">
				<li class="tabinact"><a href="disks_raid_gconcat.php"><span><?=gettext("JBOD");?></span></a></li>
				<li class="tabinact"><a href="disks_raid_gstripe.php"><span><?=gettext("RAID 0");?></span></a></li>
				<li class="tabinact"><a href="disks_raid_gmirror.php"><span><?=gettext("RAID 1");?></span></a></li>
				<li class="tabact"><a href="disks_raid_graid5.php" title="<?=gettext("Reload page");?>"><span><?=gettext("RAID 5");?></span></a></li>
				<li class="tabinact"><a href="disks_raid_gvinum.php"><span><?=gettext("RAID 0/1/5");?></span></a></li>
		  </ul>
  	</td>
	</tr>
  <tr>
		<td class="tabnavtbl">
			<ul id="tabnav2">
				<li class="tabinact"><a href="disks_raid_graid5.php"><span><?=gettext("Management"); ?></span></a></li>
				<li class="tabact"><a href="disks_raid_graid5_tools.php" title="<?=gettext("Reload page");?>" ><span><?=gettext("Tools");?></span></a></li>
				<li class="tabinact"><a href="disks_raid_graid5_info.php"><span><?=gettext("Information"); ?></span></a></li>
			</ul>
		</td>
	</tr>
  <tr>
    <td class="tabcont">
			<?php if ($input_errors) print_input_errors($input_errors);?>
			<form action="disks_raid_graid5_tools.php" method="post" name="iform" id="iform">
			  <table width="100%" border="0" cellpadding="6" cellspacing="0">
          <tr>
          	<td width="22%" valign="top" class="vncellreq"><?=gettext("Volume Name");?></td>
      			<td width="78%" class="vtable">
			    	 <select name="raid" class="formfld" id="raid" onchange="raid_change()">
			    	 	<option value=""><?=gettext("Must choose one");?></option>
			    	  <?php foreach ($a_raid as $raidv): ?>
			    				<option value="<?=$raidv['name'];?>" <?php if ($raid === $raidv['name']) echo "selected=\"selected\"";?>>
			    				<?php echo htmlspecialchars($raidv['name']);	?>
			    				</option>
			    		  <?php endforeach; ?>
			    		</select>
			      </td>
			    </tr>
					<tr>
            <td width="22%" valign="top" class="vncellreq"><?=gettext("Disk");?></td>
            <td width="78%" class="vtable">
             <select name="disk" class="formfld" id="disk"></select>
             </td>
          </tr>
					<tr>
          	<td width="22%" valign="top" class="vncellreq"><?=gettext("Command");?></td>
      			<td width="78%" class="vtable">
              <select name="action" class="formfld" id="action">
                <option value="list" <?php if ($action == "list") echo "selected=\"selected\""; ?>>list</option>
                <option value="status" <?php if ($action == "status") echo "selected=\"selected\""; ?>>status</option>
                <option value="insert" <?php if ($action == "insert") echo "selected=\"selected\""; ?>>insert</option>
                <option value="remove" <?php if ($action == "remove") echo "selected=\"selected\""; ?>>remove</option>
                <option value="clear" <?php if ($action == "clear") echo "selected=\"selected\""; ?>>clear</option>
                <option value="stop" <?php if ($action == "stop") echo "selected=\"selected\""; ?>>stop</option>
								<option value="destroy" <?php if ($action == "destroy") echo "selected=\"selected\""; ?>>destroy</option>
								<option value="configure" <?php if ($action == "configure") echo "selected=\"selected\""; ?>>configure</option>
								<option value="dump" <?php if ($action == "dump") echo "selected=\"selected\""; ?>>dump</option>
               </select>
            </td>
          </tr>
				</table>
				<div id="submit">
					<input name="Submit" type="submit" class="formbtn" value="<?=gettext("Send Command!");?>" />
				</div>
				<?php if ($do_action) {
				echo(sprintf("<div id='cmdoutput'>%s</div>", gettext("Command output:")));
				echo('<pre class="cmdoutput">');
				//ob_end_flush();

				switch ($action) {
					case "insert":
						disks_geom_cmd("raid5", "insert -v", "{$raid} {$disk}", true);
						break;
					case "remove":
						disks_geom_cmd("raid5", "remove -v", "{$raid} {$disk}", true);
						break;
					case "dump":
						disks_geom_cmd("raid5", "dump", $disk, true);
						break;
					case "clear":
						disks_geom_cmd("raid5", "clear -v", $disk, true);
						break;
					case "list":
						disks_geom_cmd("raid5", "list", $raid, true);
						break;
					case "status":
						disks_geom_cmd("raid5", "status", $raid, true);
						break;
					case "stop":
						disks_geom_cmd("raid5", "stop -v", $raid, true);
						break;
					case "destroy":
						disks_geom_cmd("raid5", "destroy -v", $raid, true);
						break;
					case "configure":
						disks_geom_cmd("raid5", "configure -v", $raid, true);
						break;
				}

				echo('</pre>');
				};?>
				<div id="remarks">
					<?php html_remark("warning", gettext("Warning"), gettext("1. Use these specials actions for debugging only!<br />2. There is no need of using this menu for starting a RAID volume (start automaticaly)."));?>
				</div>
				<?php include("formend.inc");?>
			</form>
		</td>
	</tr>
</table>
<script type="text/javascript">
<!--
raid_change();
//-->
</script>
<?php include("fend.inc");?>
