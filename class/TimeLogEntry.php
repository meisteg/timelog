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
 * @version     $Id: TimeLogEntry.php,v 1.17 2006/03/19 04:54:59 blindman1344 Exp $
 */

require_once (PHPWS_SOURCE_DIR . 'core/Item.php');

class PHPWS_TimeLogEntry extends PHPWS_Item {

    /**
     * The phase of this time entry
     *
     * @var int
     */
    var $_phase = NULL;

    /**
     * The start time of this time entry
     *
     * @var int
     */
    var $_start = NULL;

    /**
     * The end time of this time entry
     *
     * @var int
     */
    var $_end = NULL;

    /**
     * The interruption time of this time entry
     *
     * @var int
     */
    var $_interruption = NULL;

    /**
     * The delta time of this time entry
     *
     * @var int
     */
    var $_delta = NULL;

    /**
     * Stores the comments for this entry
     *
     * @var string
     */
    var $_comments = NULL;

    /**
     * Constructor
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function PHPWS_TimeLogEntry($id = NULL) {
        $this->setTable('mod_phpwstimelog_entries');
        $this->addExclude(array('_hidden', '_approved', '_label'));

        if (isset($id)) {
            $this->setId($id);
            $this->init();
        }
    }// END FUNC PHPWS_TimeLogEntry


    /**
     * Set Comments
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function setComments($comments) {
        require_once (PHPWS_SOURCE_DIR . 'core/Text.php');

        if (is_string($comments)) {
            if (strlen($comments) > 0) {
                $this->_comments = PHPWS_Text::parseInput($comments);
            } else {
                $this->_comments = NULL;
            }
            return TRUE;
        } else {
            $message = $_SESSION['translate']->it('Comments must be a string!');
            return new PHPWS_Error('phpwstimelog', 'PHPWS_TimeLogEntry::save', $message);
        }
    }// END FUNC setComments

    /**
     * Get Comments
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function getComments() {
        require_once (PHPWS_SOURCE_DIR . 'core/Text.php');

        if (isset($this->_comments) && strlen($this->_comments) > 0) {
            return PHPWS_Text::parseOutput($this->_comments);
        } else {
            return NULL;
        }
    }// END FUNC getComments

    /**
     * Set Phase
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function setPhase($phase) {
        require_once (PHPWS_SOURCE_DIR . 'core/Text.php');

        if(is_numeric($phase)) {
            $this->_phase = PHPWS_Text::parseInput($phase);
            return TRUE;
        } else {
            $message = $_SESSION['translate']->it('Problem setting phase!');
            return new PHPWS_Error('phpwstimelog', 'PHPWS_TimeLogEntry::save', $message);
        }
    }// END FUNC setPhase

    /**
     * Get Phase
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function getPhase() {
        if(isset($this->_phase) && $this->_phase > 0) {
            $sql = 'SELECT label FROM mod_phpwstimelog_phases WHERE id=' . $this->_phase;
            $result = $GLOBALS['core']->query($sql, TRUE);
            if($result) {
                $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
                return $row['label'];
            }
        } else {
            return 'N/A';
        }
    }// END FUNC getPhase

    /**
     * Set Time
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function setTime(&$var, $month, $day, $year, $hour, $min, $ampm) {
        require_once (PHPWS_SOURCE_DIR . 'core/Text.php');

        if(is_numeric($month) && is_numeric($day) && is_numeric($year) && is_numeric($hour) && is_numeric($min)) {
            $month = PHPWS_Text::parseInput($month);
            $day = PHPWS_Text::parseInput($day);
            $year = PHPWS_Text::parseInput($year);
            $hour = PHPWS_Text::parseInput($hour);
            $min = PHPWS_Text::parseInput($min);

            if (($ampm != NULL) && is_numeric($ampm)) {
                $ampm = PHPWS_Text::parseInput($ampm);

                if(($ampm == 1) && ($hour != 12)) {
                    $hour += 12;
                }

                if(($ampm == 0) && ($hour == 12)) {
                    $hour = 0;
                }
            }

            $var = mktime($hour, $min, 0, $month, $day, $year);
            return TRUE;
        }

        $message = $_SESSION['translate']->it('Problem setting time!');
        return new PHPWS_Error('phpwstimelog', 'PHPWS_TimeLogEntry::save', $message);
    }// END FUNC setTime

    /**
     * Get Start Time
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function getStart() {
        if($this->_start)
            return date(PHPWS_DATE_FORMAT . ' ' . PHPWS_TIME_FORMAT, $this->_start);
        else
            return NULL;
    }// END FUNC getStart

    /**
     * Get End Time
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function getEnd() {
        if($this->_end)
            return date(PHPWS_DATE_FORMAT . ' ' . PHPWS_TIME_FORMAT, $this->_end);
        else
            return NULL;
    }// END FUNC getEnd

    /**
     * Set Interruption Time
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function setInterruption($hour, $min) {
        require_once (PHPWS_SOURCE_DIR . 'core/Text.php');

        if(is_numeric($hour) && is_numeric($min)) {
            $hour = PHPWS_Text::parseInput($hour);
            $min = PHPWS_Text::parseInput($min);

            $this->_interruption = $hour*3600 + $min*60;
            return TRUE;
        } else {
            $message = $_SESSION['translate']->it('Problem setting interruption time!');
            return new PHPWS_Error('phpwstimelog', 'PHPWS_TimeLogEntry::save', $message);
        }
    }// END FUNC setInterruption

    /**
     * Get Interruption Time Hours
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function getInterruptionHours() {
        if($this->_interruption) {
            $retVal = (int)($this->_interruption/3600);
            if($retVal < 10) return '0' . $retVal;
            else return $retVal;
        }
        else {
            return NULL;
        }
    }// END FUNC getInterruptionHours

    /**
     * Get Interruption Time Minutes
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function getInterruptionMins() {
        if($this->_interruption) {
            $retVal = ($this->_interruption%3600)/60;
            if($retVal < 10) return '0' . $retVal;
            else return $retVal;
        }
        else {
            return NULL;
        }
    }// END FUNC getInterruptionMins

    /**
     * Set Delta Time
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function setDelta() {
        $deltaTime = $this->_end - $this->_start - $this->_interruption;

        if($deltaTime >= 0) {
            $this->_delta = $deltaTime;
            return TRUE;
        } else {
            $message = $_SESSION['translate']->it('Delta time became negative!  Check your values.');
            return new PHPWS_Error('phpwstimelog', 'PHPWS_TimeLogEntry::save', $message);
        }
    }// END FUNC setInterruption

    /**
     * Get Delta Time
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function getDelta() {
        if($this->_delta) {
            $hourVal = (int)($this->_delta/3600);
            if($hourVal < 10) $hourVal = '0' . $hourVal;

            $minVal = ($this->_delta%3600)/60;
            if($minVal < 10) $minVal = '0' . $minVal;

            return $hourVal . ':' . $minVal;
        }
        else {
            return NULL;
        }
    }// END FUNC getDelta

    /**
     * View
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _view() {
        $tags = array();
        if ($_REQUEST['timelog_op'] != 'delete') {
            $tags['MENU'] = $_SESSION['PHPWS_TimeLogManager']->menu();
        }
        $tags['PHASE'] = $this->getPhase();
        $tags['START'] = $this->getStart();
        $tags['END'] = $this->getEnd();
        $tags['INTERRUPTION'] = $this->getInterruptionHours() . ':' . $this->getInterruptionMins();
        $tags['DELTA'] = $this->getDelta();
        $tags['NAME'] = $this->getOwner();

        if($GLOBALS['core']->moduleExists('phpwscontacts')) {
            $sql = "select label from mod_phpwscontacts_contacts where owner='" . $this->getOwner() . "' order by mine desc, created asc";
            $results = $GLOBALS['core']->getCol($sql, TRUE);
            if (sizeof($results) > 0) {
                $tags['NAME'] = $results[0];
            }
        }

        if($tags['COMMENTS'] = $this->getComments()) {
            $tags['COMMENTS_LABEL'] = $_SESSION['translate']->it('Comments');
        }

        $tags['START_LABEL'] = $_SESSION['translate']->it('Start');
        $tags['END_LABEL'] = $_SESSION['translate']->it('End');

        if($tags['INTERRUPTION'] != ':') $tags['INTERRUPTION_LABEL'] = $_SESSION['translate']->it('Interruption');
        else $tags['INTERRUPTION'] = NULL;

        $tags['PHASE_LABEL'] = $_SESSION['translate']->it('Phase');
        $tags['DELTA_LABEL'] = $_SESSION['translate']->it('Delta');
        $tags['NAME_LABEL'] = $_SESSION['translate']->it('Name');

        if(($_SESSION['OBJ_user']->username == $this->getOwner()) || $_SESSION['OBJ_user']->allow_access('phpwstimelog', 'edit_entries')) {
            $tags['EDIT'] = "<a href=\"./index.php?module=phpwstimelog&amp;timelog_op=edit&amp;id={$this->_id}\">" . $_SESSION['translate']->it('Edit') . '</a>';
        }

        return PHPWS_Template::processTemplate($tags, 'phpwstimelog', 'view.tpl');
    }// END FUNC _view

    /**
     * Edit Entry
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _edit() {
        if (!$_SESSION['OBJ_user']->allow_access('phpwstimelog', 'add_entries') && !$_SESSION['OBJ_user']->allow_access('phpwstimelog', 'edit_entries')) {
            $message = $_SESSION['translate']->it('Access to edit was denied due to lack of proper permissions.');
            $error = new PHPWS_Error('phpwstimelog', 'PHPWS_TimeLogEntry::_edit()', $message, 'exit', 1);
            $error->message();
            return FALSE;
        }

        require_once (PHPWS_SOURCE_DIR . 'core/EZform.php');
        require_once (PHPWS_SOURCE_DIR . 'core/WizardBag.php');

        // Get array of phases
        $phases = array();
        $sql = 'SELECT id,label FROM mod_phpwstimelog_phases ORDER BY ordinal';
        $result = $GLOBALS['core']->query($sql, TRUE);
        if($result) {
            while($row = $result->fetchRow(DB_FETCHMODE_ASSOC))
                $phases[$row['id']] = $row['label'];
        }

        $form = new EZForm();
        $form->add('module', 'hidden', 'phpwstimelog');
        $form->add('timelog_op', 'hidden', 'save');

        $id = $this->getId();
        if (isset($id)) {
            $form->add('timelog_id', 'hidden', $id);
        }

        $form->add('comments', 'textarea', $this->_comments);
        $form->setId('comments');
        $form->setCols('comments', 50);

        $form->add('phase', 'select', $phases);
        $form->setMatch('phase', $this->_phase);

        $form->dateForm('start', $this->_start, 2002, date('Y')+1);
        $form->setId('start_MONTH');
        $form->setId('start_DAY');
        $form->setId('start_YEAR');
        $form->timeForm('start', $this->_start, 5);

        $form->dateForm('end', $this->_end, 2002, date('Y')+1);
        $form->setId('end_MONTH');
        $form->setId('end_DAY');
        $form->setId('end_YEAR');
        $form->timeForm('end', $this->_end, 5);

        $intHours = array('00'=>'00',
                          '01'=>'01',
                          '02'=>'02',
                          '03'=>'03',
                          '04'=>'04',
                          '05'=>'05',
                          '06'=>'06',
                          '07'=>'07',
                          '08'=>'08',
                          '09'=>'09',
                          '10'=>'10',
                          '11'=>'11',
                          '12'=>'12');
        $intMins = array('00'=>'00',
                         '05'=>'05',
                         '10'=>'10',
                         '15'=>'15',
                         '20'=>'20',
                         '25'=>'25',
                         '30'=>'30',
                         '35'=>'35',
                         '40'=>'40',
                         '45'=>'45',
                         '50'=>'50',
                         '55'=>'55');
        $form->add('interruption_HOUR', 'select', $intHours);
        $form->setMatch('interruption_HOUR', $this->getInterruptionHours());
        $form->add('interruption_MINUTE', 'select', $intMins);
        $form->setMatch('interruption_MINUTE', $this->getInterruptionMins());

        $form->add('save', 'submit', $_SESSION['translate']->it('Save'));
        $form->add('reset', 'reset', $_SESSION['translate']->it('Reset'));

        $tags = PHPWS_TimeLogManager::dateReorder(PHPWS_TimeLogManager::dateReorder($form->getTemplate(), 'START'), 'END');
        $tags['MENU'] = $_SESSION['PHPWS_TimeLogManager']->menu();

        if (isset($id)) {
            $tags['TITLE'] = $_SESSION['translate']->it('Edit Time Log Entry');
        } else {
            $tags['TITLE'] = $_SESSION['translate']->it('Add Time Log Entry');
        }

        $tags['COMMENTS_LABEL'] = $_SESSION['translate']->it('Comments');
        $tags['COMMENTS_WYSIWYG'] = PHPWS_WizardBag::js_insert('wysiwyg', 'TimeLogEntry_edit', 'comments');
        $tags['START_LABEL'] = $_SESSION['translate']->it('Start');
        $tags['END_LABEL'] = $_SESSION['translate']->it('End');
        $tags['INTERRUPTION_LABEL'] = $_SESSION['translate']->it('Interruption');
        $tags['PHASE_LABEL'] = $_SESSION['translate']->it('Phase');

        $tags['START_POPCAL'] = PHPWS_WizardBag::js_insert('popcalendar', NULL, NULL, FALSE,
                                    array('month'=>'start_MONTH', 'day'=>'start_DAY', 'year'=>'start_YEAR'));

        $tags['END_POPCAL'] = PHPWS_WizardBag::js_insert('popcalendar', NULL, NULL, FALSE,
                                    array('month'=>'end_MONTH', 'day'=>'end_DAY', 'year'=>'end_YEAR'));

        return PHPWS_Template::processTemplate($tags, 'phpwstimelog', 'edit.tpl');
    }// END FUNC _edit

    /**
     * Save Entry
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _save() {
        require_once (PHPWS_SOURCE_DIR . 'core/Error.php');

        if (!$_SESSION['OBJ_user']->allow_access('phpwstimelog', 'add_entries') && !$_SESSION['OBJ_user']->allow_access('phpwstimelog', 'edit_entries')) {
            $message = $_SESSION['translate']->it('Access to save was denied due to lack of proper permissions.');
            $error = new PHPWS_Error('phpwstimelog', 'PHPWS_TimeLogEntry::_save()', $message, 'exit', 1);
            $error->message();
            return FALSE;
        }

        // This will prevent overwriting other entries if user has multiple tabs/windows open
        if (isset($_POST['timelog_id'])) {
            $this->setId($_POST['timelog_id']);
        }
        else {
            $this->_id = NULL;
        }

        $error = $this->setComments($_POST['comments']);
        if (PHPWS_Error::isError($error)) {
            $error->message('CNT_timelog');
            return $this->_edit();
        }

        $error = $this->setPhase($_POST['phase']);
        if (PHPWS_Error::isError($error)) {
            $error->message('CNT_timelog');
            return $this->_edit();
        }

        $error = $this->setTime($this->_start, $_POST['start_MONTH'], $_POST['start_DAY'], $_POST['start_YEAR'],
                                $_POST['start_HOUR'], $_POST['start_MINUTE'], @$_POST['start_AMPM']);
        if (PHPWS_Error::isError($error)) {
            $error->message('CNT_timelog');
            return $this->_edit();
        }

        $error = $this->setTime($this->_end, $_POST['end_MONTH'], $_POST['end_DAY'], $_POST['end_YEAR'],
                                $_POST['end_HOUR'], $_POST['end_MINUTE'], @$_POST['end_AMPM']);
        if (PHPWS_Error::isError($error)) {
            $error->message('CNT_timelog');
            return $this->_edit();
        }

        $error = $this->setInterruption($_POST['interruption_HOUR'], $_POST['interruption_MINUTE']);
        if (PHPWS_Error::isError($error)) {
            $error->message('CNT_timelog');
            return $this->_edit();
        }

        $error = $this->setDelta();
        if (PHPWS_Error::isError($error)) {
            $error->message('CNT_timelog');
            return $this->_edit();
        }

        $error = $this->commit();

        $_SESSION['PHPWS_TimeLogManager']->message = $_SESSION['translate']->it('Time Log Entry Saved!');

        $_REQUEST['id'] = null;
        $_REQUEST['timelog_op'] = null;

        $_SESSION['PHPWS_TimeLogManager']->action();
    }// END FUNC _save

    /**
     * Delete Entry
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _delete() {
        if (!$_SESSION['OBJ_user']->allow_access('phpwstimelog', 'delete_entries')) {
            $message = $_SESSION['translate']->it('Access to delete was denied due to lack of proper permissions.');
            $error = new PHPWS_Error('phpwstimelog', 'PHPWS_TimeLogEntry::_delete()', $message, 'exit', 1);
            $error->message();
            return FALSE;
        }

        if (isset($_REQUEST['yes'])) {
            $this->kill();
            $_SESSION['PHPWS_TimeLogManager']->message = $_SESSION['translate']->it('Time Log Entry Deleted!');

            $_REQUEST['id'] = null;
            $_REQUEST['timelog_op'] = null;

            $_SESSION['PHPWS_TimeLogManager']->action();
        } else if (isset($_REQUEST['no'])) {
            $_SESSION['PHPWS_TimeLogManager']->message = $_SESSION['translate']->it('Time Log entry was not deleted!');

            $_REQUEST['id'] = null;
            $_REQUEST['timelog_op'] = null;

            $_SESSION['PHPWS_TimeLogManager']->action();
        } else {
            $tags = array();
            $tags['MENU'] = $_SESSION['PHPWS_TimeLogManager']->menu();
            $tags['MESSAGE'] = $_SESSION['translate']->it('Are you sure you want to delete this time log entry?');

            $tags['YES'] = '<a href="index.php?module=phpwstimelog&amp;timelog_op=delete&amp;yes=1">' .
            $_SESSION['translate']->it('Yes') . '</a>';

            $tags['NO'] = '<a href="index.php?module=phpwstimelog&amp;timelog_op=delete&amp;no=1">' .
            $_SESSION['translate']->it('No') .'</a>';

            $tags['TIMELOGENTRY'] = $this->_view();

            return PHPWS_Template::processTemplate($tags, 'phpwstimelog', 'confirm.tpl');
        }
    }// END FUNC _delete


    /**
     * Action
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function action() {
        $content = NULL;

        switch($_REQUEST['timelog_op']) {
            case 'edit':
            $content .= $this->_edit();
            break;

            case 'save':
            $content .= $this->_save();
            break;

            case 'delete':
            $content .= $this->_delete();
            break;

            default:
            $content .= $this->_view();
        }

        if (isset($content)) {
            $GLOBALS['CNT_timelog']['content'] .= $content;
        }
    }// END FUNC action
}


class PHPWS_TimeLogEntryList extends PHPWS_TimeLogEntry {

    function PHPWS_TimeLogEntryList($vars) {
        /* Function provided by PHPWS_Item */
        $this->setVars($vars);
    }

    function getListPhase() {
        return $this->getPhase();
    }

    function getListComments() {
        return $this->getComments();
    }

    function getListCsv_comments() {
        require_once (PHPWS_SOURCE_DIR . 'core/Text.php');

        if (isset($this->_comments) && strlen($this->_comments) > 0) {
            return PHPWS_Text::parseInput(str_replace(',','',str_replace(array("\r\n","\n","\r"),' ',$this->_comments)),"none");
        } else {
            return NULL;
        }
    }

    function getListOwner() {
        if($GLOBALS['core']->moduleExists('phpwscontacts')) {
            $sql = "select label from mod_phpwscontacts_contacts where owner='" . $this->getOwner() . "' order by mine desc, created asc";
            $results = $GLOBALS['core']->getCol($sql, TRUE);
            if (sizeof($results) > 0) {
                if($_REQUEST['op'] == 'list_csv') { return str_replace(',','',$results[0]); }
                else { return $results[0]; }
            }
        }

        /* Function provided by PHPWS_Item */
        return $this->getOwner();
    }

    function getListDelta() {
        return $this->getDelta();
    }

    function getListStart() {
        return $this->getStart();
    }

    function getListEnd() {
        return $this->getEnd();
    }

    function getListInterruption() {
        $retVal = $this->getInterruptionHours() . ':' . $this->getInterruptionMins();
        if($retVal == ':') { $retVal = '00:00'; }
        return $retVal;
    }

    function getListActions() {
        $actions = array();

        $view = $_SESSION['translate']->it('View');
        $edit = $_SESSION['translate']->it('Edit');
        $delete = $_SESSION['translate']->it('Delete');


        $actions[] = "<a href=\"./index.php?module=phpwstimelog&amp;id={$this->_id}\">{$view}</a>";

        if(($_SESSION['OBJ_user']->username == $this->getOwner()) || $_SESSION['OBJ_user']->allow_access('phpwstimelog', 'edit_entries')) {
            $actions[] = "<a href=\"./index.php?module=phpwstimelog&amp;timelog_op=edit&amp;id={$this->_id}\">{$edit}</a>";
        }

        if($_SESSION['OBJ_user']->allow_access('phpwstimelog', 'delete_entries')) {
            $actions[] = "<a href=\"./index.php?module=phpwstimelog&amp;timelog_op=delete&amp;id={$this->_id}\">{$delete}</a>";
        }

        return implode(' | ', $actions);
    }
}

?>