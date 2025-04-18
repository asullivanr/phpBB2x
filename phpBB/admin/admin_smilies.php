<?php
/***************************************************************************
*                               admin_smilies.php
*                              -------------------
*     begin                : Thu May 31, 2001
*     copyright            : (C) 2001 The phpBB Group
*     email                : support@phpbb.com
*
*     $Id$
*
****************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/

/**************************************************************************
*	This file will be used for modifying the smiley settings for a board.
**************************************************************************/

if (!defined('IN_PHPBB'))
{
    define( 'IN_PHPBB', 1);
}

//
// First we do the setmodules stuff for the admin cp.
//
if( !empty($setmodules) )
{
	$filename = basename(__FILE__);
	$module['General']['Smilies'] = $filename;

	return;
}

$phpbb_root_path = "./../";
require($phpbb_root_path . 'extension.inc');

$cancel = ( isset($_POST['cancel']) || isset($_POST['cancel']) ) ? true : false;
$no_page_header = $cancel;

//
// Load default header
//
if ((!empty($_GET['export_pack']) && $_GET['export_pack'] == 'send') || (!empty($_GET['export_pack']) && $_GET['export_pack'] == 'send'))
{
	$no_page_header = true;
}

require('./pagestart.' . $phpEx);

if ($cancel)
{
	redirect('admin/' . append_sid("admin_smilies.$phpEx", true));
}

//
// Check to see what mode we should operate in.
//
if( isset($_POST['mode']) || isset($_GET['mode']) )
{
	$mode = ( isset($_POST['mode']) ) ? $_POST['mode'] : $_GET['mode'];
	$mode = htmlspecialchars($mode, ENT_COMPAT, 'utf-8');
}
else
{
	$mode = "";
}

$delimeter  = '=+:';

//
// Read a listing of uploaded smilies for use in the add or edit smliey code...
//
$dir = @opendir($phpbb_root_path . $board_config['smilies_path']);

while($file = @readdir($dir))
{
	if( !@is_dir(phpbb_realpath($phpbb_root_path . $board_config['smilies_path'] . '/' . $file)) )
	{
		$img_size = @getimagesize($phpbb_root_path . $board_config['smilies_path'] . '/' . $file);

		if( $img_size[0] && $img_size[1] )
		{
			$smiley_images[] = $file;
		}
		else if( preg_match('/.pak$/i', $file) )
		{	
			$smiley_paks[] = $file;
		}
	}
}

@closedir($dir);

//
// Select main mode
//
$s_hidden_fields = ( isset($s_hidden_fields) ) ? $s_hidden_fields : '';
if( isset($_GET['import_pack']) || isset($_POST['import_pack']) )
{
	//
	// Import a list a "Smiley Pack"
	//
	
	if (!isset($_GET['smile_pak']))
    {
        $_GET['smile_pak']='';
    }
    if (!isset($_GET['clear_current']))
    {
        $_GET['clear_current']='';
    }
    if (!isset($_GET['replace']))
    {
        $_GET['replace']='';
    }
	
	$smile_pak = ( isset($_POST['smile_pak']) ) ? $_POST['smile_pak'] : $_GET['smile_pak'];
	$clear_current = ( isset($_POST['clear_current']) ) ? $_POST['clear_current'] : $_GET['clear_current'];
	$replace_existing = ( isset($_POST['replace']) ) ? $_POST['replace'] : $_GET['replace'];

	if ( !empty($smile_pak) )
	{
		//
		// The user has already selected a smile_pak file.. Import it.
		//
		if( !empty($clear_current)  )
		{
			$sql = "DELETE 
				FROM " . SMILIES_TABLE;
			if( !$result = $db->sql_query($sql) )
			{
				message_die(GENERAL_ERROR, "Couldn't delete current smilies", "", __LINE__, __FILE__, $sql);
			}
		}
		else
		{
			$sql = "SELECT code 
				FROM ". SMILIES_TABLE;
			if( !$result = $db->sql_query($sql) )
			{
				message_die(GENERAL_ERROR, "Couldn't get current smilies", "", __LINE__, __FILE__, $sql);
			}

			$cur_smilies = $db->sql_fetchrowset($result);

	        foreach( $cur_smilies as $smiley )
            {
                $k = $smiley['code'];
                $smiles[$k] = 1;
            }
		}

		$fcontents = @file($phpbb_root_path . $board_config['smilies_path'] . '/'. $smile_pak);

		if( empty($fcontents) )
		{
			message_die(GENERAL_ERROR, "Couldn't read smiley pak file", "", __LINE__, __FILE__, $sql);
		}

		for ($i = 0; $i < count($fcontents); $i++) {
			$smile_data = explode($delimeter, trim(addslashes($fcontents[$i])));
		 
			for ($j = 2; $j < count($smile_data); $j++) {
				//
				// Replace > and < with the proper html_entities for matching.
				//
				$smile_data[$j] = str_replace("<", "&lt;", $smile_data[$j]);
				$smile_data[$j] = str_replace(">", "&gt;", $smile_data[$j]);
				$k = $smile_data[$j];
		 
				if (!isset($smiles[$k])) {
					$smiles[$k] = 0;
				}
		 
				if ($smiles[$k] == 1) {
					if (!empty($replace_existing)) {
						$sql = "UPDATE " . SMILIES_TABLE . " 
							SET smile_url = '" . str_replace("'", "''", $smile_data[0]) . "', emoticon = '" . str_replace("'", "''", $smile_data[1]) . "' 
							WHERE code = '" . str_replace("'", "''", $smile_data[$j]) . "'";
					} else {
						$sql = '';
					}
				} else {
					$sql = "INSERT INTO " . SMILIES_TABLE . " (code, smile_url, emoticon)
						VALUES('" . str_replace("'", "''", $smile_data[$j]) . "', '" . str_replace("'", "''", $smile_data[0]) . "', '" . str_replace("'", "''", $smile_data[1]) . "')";
				}
		 
				if ($sql != '') {
					$result = $db->sql_query($sql);
					if (!$result) {
						message_die(GENERAL_ERROR, "Couldn't update smilies!", "", __LINE__, __FILE__, $sql);
					}
				}
			}
		}

		$message = $lang['smiley_import_success'] . "<br /><br />" . sprintf($lang['Click_return_smileadmin'], "<a href=\"" . append_sid("admin_smilies.$phpEx") . "\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"" . append_sid("index.$phpEx?pane=right") . "\">", "</a>");

		message_die(GENERAL_MESSAGE, $message);
		
	}
	else
	{
		//
		// Display the script to get the smile_pak cfg file...
		//
		$smile_paks_select = "<select name='smile_pak'><option value=''>" . $lang['Select_pak'] . "</option>";
		while( list($key, $value) = @each($smiley_paks) )
		{
			if ( !empty($value) ) 
			{
				$smile_paks_select .= "<option>" . $value . "</option>";
			}
		}
		$smile_paks_select .= "</select>";

		$hidden_vars = "<input type='hidden' name='mode' value='import'>";	

		$template->set_filenames(array(
			"body" => "admin/smile_import_body.tpl")
		);

		$template->assign_vars(array(
			"L_SMILEY_TITLE" => $lang['smiley_title'],
			"L_SMILEY_EXPLAIN" => $lang['smiley_import_inst'],
			"L_SMILEY_IMPORT" => $lang['smiley_import'],
			"L_SELECT_LBL" => $lang['choose_smile_pak'],
			"L_IMPORT" => $lang['import'],
			"L_CONFLICTS" => $lang['smile_conflicts'],
			"L_DEL_EXISTING" => $lang['del_existing_smileys'], 
			"L_REPLACE_EXISTING" => $lang['replace_existing'], 
			"L_KEEP_EXISTING" => $lang['keep_existing'], 

			"S_SMILEY_ACTION" => append_sid("admin_smilies.$phpEx"),
			"S_SMILE_SELECT" => $smile_paks_select,
			"S_HIDDEN_FIELDS" => $hidden_vars)
		);

		$template->pparse("body");
	}
}
else if( isset($_POST['export_pack']) || isset($_GET['export_pack']) )
{
	//
	// Export our smiley config as a smiley pak...
	//
	
	if (isset($_GET['export_pack']))
    {
	if ( $_GET['export_pack'] == "send" )
	{	
		$sql = "SELECT * 
			FROM " . SMILIES_TABLE;
		if( !$result = $db->sql_query($sql) )
		{
			message_die(GENERAL_ERROR, "Could not get smiley list", "", __LINE__, __FILE__, $sql);
		}

		$resultset = $db->sql_fetchrowset($result);

        $smile_pak = "";
        foreach ($resultset as $result)
        {
            $smile_pak .= $result['smile_url'] . $delimeter;
            $smile_pak .= $result['emoticon'] . $delimeter;
            $smile_pak .= $result['code'] . "\n";
        }

		header("Content-Type: text/x-delimtext; name=\"smiles.pak\"");
		header("Content-disposition: attachment; filename=smiles.pak");

		echo $smile_pak;

		exit;
	}
	}
	
	$message = sprintf($lang['export_smiles'], "<a href=\"" . append_sid("admin_smilies.$phpEx?export_pack=send", true) . "\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_smileadmin'], "<a href=\"" . append_sid("admin_smilies.$phpEx") . "\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"" . append_sid("index.$phpEx?pane=right") . "\">", "</a>");

	message_die(GENERAL_MESSAGE, $message);

}
else if( isset($_POST['add']) || isset($_GET['add']) )
{
	//
	// Admin has selected to add a smiley.
	//

	$template->set_filenames(array(
		"body" => "admin/smile_edit_body.tpl")
	);

	$filename_list = "";
	foreach ($smiley_images as $smiley_image)
	{
		$filename_list .= '<option value="' . $smiley_image . '">' . $smiley_image . '</option>';
	}

	$s_hidden_fields = '<input type="hidden" name="mode" value="savenew" />';

	$template->assign_vars(array(
		"L_SMILEY_TITLE" => $lang['smiley_title'],
		"L_SMILEY_CONFIG" => $lang['smiley_config'],
		"L_SMILEY_EXPLAIN" => $lang['smile_desc'],
		"L_SMILEY_CODE" => $lang['smiley_code'],
		"L_SMILEY_URL" => $lang['smiley_url'],
		"L_SMILEY_EMOTION" => $lang['smiley_emot'],
		"L_SUBMIT" => $lang['Submit'],
		"L_RESET" => $lang['Reset'],

		"SMILEY_IMG" => $phpbb_root_path . $board_config['smilies_path'] . '/' . $smiley_images[0], 

		"S_SMILEY_ACTION" => append_sid("admin_smilies.$phpEx"), 
		"S_HIDDEN_FIELDS" => $s_hidden_fields, 
		"S_FILENAME_OPTIONS" => $filename_list, 
		"S_SMILEY_BASEDIR" => $phpbb_root_path . $board_config['smilies_path'])
	);

	$template->pparse("body");
}
else if ( $mode != "" )
{
	switch( $mode )
	{
		case 'delete':
			//
			// Admin has selected to delete a smiley.
			//

			$smiley_id = ( !empty($_POST['id']) ) ? $_POST['id'] : $_GET['id'];
			$smiley_id = intval($smiley_id);

			$confirm = isset($_POST['confirm']);

			if( $confirm )
			{
				$sql = "DELETE FROM " . SMILIES_TABLE . "
					WHERE smilies_id = " . $smiley_id;
				$result = $db->sql_query($sql);
				if( !$result )
				{
					message_die(GENERAL_ERROR, "Couldn't delete smiley", "", __LINE__, __FILE__, $sql);
				}

				$message = $lang['smiley_del_success'] . "<br /><br />" . sprintf($lang['Click_return_smileadmin'], "<a href=\"" . append_sid("admin_smilies.$phpEx") . "\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"" . append_sid("index.$phpEx?pane=right") . "\">", "</a>");

				message_die(GENERAL_MESSAGE, $message);
			}
			else
			{
				// Present the confirmation screen to the user
				$template->set_filenames(array(
					'body' => 'admin/confirm_body.tpl')
				);

				$hidden_fields = '<input type="hidden" name="mode" value="delete" /><input type="hidden" name="id" value="' . $smiley_id . '" />';

				$template->assign_vars(array(
					'MESSAGE_TITLE' => $lang['Confirm'],
					'MESSAGE_TEXT' => $lang['Confirm_delete_smiley'],

					'L_YES' => $lang['Yes'],
					'L_NO' => $lang['No'],

					'S_CONFIRM_ACTION' => append_sid("admin_smilies.$phpEx"),
					'S_HIDDEN_FIELDS' => $hidden_fields)
				);
				$template->pparse('body');
			}
			break;

		case 'edit':
			//
			// Admin has selected to edit a smiley.
			//

			$smiley_id = ( !empty($_POST['id']) ) ? $_POST['id'] : $_GET['id'];
			$smiley_id = intval($smiley_id);

			$sql = "SELECT *
				FROM " . SMILIES_TABLE . "
				WHERE smilies_id = " . $smiley_id;
			$result = $db->sql_query($sql);
			if( !$result )
			{
				message_die(GENERAL_ERROR, 'Could not obtain emoticon information', "", __LINE__, __FILE__, $sql);
			}
			$smile_data = $db->sql_fetchrow($result);

	        $filename_list = "";
			for ($i = 0; $i < count($smiley_images); $i++)
			{
				if ($smiley_images[$i] == $smile_data['smile_url'])
				{
					$smiley_selected = 'selected="selected"';
					$smiley_edit_img = $smiley_images[$i];
				}
				else
				{
					$smiley_selected = '';
				}
			 
				$filename_list .= '<option value="' . htmlspecialchars($smiley_images[$i], ENT_QUOTES, 'UTF-8') . '"' . $smiley_selected . '>' . htmlspecialchars($smiley_images[$i], ENT_QUOTES, 'UTF-8') . '</option>';
			}

			$template->set_filenames(array(
				"body" => "admin/smile_edit_body.tpl")
			);

			$s_hidden_fields = '<input type="hidden" name="mode" value="save" /><input type="hidden" name="smile_id" value="' . $smile_data['smilies_id'] . '" />';

			$template->assign_vars(array(
				"SMILEY_CODE" => $smile_data['code'],
				"SMILEY_EMOTICON" => $smile_data['emoticon'],

				"L_SMILEY_TITLE" => $lang['smiley_title'],
				"L_SMILEY_CONFIG" => $lang['smiley_config'],
				"L_SMILEY_EXPLAIN" => $lang['smile_desc'],
				"L_SMILEY_CODE" => $lang['smiley_code'],
				"L_SMILEY_URL" => $lang['smiley_url'],
				"L_SMILEY_EMOTION" => $lang['smiley_emot'],
				"L_SUBMIT" => $lang['Submit'],
				"L_RESET" => $lang['Reset'],

				"SMILEY_IMG" => $phpbb_root_path . $board_config['smilies_path'] . '/' . $smiley_edit_img, 

				"S_SMILEY_ACTION" => append_sid("admin_smilies.$phpEx"),
				"S_HIDDEN_FIELDS" => $s_hidden_fields, 
				"S_FILENAME_OPTIONS" => $filename_list, 
				"S_SMILEY_BASEDIR" => $phpbb_root_path . $board_config['smilies_path'])
			);

			$template->pparse("body");
			break;

		case "save":
			//
			// Admin has submitted changes while editing a smiley.
			//

			//
			// Get the submitted data, being careful to ensure that we only
			// accept the data we are looking for.
			//
			$smile_code = ( isset($_POST['smile_code']) ) ? trim($_POST['smile_code']) : '';
			$smile_url = ( isset($_POST['smile_url']) ) ? trim($_POST['smile_url']) : '';
			$smile_url = phpbb_ltrim(basename($smile_url), "'");
			$smile_emotion = ( isset($_POST['smile_emotion']) ) ? htmlspecialchars(trim($_POST['smile_emotion']), ENT_COMPAT, 'utf-8') : '';
			$smile_id = ( isset($_POST['smile_id']) ) ? intval($_POST['smile_id']) : 0;
			$smile_code = trim($smile_code);
			$smile_url = trim($smile_url);

			// If no code was entered complain ...
			if ($smile_code == '' || $smile_url == '')
			{
				message_die(GENERAL_MESSAGE, $lang['Fields_empty']);
			}

			//
			// Convert < and > to proper htmlentities for parsing.
			//
			$smile_code = str_replace('<', '&lt;', $smile_code);
			$smile_code = str_replace('>', '&gt;', $smile_code);

			//
			// Proceed with updating the smiley table.
			//
			$sql = "UPDATE " . SMILIES_TABLE . "
				SET code = '" . str_replace("\'", "''", $smile_code) . "', smile_url = '" . str_replace("\'", "''", $smile_url) . "', emoticon = '" . str_replace("\'", "''", $smile_emotion) . "'
				WHERE smilies_id = $smile_id";
			if( !($result = $db->sql_query($sql)) )
			{
				message_die(GENERAL_ERROR, "Couldn't update smilies info", "", __LINE__, __FILE__, $sql);
			}

			$message = $lang['smiley_edit_success'] . "<br /><br />" . sprintf($lang['Click_return_smileadmin'], "<a href=\"" . append_sid("admin_smilies.$phpEx") . "\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"" . append_sid("index.$phpEx?pane=right") . "\">", "</a>");

			message_die(GENERAL_MESSAGE, $message);
			break;

		case "savenew":
			//
			// Admin has submitted changes while adding a new smiley.
			//

			//
			// Get the submitted data being careful to ensure the the data
			// we recieve and process is only the data we are looking for.
			//
			$smile_code = ( isset($_POST['smile_code']) ) ? $_POST['smile_code'] : '';
			$smile_url = ( isset($_POST['smile_url']) ) ? $_POST['smile_url'] : '';
			$smile_url = phpbb_ltrim(basename($smile_url), "'");
			$smile_emotion = ( isset($_POST['smile_emotion']) ) ? htmlspecialchars(trim($_POST['smile_emotion']), ENT_COMPAT, 'utf-8') : '';
			$smile_code = trim($smile_code);
			$smile_url = trim($smile_url);

			// If no code was entered complain ...
			if ($smile_code == '' || $smile_url == '')
			{
				message_die(GENERAL_MESSAGE, $lang['Fields_empty']);
			}

			//
			// Convert < and > to proper htmlentities for parsing.
			//
			$smile_code = str_replace('<', '&lt;', $smile_code);
			$smile_code = str_replace('>', '&gt;', $smile_code);

			//
			// Save the data to the smiley table.
			//
			$sql = "INSERT INTO " . SMILIES_TABLE . " (code, smile_url, emoticon)
				VALUES ('" . str_replace("\'", "''", $smile_code) . "', '" . str_replace("\'", "''", $smile_url) . "', '" . str_replace("\'", "''", $smile_emotion) . "')";
			$result = $db->sql_query($sql);
			if( !$result )
			{
				message_die(GENERAL_ERROR, "Couldn't insert new smiley", "", __LINE__, __FILE__, $sql);
			}

			$message = $lang['smiley_add_success'] . "<br /><br />" . sprintf($lang['Click_return_smileadmin'], "<a href=\"" . append_sid("admin_smilies.$phpEx") . "\">", "</a>") . "<br /><br />" . sprintf($lang['Click_return_admin_index'], "<a href=\"" . append_sid("index.$phpEx?pane=right") . "\">", "</a>");

			message_die(GENERAL_MESSAGE, $message);
			break;
	}
}
else
{

	//
	// This is the main display of the page before the admin has selected
	// any options.
	//
	$sql = "SELECT *
		FROM " . SMILIES_TABLE;
	$result = $db->sql_query($sql);
	if( !$result )
	{
		message_die(GENERAL_ERROR, "Couldn't obtain smileys from database", "", __LINE__, __FILE__, $sql);
	}

	$smilies = $db->sql_fetchrowset($result);

	$template->set_filenames(array(
		"body" => "admin/smile_list_body.tpl")
	);

	$template->assign_vars(array(
		"L_ACTION" => $lang['Action'],
		"L_SMILEY_TITLE" => $lang['smiley_title'],
		"L_SMILEY_TEXT" => $lang['smile_desc'],
		"L_DELETE" => $lang['Delete'],
		"L_EDIT" => $lang['Edit'],
		"L_SMILEY_ADD" => $lang['smile_add'],
		"L_CODE" => $lang['Code'],
		"L_EMOT" => $lang['Emotion'],
		"L_SMILE" => $lang['Smile'],
		"L_IMPORT_PACK" => $lang['import_smile_pack'],
		"L_EXPORT_PACK" => $lang['export_smile_pack'],
		
		"S_HIDDEN_FIELDS" => $s_hidden_fields, 
		"S_SMILEY_ACTION" => append_sid("admin_smilies.$phpEx"))
	);

	//
	// Loop throuh the rows of smilies setting block vars for the template.
	//
	foreach ($smilies as $i => $smilie) {
    //
    // Replace htmlentites for < and > with actual character.
    //
    $smilie['code'] = str_replace('&lt;', '<', $smilie['code']);
    $smilie['code'] = str_replace('&gt;', '>', $smilie['code']);
 
    if ($smilie['code'] == '') 
	{
        continue;
    }
 
    $row_color = (!($i % 2)) ? $theme['td_color1'] : $theme['td_color2'];
    $row_class = (!($i % 2)) ? $theme['td_class1'] : $theme['td_class2'];
 
    $template->assign_block_vars("smiles", array(
        "ROW_COLOR" => "#" . $row_color,
        "ROW_CLASS" => $row_class,
 
        "SMILEY_IMG" =>  $phpbb_root_path . $board_config['smilies_path'] . '/' . $smilie['smile_url'], 
        "CODE" => $smilie['code'],
        "EMOT" => $smilie['emoticon'],
 
        "U_SMILEY_EDIT" => append_sid("admin_smilies." . $phpEx . "?mode=edit&amp;id=" . $smilie['smilies_id']), 
        "U_SMILEY_DELETE" => append_sid("admin_smilies." . $phpEx . "?mode=delete&amp;id=" . $smilie['smilies_id']))
    );
}


	//
	// Spit out the page.
	//
	$template->pparse("body");
}

//
// Page Footer
//
include('./page_footer_admin.'.$phpEx);