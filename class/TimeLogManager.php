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
 * @version     $Id: TimeLogManager.php,v 1.17 2006/03/19 04:54:59 blindman1344 Exp $
 */

require_once PHPWS_SOURCE_DIR . 'core/List.php';
require_once PHPWS_SOURCE_DIR . 'mod/phpwstimelog/class/TimeLogEntry.php';

class PHPWS_TimeLogManager {

    /**
     * Holds reference to list object
     *
     * @var reference
     */
    var $_list = null;

    /**
     * Holds reference to list object for CSV downloads
     *
     * @var reference
     */
    var $_list_csv = null;

    /**
     * Holds reference to TimeLogEntry
     *
     * @var reference
     */
    var $_timelogentry = null;

    /**
     * Contains message string
     *
     * @var string
     */
    var $message = null;

    /**
     * Flag 1/0 to allow anonymous viewing
     *
     * @var smallint
     */
    var $_allow_anon_view;

    /**
     * Flag 1/0 to toggle filter
     *
     * @var bit
     */
    var $_filter = 0;

    /**
     * Filter start time
     *
     * @var int
     */
    var $_filter_start = null;

    /**
     * Filter end time
     *
     * @var int
     */
    var $_filter_end = null;


    /**
     * Constructor
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function PHPWS_TimeLogManager() {
        // Settings
        $result = $GLOBALS['core']->sqlSelect('mod_phpwstimelog_settings');
        $this->_allow_anon_view = $result[0]['allow_anon_view'];
    }// END FUNC PHPWS_TimeLogManager

    /**
     * Menu
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function menu() {
        $addTimeLogEntry = $_SESSION['translate']->it('Add New Entry');
        $listTimeLogEntries = $_SESSION['translate']->it('List Entries');
        $overview = $_SESSION['translate']->it('Overview');
        $settings = $_SESSION['translate']->it('Settings');

        $links = array();
        if ($_SESSION['OBJ_user']->allow_access('phpwstimelog', 'add_entries')) {
            $links[] = '<a href="index.php?module=phpwstimelog&amp;op=add">' . $addTimeLogEntry . '</a>';
        }

        $links[] = '<a href="index.php?module=phpwstimelog">' . $overview . '</a>';
        $links[] = '<a href="index.php?module=phpwstimelog&amp;op=list">' . $listTimeLogEntries . '</a>';

        if ($_SESSION['OBJ_user']->allow_access('phpwstimelog', 'edit_settings')) {
            $links[] = '<a href="index.php?module=phpwstimelog&amp;op=getsettings">' . $settings . '</a>';
        }

        $tags = array();
        $tags['LINKS'] = implode(' | ', $links);

        if (isset($this->message)) {
            $tags['MESSAGE'] = $this->message;
            $this->message = null;
        }

        return PHPWS_Template::processTemplate($tags, 'phpwstimelog', 'menu.tpl');
    }// END FUNC menu

    /**
     * Generates the time log overview
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _main($tpl=NULL) {
        $totals = array();

        $tags = array();
        $tags['OVERVIEW_LABEL'] = $_SESSION['translate']->it('Overview');
        $tags['NAMES_LABEL'] = $_SESSION['translate']->it('Names');
        $tags['TOTAL_LABEL'] = $_SESSION['translate']->it('Total');
        $tags['COLUMNS'] = '';
        $tags['ROWS'] = '';
        $tags['TOTALS'] = '';

        $sql = 'SELECT id,label FROM mod_phpwstimelog_phases ORDER BY ordinal';
        $result = $GLOBALS['core']->query($sql, TRUE);
        if($result) {
            while($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
                $tags['COLUMNS'] .= PHPWS_Template::processTemplate(array('COLUMN_LABEL'=>$row['label']), 'phpwstimelog', 'overview/headercell' . $tpl . '.tpl');
                $totals[$row['id']] = 0;
            }
        }

        $sql = 'SELECT DISTINCT owner FROM ' . $GLOBALS['core']->tbl_prefix . 'mod_phpwstimelog_entries ORDER BY owner';
        $result = $GLOBALS['core']->query($sql);
        if($result) {
            while($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
                $tags_row = array();

                if($GLOBALS['core']->moduleExists('phpwscontacts')) {
                    $sql = "select label from mod_phpwscontacts_contacts where owner='" . $row['owner'] . "' order by mine desc, created asc";
                    $results = $GLOBALS['core']->getCol($sql, TRUE);
                    if (sizeof($results) > 0) {
                        $tags_row['NAME'] = $results[0];
                    }
                    else {
                        $tags_row['NAME'] = $row['owner'];
                    }
                }
                else {
                    $tags_row['NAME'] = $row['owner'];
                }

                if(isset($tpl) && ($tpl == '_csv')) {
                    $tags_row['NAME'] = str_replace(',','',$tags_row['NAME']);
                }

                $tags_row['COLUMNS'] = '';
                $rowTotal = 0;

                $sql = 'SELECT id FROM mod_phpwstimelog_phases ORDER BY ordinal';
                $result2 = $GLOBALS['core']->query($sql, TRUE);
                if($result2) {
                    while($row2 = $result2->fetchRow(DB_FETCHMODE_ASSOC)) {
                        $phaseTotal = 0;
                        $sql = "SELECT delta FROM mod_phpwstimelog_entries WHERE owner='" . $row['owner'] . "' AND phase='" . $row2['id'] . "'";
                        if($this->_filter) { $sql .= ' AND start>=' . $this->_filter_start . ' AND start<=' . $this->_filter_end; }
                        $result3 = $GLOBALS['core']->query($sql, TRUE);
                        if($result3) {
                            while($row3 = $result3->fetchRow(DB_FETCHMODE_ASSOC)) {
                                $phaseTotal += $row3['delta'];
                            }
                        }

                        $rowTotal += $phaseTotal;
                        $totals[$row2['id']] += $phaseTotal;
                        $tags_row['COLUMNS'] .= PHPWS_Template::processTemplate(array('VALUE'=>$this->_timeConvert($phaseTotal)), 'phpwstimelog', 'overview/cell' . $tpl . '.tpl');
                    }
                }

                $tags_row['TOTAL'] = $this->_timeConvert($rowTotal);
                $tags['ROWS'] .= PHPWS_Template::processTemplate($tags_row, 'phpwstimelog', 'overview/row' . $tpl . '.tpl');
            }
        }

        $grandTotal = 0;
        foreach($totals as $total) {
            $tags['TOTALS'] .= PHPWS_Template::processTemplate(array('VALUE'=>$this->_timeConvert($total)), 'phpwstimelog', 'overview/cell' . $tpl . '.tpl');
            $grandTotal += $total;
        }

        $tags['GRAND_TOTAL'] = $this->_timeConvert($grandTotal);

        if(!isset($_REQUEST['op'])) {
            $tags['CSV'] = '<a href="index.php?module=phpwstimelog&amp;op=csv">' . $_SESSION['translate']->it('Download') . '</a>';
            $image = '<img src="./images/print.gif" style="border-width:0" width="22" height="20" alt="' . $_SESSION['translate']->it('Printable Version') . '" />';
            $tags['PRINT_ICON'] = '<a href="index.php?module=phpwstimelog&amp;op=print&amp;lay_quiet=1" onclick="window.open(this.href, \'_blank\'); return false;">'.$image.'</a>';
            $tags['MENU'] = $this->menu();
        }

        if(isset($tpl) && ($tpl == '_csv')) {
            $content = PHPWS_Template::processTemplate($tags, 'phpwstimelog', 'overview/overview_csv.tpl');

            // Write to file
            $filename = 'timelogOverview_' . date('Ymd') . '.csv';
            Header('Content-Disposition: attachment; filename='.$filename);
            Header('Content-Length: '.strlen($content));
            Header('Connection: close');
            Header('Content-Type: text/plain; name='.$filename);
            echo $content;
            exit();
        }
        else {
            return PHPWS_Template::processTemplate($tags, 'phpwstimelog', 'overview/overview.tpl');
        }
    }// END FUNC _main

    /**
     * List All Entries
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _listAll() {
        if(!isset($this->_list)) {
            $this->_list =& new PHPWS_List;
        }

        $listSettings = array('limit'   => 10,
                              'section' => true,
                              'limits'  => array(5,10,20,50),
                              'back'    => '&#60;&#60;',
                              'forward' => '&#62;&#62;',
                              'anchor'  => false);

        $this->_list->setModule('phpwstimelog');
        $this->_list->setClass('PHPWS_TimeLogEntryList');
        $this->_list->setTable('mod_phpwstimelog_entries');
        $this->_list->setDbColumns(array('id', 'phase', 'owner', 'comments', 'delta'));
        $this->_list->setListColumns(array('owner', 'phase', 'actions', 'comments', 'delta'));
        $this->_list->setName('list');
        $this->_list->setOp('op=list');
        $this->_list->anchorOn();
        $this->_list->setPaging($listSettings);
        $this->_list->setOrder('start');
        if($this->_filter) {
            $this->_list->setWhere('start>=' . $this->_filter_start . ' AND start<=' . $this->_filter_end);
        }

        $tags = array();
        $tags['MENU'] = $this->menu();
        $tags['TITLE'] = $_SESSION['translate']->it('Time Log Entries');
        $tags['ACTIONS'] = $_SESSION['translate']->it('Actions');
        $tags['PHASE'] = $_SESSION['translate']->it('Phase');
        $tags['OWNER'] = $_SESSION['translate']->it('Name');
        $tags['DELTA'] = $_SESSION['translate']->it('Delta Time');

        if($GLOBALS['core']->sqlSelect('mod_phpwstimelog_entries')) {
            $tags['CSV'] = '<a href="index.php?module=phpwstimelog&amp;op=list_csv">' . $_SESSION['translate']->it('Download') . '</a>';
        }

        $this->_list->setExtraListTags($tags);

        return $this->_list->getList();
    }// END FUNC _listAll

    /**
     * List All Entries without paging for CSV
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _listAllCSV() {
        if(!isset($this->_list_csv)) {
            $this->_list_csv =& new PHPWS_List;
        }

        $this->_list_csv->setModule('phpwstimelog');
        $this->_list_csv->setClass('PHPWS_TimeLogEntryList');
        $this->_list_csv->setTable('mod_phpwstimelog_entries');
        $this->_list_csv->setDbColumns(array('id', 'phase', 'owner', 'start', 'end', 'interruption', 'comments', 'delta'));
        $this->_list_csv->setListColumns(array('owner', 'phase', 'start', 'end', 'interruption', 'csv_comments', 'delta'));
        $this->_list_csv->setName('list_csv');
        $this->_list_csv->setOp('op=list_csv');
        $this->_list_csv->setOrder('start');
        if($this->_filter) {
            $this->_list_csv->setWhere('start>=' . $this->_filter_start . ' AND start<=' . $this->_filter_end);
        }

        $tags = array();
        $tags['TITLE'] = $_SESSION['translate']->it('Time Log Entries');
        $tags['COMMENTS'] = $_SESSION['translate']->it('Comments');
        $tags['PHASE'] = $_SESSION['translate']->it('Phase');
        $tags['OWNER'] = $_SESSION['translate']->it('Name');
        $tags['DELTA'] = $_SESSION['translate']->it('Delta Time');
        $tags['START'] = $_SESSION['translate']->it('Start');
        $tags['END'] = $_SESSION['translate']->it('End');
        $tags['INTERRUPTION'] = $_SESSION['translate']->it('Interruption');
        $this->_list_csv->setExtraListTags($tags);

        $content = $this->_list_csv->getList();

        // Write to file
        $filename = 'timelogEntries_' . date('Ymd') . '.csv';
        Header('Content-Disposition: attachment; filename='.$filename);
        Header('Content-Length: '.strlen($content));
        Header('Connection: close');
        Header('Content-Type: text/plain; name='.$filename);
        echo $content;
        exit();
    }// END FUNC _listAllCSV

    /**
     * Displays the filter box
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _filter() {
        $GLOBALS['CNT_timelog_filter']['title'] = $_SESSION['translate']->it('Time Log Filter');
        require_once (PHPWS_SOURCE_DIR . 'core/EZform.php');

        if(!$this->_filter) {
            require_once (PHPWS_SOURCE_DIR . 'core/WizardBag.php');

            $form = new EZForm();
            $form->add('module', 'hidden', 'phpwstimelog');
            $form->add('op', 'hidden', 'filter');
            if(isset($_REQUEST['op'])) $form->add('old_op', 'hidden', $_REQUEST['op']);
            else $form->add('old_op', 'hidden', '');

            $form->dateForm('filter_start', $this->_filter_start, 2002, date('Y')+1);
            $form->setId('filter_start_MONTH');
            $form->setId('filter_start_DAY');
            $form->setId('filter_start_YEAR');
            $form->dateForm('filter_end', $this->_filter_end, 2002, date('Y')+1);
            $form->setId('filter_end_MONTH');
            $form->setId('filter_end_DAY');
            $form->setId('filter_end_YEAR');

            $form->add('filter_set', 'submit', $_SESSION['translate']->it('Set Filter'));

            $tags = $this->dateReorder($this->dateReorder($form->getTemplate(), 'FILTER_START'), 'FILTER_END');
            $tags['START_LABEL'] = $_SESSION['translate']->it('Start');
            $tags['END_LABEL'] = $_SESSION['translate']->it('End');

            $tags['START_POPCAL'] = PHPWS_WizardBag::js_insert('popcalendar', NULL, NULL, FALSE,
                                    array('month'=>'filter_start_MONTH', 'day'=>'filter_start_DAY', 'year'=>'filter_start_YEAR'));

            $tags['END_POPCAL'] = PHPWS_WizardBag::js_insert('popcalendar', NULL, NULL, FALSE,
                                  array('month'=>'filter_end_MONTH', 'day'=>'filter_end_DAY', 'year'=>'filter_end_YEAR'));

            $GLOBALS['CNT_timelog_filter']['content'] = PHPWS_Template::processTemplate($tags, 'phpwstimelog', 'filter_set.tpl');
        }
        else {
            $form = new EZForm();
            $form->add('module', 'hidden', 'phpwstimelog');
            $form->add('op', 'hidden', 'filter');
            if (isset($_REQUEST['op'])) {
                $form->add('old_op', 'hidden', $_REQUEST['op']);
            }
            else {
                $form->add('old_op', 'hidden', 'overview');
            }
            $form->add('filter_remove', 'submit', $_SESSION['translate']->it('Remove Filter'));

            $tags = $form->getTemplate();
            $tags['START_LABEL'] = $_SESSION['translate']->it('Start');
            $tags['END_LABEL'] = $_SESSION['translate']->it('End');
            $tags['MESSAGE'] = $_SESSION['translate']->it('Filter Applied');
            $tags['FILTER_START'] = date(PHPWS_DATE_FORMAT, $this->_filter_start);
            $tags['FILTER_END'] = date(PHPWS_DATE_FORMAT, $this->_filter_end);

            $GLOBALS['CNT_timelog_filter']['content'] = PHPWS_Template::processTemplate($tags, 'phpwstimelog', 'filter_applied.tpl');
        }
    }// END FUNC _filter

    /**
     * Toggle filter on/off
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _filter_toggle() {
        if($this->_filter) {
            $this->_filter = 0;
        }
        else {
            $this->_filter = 1;

            require_once (PHPWS_SOURCE_DIR . 'core/Text.php');

            if(is_numeric($_REQUEST['filter_start_MONTH']) && is_numeric($_REQUEST['filter_start_DAY']) && is_numeric($_REQUEST['filter_start_YEAR'])) {
                $month = PHPWS_Text::parseInput($_REQUEST['filter_start_MONTH']);
                $day = PHPWS_Text::parseInput($_REQUEST['filter_start_DAY']);
                $year = PHPWS_Text::parseInput($_REQUEST['filter_start_YEAR']);

                $this->_filter_start = mktime(0, 0, 0, $month, $day, $year);
            }

            if(is_numeric($_REQUEST['filter_end_MONTH']) && is_numeric($_REQUEST['filter_end_DAY']) && is_numeric($_REQUEST['filter_end_YEAR'])) {
                $month = PHPWS_Text::parseInput($_REQUEST['filter_end_MONTH']);
                $day = PHPWS_Text::parseInput($_REQUEST['filter_end_DAY']) + 1;  // Add extra day for timestamp
                $year = PHPWS_Text::parseInput($_REQUEST['filter_end_YEAR']);

                $this->_filter_end = mktime(0, 0, 0, $month, $day, $year) - 1;  // Substract one second
            }
        }

        if($_REQUEST['old_op'] == 'list')
            $_REQUEST['op'] = 'list';
        else
            $_REQUEST['op'] = NULL;

        $this->action();
    }// END FUNC _filter_toggle

    /**
     * Add Entry
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _add() {
        $this->_timelogentry =& new PHPWS_TimeLogEntry;
        $_REQUEST['timelog_op'] = 'edit';

        $this->_timelogentry->action();
    }// END FUNC _add

    /**
     * Convert time to output format
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _timeConvert($seconds) {
        $hourVal = (int)($seconds/3600);
        if($hourVal < 10) $hourVal = '0' . $hourVal;

        $minVal = ($seconds%3600)/60;
        if($minVal < 10) $minVal = '0' . $minVal;

        return $hourVal . ':' . $minVal;
    }// END FUNC _timeConvert

    /**
     * Reorder the date for the add/edit form based on core date settings
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function dateReorder($tags, $name) {
        $date_format = PHPWS_DATE_FORMAT;
        $done = FALSE;

        for ($i = 0; ($i < strlen($date_format)) && !$done; $i++) {
            // Month-Day-Year
            if (($date_format[$i] == 'F') || ($date_format[$i] == 'm') || ($date_format[$i] == 'M') || ($date_format[$i] == 'n')) {
                $tags[$name . '_DATE1'] = $tags[$name . '_MONTH'];
                $tags[$name . '_DATE2'] = $tags[$name . '_DAY'];
                $tags[$name . '_DATE3'] = $tags[$name . '_YEAR'];
                $done = TRUE;
            }
            // Day-Month-Year
            else if (($date_format[$i] == 'd') || ($date_format[$i] == 'j')) {
                $tags[$name . '_DATE1'] = $tags[$name . '_DAY'];
                $tags[$name . '_DATE2'] = $tags[$name . '_MONTH'];
                $tags[$name . '_DATE3'] = $tags[$name . '_YEAR'];
                $done = TRUE;
            }
            // Year-Month-Day
            else if (($date_format[$i] == 'y') || ($date_format[$i] == 'Y') || ($date_format[$i] == 'o')) {
                $tags[$name . '_DATE1'] = $tags[$name . '_YEAR'];
                $tags[$name . '_DATE2'] = $tags[$name . '_MONTH'];
                $tags[$name . '_DATE3'] = $tags[$name . '_DAY'];
                $done = TRUE;
            }
        }

        // Default to Month-Day-Year if order could not be determined from date_format
        if (!$done) {
            $tags[$name . '_DATE1'] = $tags[$name . '_MONTH'];
            $tags[$name . '_DATE2'] = $tags[$name . '_DAY'];
            $tags[$name . '_DATE3'] = $tags[$name . '_YEAR'];
        }

        unset($tags[$name . '_MONTH'], $tags[$name . '_DAY'], $tags[$name . '_YEAR']);
        return $tags;
    }// END FUNC dateReorder

    /**
     * Get settings for editing
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _editSettings() {
        if (!$_SESSION['OBJ_user']->allow_access('phpwstimelog', 'edit_settings')) {
            return $_SESSION['translate']->it('Access to edit settings was denied.');
        }

        require_once PHPWS_SOURCE_DIR . 'mod/phpwstimelog/class/TimeLogPhase.php';

        if (isset($_POST['op']) && ($_POST['op'] == 'savesettings')) {
            $message = $this->_saveSettings();
        }

        $tabs = 1;
        $form = new EZform();

        $form->add('allow_anon_view', 'checkbox');
        $form->setMatch('allow_anon_view', $this->_allow_anon_view);
        $form->setTab('allow_anon_view', $tabs);
        $tabs++;

        $form->add('save', 'submit', $_SESSION['translate']->it('Save'));
        $form->setTab('save', $tabs);

        $form->add('module', 'hidden', 'phpwstimelog');
        $form->add('op', 'hidden', 'savesettings');

        $tags = $form->getTemplate();
        $tags['ALLOW_ANON_VIEW_LABEL'] = $_SESSION['translate']->it('Allow Anonymous Viewing');
        $tags['SETTINGS_LABEL'] = $_SESSION['translate']->it('Settings');
        if (isset($message)) {
            $tags['SETTINGS_MESSAGE'] = $message;
        }
        $tags['MENU'] = $this->menu();

        $phase_list =& new PHPWS_List;
        $phase_list->setModule('phpwstimelog');
        $phase_list->setClass('PHPWS_TimeLogPhase');
        $phase_list->setTable('mod_phpwstimelog_phases');
        $phase_list->setDbColumns(array('id', 'label', 'updated', 'ordinal'));
        $phase_list->setListColumns(array('label', 'updated', 'ordinal', 'actions'));
        $phase_list->setName('phases');
        $phase_list->setOp('op=getsettings');
        $phase_list->setOrder('ordinal');

        $list_tags = array();
        $list_tags['TITLE'] = $_SESSION['translate']->it('Phases');
        $list_tags['ADD_PHASE'] = '<a href="./index.php?module=phpwstimelog&amp;phase_op=edit">' . $_SESSION['translate']->it('Add New Phase') . '</a>';
        $list_tags['LABEL_LABEL'] = $_SESSION['translate']->it('Phase');
        $list_tags['UPDATED_LABEL'] = $_SESSION['translate']->it('Updated');
        $list_tags['ORDINAL_LABEL'] = $_SESSION['translate']->it('Order');
        $list_tags['ACTIONS_LABEL'] = $_SESSION['translate']->it('Actions');
        $phase_list->setExtraListTags($list_tags);

        $tags['PHASES'] = $phase_list->getList();

        return PHPWS_Template::processTemplate($tags, 'phpwstimelog', 'edit_settings.tpl');
    }// END FUNC _editSettings

    /**
     * Save new settings
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _saveSettings() {
        if(isset($_REQUEST['allow_anon_view']))
            $this->_allow_anon_view = 1;
        else
            $this->_allow_anon_view = 0;

        $data = array();
        $data['allow_anon_view'] = $this->_allow_anon_view;

        if($GLOBALS['core']->sqlUpdate($data, 'mod_phpwstimelog_settings')) {
            return $_SESSION['translate']->it('Your settings have been successfully saved.');
        } else {
            return $_SESSION['translate']->it('There was an error saving the settings.');
        }
    }// END FUNC _saveSettings

    /**
     * Used by search module
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function search($where) {
        $array = array();
        if(!$this->_allow_anon_view && !$_SESSION['OBJ_user']->username) {
            $GLOBALS['CNT_timelog']['title'] = $_SESSION['translate']->it('Anonymous Viewing Denied');
            $GLOBALS['CNT_timelog']['content'] = $_SESSION['translate']->it('Anonymous viewing of the time log has been disabled.');
        } else {
            $sql = 'SELECT id,owner,delta,start FROM mod_phpwstimelog_entries ' . $where;
            $result = $GLOBALS['core']->query($sql, TRUE);

            if($result) {
                while($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
                    if($GLOBALS['core']->moduleExists('phpwscontacts')) {
                        $sql = "select label from mod_phpwscontacts_contacts where owner='" . $row['owner'] . "' order by mine desc, created asc";
                        $results = $GLOBALS['core']->getCol($sql, TRUE);
                        if (sizeof($results) > 0) {
                            $row['owner'] = $results[0];
                        }
                    }

                    $array[$row['id']] = $row['owner'] . ' worked ' . $this->_timeConvert($row['delta']) . ' on ' . date(PHPWS_DATE_FORMAT, $row['start']);
                }
            }
        }

        return $array;
    }// END FUNC search

    /**
     * Action
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function action() {
        if(!$this->_allow_anon_view && !$_SESSION['OBJ_user']->username) {
            $GLOBALS['CNT_timelog']['title'] = $_SESSION['translate']->it('Anonymous Viewing Denied');
            $GLOBALS['CNT_timelog']['content'] = $_SESSION['translate']->it('Anonymous viewing of the time log has been disabled.');
        } else {
            $content = null;

            if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
                $this->_timelogentry =& new PHPWS_TimeLogEntry($_REQUEST['id']);

                if(!isset($_REQUEST['op']) && !isset($_REQUEST['timelog_op'])) {
                    $_REQUEST['timelog_op'] = 'view';
                }
            }

            if(isset($_REQUEST['timelog_op']) && isset($this->_timelogentry)) {
                $this->_timelogentry->action();
                return;
            }

            if(isset($_REQUEST['phase_op'])) {
                require_once PHPWS_SOURCE_DIR . 'mod/phpwstimelog/class/TimeLogPhase.php';
                if (isset($_REQUEST['phase_id']) && is_numeric($_REQUEST['phase_id'])) {
                    $phase_obj =& new PHPWS_TimeLogPhase($_REQUEST['phase_id']);
                }
                else {
                    $phase_obj =& new PHPWS_TimeLogPhase();
                }
                $phase_obj->action();
                return;
            }

            switch(@$_REQUEST['op']) {
                case 'add':
                $this->_add();
                break;

                case 'list':
                $content .= $this->_listAll();
                $this->_filter();
                break;

                case 'list_csv':
                $this->_listAllCSV();
                break;

                case 'getsettings':
                case 'savesettings':
                $content .= $this->_editSettings();
                break;

                case 'csv':
                $this->_main('_csv');
                break;

                case 'print':
                echo $this->_main();
                break;

                case 'filter':
                $this->_filter_toggle();
                break;

                default:
                $content .= $this->_main();
                $this->_filter();
            }

            if (isset($content)) {
                $GLOBALS['CNT_timelog']['content'] .= $content;
            }
        }
    }// END FUNC action
}

?>