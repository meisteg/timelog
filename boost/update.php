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
 * @version     $Id: update.php,v 1.6 2006/03/19 04:05:51 blindman1344 Exp $
 */

if(!$_SESSION['OBJ_user']->isDeity()) {
    header('location:index.php');
    exit();
}

// Need to do core version check
if(version_compare($GLOBALS['core']->version, '0.10.0') < 0) {
    $content .= 'This module requires a phpWebSite core version of 0.10.0 or greater to install.<br />';
    $content .= 'You are currently using phpWebSite core version ' . $GLOBALS['core']->version . '.<br />';
    return;
}

// Update Language
require_once(PHPWS_SOURCE_DIR . 'mod/language/class/Language.php');
PHPWS_Language::uninstallLanguages('phpwstimelog');
PHPWS_Language::installLanguages('phpwstimelog');

$status = 1;

if(version_compare($currentVersion, '0.2.0') < 0) {
    $sql = 'SELECT id,label FROM mod_phpwstimelog_phases';
    $result = $GLOBALS['core']->getAll($sql, TRUE);
    if(sizeof($result) > 0) {
        foreach($result as $row) {
            $sql = 'UPDATE mod_phpwstimelog_phases SET label="' . str_replace("\n", '', $row['label']) . '" WHERE id=' . $row['id'];
            $GLOBALS['core']->query($sql, TRUE);
        }
    }

    $content .= '<br />phpwsTimeLog Updates for Version 0.2.0<br />';
    $content .= '------------------------------------------<br />';
    $content .= '- Print View and CSV download<br />';
}

if(version_compare($currentVersion, '0.3.0') < 0) {
    // Reinstalling boxes in boost due to added filter box
    PHPWS_Layout::uninstallBoxStyle('phpwstimelog');
    $_SESSION["OBJ_layout"]->installModule('phpwstimelog');

    $content .= '<br />phpwsTimeLog Updates for Version 0.3.0<br />';
    $content .= '------------------------------------------<br />';
    $content .= '- Can filter to show times for a specified time range<br />';
}

if(version_compare($currentVersion, '0.3.1') < 0) {
    $content .= '<br />phpwsTimeLog Updates for Version 0.3.1<br />';
    $content .= '------------------------------------------<br />';
    $content .= '- Now prevents against overwriting previous entries<br />';
    $content .= '- Fixed JavaScript error in add/edit form when invoking Spell Check<br />';
}

if(version_compare($currentVersion, '0.3.2') < 0) {
    $content .= '<br />phpwsTimeLog Updates for Version 0.3.2<br />';
    $content .= '------------------------------------------<br />';
    $content .= '- Now installs correctly on branch sites.<br />';
}

if(version_compare($currentVersion, '0.4.0') < 0) {
    $sql = 'ALTER TABLE mod_phpwstimelog_phases ADD ordinal int NOT NULL default 0 AFTER updated';
    $GLOBALS['core']->query($sql, TRUE);

    $sql = 'SELECT id FROM mod_phpwstimelog_phases';
    $result = $GLOBALS['core']->getAll($sql, TRUE);
    if(sizeof($result) > 0) {
        foreach($result as $row) {
            $sql = 'UPDATE mod_phpwstimelog_phases SET ordinal=id WHERE id=' . $row['id'];
            $GLOBALS['core']->query($sql, TRUE);
        }
    }

    $content .= '<br />phpwsTimeLog Updates for Version 0.4.0<br />';
    $content .= '------------------------------------------<br />';
    $content .= '+ Can now add/edit phases from settings page.<br />';

    $content .= '<br /><em>The templates have changed with this release.  If your site theme
                 overrides the module templates, you may have to modify your theme templates
                 in order for this version to work as expected.</em><br />';
}

if(version_compare($currentVersion, '0.4.1') < 0) {
    $content .= '<br />phpwsTimeLog Updates for Version 0.4.1<br />';
    $content .= '------------------------------------------<br />';
    $content .= '+ Fixed entry saving when site set to 24 hour time.<br />';
    $content .= '+ Support international date formats.<br />';

    $content .= '<br /><em>The templates have changed with this release.  If your site theme
                 overrides the module templates, you may have to modify your theme templates
                 in order for this version to work as expected.</em><br />';
}

?>