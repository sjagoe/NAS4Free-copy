<?php
/*
	error.php

	Part of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2013 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

	Portions of Quixplorer (http://quixplorer.sourceforge.net).
	Authors: quix@free.fr, ck@realtime-projects.com.
	The Initial Developer of the Original Code is The QuiX project.

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
//------------------------------------------------------------------------------
// THESE ARE NUMEROUS HELPER FUNCTIONS FOR THE OTHER INCLUDE FILES
//------------------------------------------------------------------------------
function make_link($_action,$_dir,$_item=NULL,$_order=NULL,$_srt=NULL,$_lang=NULL) {
						// make link to next page
	if($_action=="" || $_action==NULL) $_action="list";
	if($_dir=="") $_dir=NULL;
	if($_item=="") $_item=NULL;
	if($_order==NULL) $_order=$GLOBALS["order"];
	if($_srt==NULL) $_srt=$GLOBALS["srt"];
	if($_lang==NULL) $_lang=(isset($GLOBALS["lang"])?$GLOBALS["lang"]:NULL);

	$link=$GLOBALS["script_name"]."?action=".$_action;
	if($_dir!=NULL) $link.="&dir=".urlencode($_dir);
	if($_item!=NULL) $link.="&item=".urlencode($_item);
	if($_order!=NULL) $link.="&order=".$_order;
	if($_srt!=NULL) $link.="&srt=".$_srt;
	if($_lang!=NULL) $link.="&lang=".$_lang;

	return $link;
}

function get_abs_dir($path)
{
    return path_f($path);
}

function path_f ($path)
{
    global $home_dir;
    $abs_dir = $home_dir;
    if ($path != "")
         $abs_dir .= "/$path";
    $abs_dir = realpath($abs_dir);
    return $abs_dir;
}

function path_r ($path)
{
    global $home_dir;
    $base = realpath($home_dir);
    $ret = preg_replace("#^$base#", "", $path);
    return $ret;
}

function get_abs_item($dir, $item) {		// get absolute file+path
	return get_abs_dir($dir)."/".$item;
}
//------------------------------------------------------------------------------
function get_rel_item($dir,$item) {		// get file relative from home
	if($dir!="") return $dir."/".$item;
	else return $item;
}
//------------------------------------------------------------------------------
function get_is_file($dir, $item) {		// can this file be edited?
	return @is_file(get_abs_item($dir,$item));
}
//------------------------------------------------------------------------------
function get_is_dir($dir, $item) {		// is this a directory?
	return @is_dir(get_abs_item($dir,$item));
}
//------------------------------------------------------------------------------
function parse_file_type($dir,$item) {		// parsed file type (d / l / -)
	$abs_item = get_abs_item($dir, $item);
	if(@is_dir($abs_item)) return "d";
	if(@is_link($abs_item)) return "l";
	return "-";
}
//------------------------------------------------------------------------------
function get_file_perms($dir,$item) {		// file permissions
	return @decoct(@fileperms(get_abs_item($dir,$item)) & 0777);
}
//------------------------------------------------------------------------------
function parse_file_perms($mode) {		// parsed file permisions
	if(strlen($mode)<3) return "---------";
	$parsed_mode="";
	for($i=0;$i<3;$i++) {
		// read
		if(($mode{$i} & 04)) $parsed_mode .= "r";
		else $parsed_mode .= "-";
		// write
		if(($mode{$i} & 02)) $parsed_mode .= "w";
		else $parsed_mode .= "-";
		// execute
		if(($mode{$i} & 01)) $parsed_mode .= "x";
		else $parsed_mode .= "-";
	}
	return $parsed_mode;
}
//------------------------------------------------------------------------------
function get_file_size($dir, $item) {		// file size
	return @filesize(get_abs_item($dir, $item));
}
//------------------------------------------------------------------------------
function parse_file_size($size) {		// parsed file size
	if($size >= 1073741824) {
		$size = round($size / 1073741824 * 100) / 100 . " GiB";
	} elseif($size >= 1048576) {
		$size = round($size / 1048576 * 100) / 100 . " MiB";
	} elseif($size >= 1024) {
		$size = round($size / 1024 * 100) / 100 . " KiB";
	} else $size = $size . " Bytes";
	if($size==0) $size="-";

	return $size;
}
//------------------------------------------------------------------------------
function get_file_date($dir, $item) {		// file date
	return @filemtime(get_abs_item($dir, $item));
}
//------------------------------------------------------------------------------
function parse_file_date($date) {		// parsed file date
	return @date($GLOBALS["date_fmt"],$date);
}
//------------------------------------------------------------------------------
function get_is_image($dir, $item) {		// is this file an image?
	if(!get_is_file($dir, $item)) return false;
	return @eregi($GLOBALS["images_ext"], $item);
}
//-----------------------------------------------------------------------------
function get_is_editable($dir, $item) {		// is this file editable?
	if(!get_is_file($dir, $item)) return false;
	foreach($GLOBALS["editable_ext"] as $pat) if(@eregi($pat,$item)) return true;
	return false;
}
//-----------------------------------------------------------------------------
function get_is_unzipable($dir, $item) {		// is this file editable?
	if(!get_is_file($dir, $item)) return false;
	foreach($GLOBALS["unzipable_ext"] as $pat) if(@eregi($pat,$item)) return true;
	return false;
}
//-----------------------------------------------------------------------------
function get_mime_type($dir, $item, $query) {	// get file's mimetype
	if(get_is_dir($dir, $item)) {			// directory
		$mime_type	= $GLOBALS["super_mimes"]["dir"][0];
		$image		= $GLOBALS["super_mimes"]["dir"][1];

		if($query=="img") return $image;
		else return $mime_type;
	}
				// mime_type
	foreach($GLOBALS["used_mime_types"] as $mime) {
		list($desc,$img,$ext,$type)	= $mime;
		if(@eregi($ext,$item)) {
			$mime_type	= $desc;
			$image		= $img;
			if($query=="img"){ return $image;}
			else if($query=="ext"){ return $type;}
			else return $mime_type;


		}
	}

	if((function_exists("is_executable") &&
		@is_executable(get_abs_item($dir,$item))) ||
		@eregi($GLOBALS["super_mimes"]["exe"][2],$item))
	{						// executable
		$mime_type	= $GLOBALS["super_mimes"]["exe"][0];
		$image		= $GLOBALS["super_mimes"]["exe"][1];
	} else {					// unknown file
		$mime_type	= $GLOBALS["super_mimes"]["file"][0];
		$image		= $GLOBALS["super_mimes"]["file"][1];
	}

	if($query=="img") return $image;
	else return $mime_type;
}

/**
    Check if user is allowed to access $file in $directory

 */
function get_show_item ($directory, $file)
{
    if ( preg_match( "/\.\./", $directory ) )
        return false;

    if ( isset($file) && preg_match( "/\.\./", $file ) )
        return false;

    // dont display own directory
    if ( $file == "." )
        return false;

    if ( substr( $file, 0, 1) == "." && $GLOBALS["show_hidden"] == false )
        return false;

    global $no_access;
    if ( defined($no_access) && $no_access != "" && preg_match( "%$no_access%", $file ) )
        return false;

    if ( $GLOBALS["show_hidden"] == false )
    {
      $directory_parts = explode( "/", $directory );
      foreach ($directory_parts as $directory_part )
      {
        if ( substr ( $directory_part, 0, 1) == "." )
          return false;
      }
    }

    return true;
}

//------------------------------------------------------------------------------
function copy_dir($source,$dest) {		// copy dir
	$ok = true;

	if ( !@mkdir($dest,0777) )
        return false;
	if ( ($handle = @opendir( $source ) ) === false)
        show_error($source."xx:".basename($source)."xx : ".$GLOBALS["error_msg"]["opendir"]);

	while(($file=readdir($handle))!==false) {
		if(($file==".." || $file==".")) continue;

		$new_source = $source."/".$file;
		$new_dest = $dest."/".$file;
		if(@is_dir($new_source)) {
			$ok=copy_dir($new_source,$new_dest);
		} else {
			$ok=@copy($new_source,$new_dest);
		}
	}
	closedir($handle);
	return $ok;
}

/**
    remove file / dir
 */
function remove ( $item )
{
	$ok = true;
	if(@is_link($item) || @is_file($item)) $ok=@unlink($item);
	elseif(@is_dir($item))
    {
		if(($handle=@opendir($item))===false)
            show_error($item.":".basename($item).": ".$GLOBALS["error_msg"]["opendir"]);

		while(($file=readdir($handle))!==false) {
			if(($file==".." || $file==".")) continue;

			$new_item = $item."/".$file;
			if(!@file_exists($new_item)) show_error(basename($item).": ".$GLOBALS["error_msg"]["readdir"]);
			//if(!get_show_item($item, $new_item)) continue;

			if(@is_dir($new_item)) {
				$ok=remove($new_item);
			} else {
				$ok=@unlink($new_item);
			}
		}

		closedir($handle);
		$ok=@rmdir($item);
	}
	return $ok;
}
//------------------------------------------------------------------------------
function get_max_file_size() {			// get php max_upload_file_size
	$max = get_cfg_var("upload_max_filesize");
	if(@eregi("G$",$max)) {
		$max = substr($max,0,-1);
		$max = round($max*1073741824);
	} elseif(@eregi("M$",$max)) {
		$max = substr($max,0,-1);
		$max = round($max*1048576);
	} elseif(@eregi("K$",$max)) {
		$max = substr($max,0,-1);
		$max = round($max*1024);
	}

	return $max;
}
//------------------------------------------------------------------------------
function down_home($abs_dir) {			// dir deeper than home?
	$real_home = @realpath($GLOBALS["home_dir"]);
	$real_dir = @realpath($abs_dir);

	if($real_home===false || $real_dir===false) {
		if(@eregi("\\.\\.",$abs_dir)) return false;
	} else if(strcmp($real_home,@substr($real_dir,0,strlen($real_home)))) {
		return false;
	}
	return true;
}
//------------------------------------------------------------------------------
function id_browser() {
	$browser=$GLOBALS['__SERVER']['HTTP_USER_AGENT'];

	if(preg_match('#Opera(/| )([0-9].[0-9]{1,2})#', $browser)) {
		return 'OPERA';
	} else if(preg_match('/MSIE ([0-9].[0-9]{1,2})/', $browser)) {
		return 'IE';
	} else if(preg_match('#OmniWeb/([0-9].[0-9]{1,2})#', $browser)) {
		return 'OMNIWEB';
	} else if(preg_match('#(Konqueror/)(.*)#', $browser)) {
		return 'KONQUEROR';
	} else if(preg_match('#Mozilla/([0-9].[0-9]{1,2})#', $browser)) {
		return 'MOZILLA';
	} else {
		return 'OTHER';
	}
}
//------------------------------------------------------------------------------
?>
