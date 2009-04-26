<?php
/**
 * phpwsTimeLog
 *
 * See docs/CREDITS for copyright information
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @author      Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
 * @version     $Id: install.php,v 1.4 2006/03/12 04:38:16 blindman1344 Exp $
 */

/* Make sure the user is a deity before running this script */
if (!$_SESSION['OBJ_user']->isDeity()){
    header('location:index.php');
    exit();
}

if (version_compare($GLOBALS['core']->version, '0.10.0') < 0) {
    $content .= 'This module requires a phpWebSite core version of 0.10.0 or greater to install.<br />';
    $content .= 'You are currently using phpWebSite core version ' . $GLOBALS['core']->version . '.<br />';
    return;
}

/* Import installation database and dump result into status variable */
if ($status = $GLOBALS['core']->sqlImport(PHPWS_SOURCE_DIR . 'mod/phpwstimelog/boost/install.sql', TRUE)) {

    if(file_exists(PHPWS_SOURCE_DIR . 'mod/phpwstimelog/boost/phases.txt')) {
        $phases = file(PHPWS_SOURCE_DIR . 'mod/phpwstimelog/boost/phases.txt');
        $i = 1;
        foreach ($phases as $phase) {
            $data['owner'] = 'install';
            $data['editor'] = 'install';
            $data['created'] = time();
            $data['updated'] = time();
            $data['label'] = str_replace("\n", '', $phase);
            $data['ordinal'] = $i++;
            $GLOBALS['core']->sqlInsert($data, 'mod_phpwstimelog_phases');
        }
    }

    $content .= 'All Time Log tables successfully written.<br /><br />';
} else {
    $content .= 'There was a problem writing to the database!<br /><br />';
}

?>