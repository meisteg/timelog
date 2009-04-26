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
 * @author    Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
 * @version   $Id: TimeLogPhase.php,v 1.1 2006/03/12 04:39:23 blindman1344 Exp $
 */

require_once PHPWS_SOURCE_DIR . 'core/Item.php';

class PHPWS_TimeLogPhase extends PHPWS_Item {

    /**
     * The ordinal of this phase
     *
     * @var int
     */
    var $_ordinal = 0;


    /**
     * Constructor
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function PHPWS_TimeLogPhase($phase = null) {
        $this->setTable('mod_phpwstimelog_phases');
        $this->addExclude(array('_hidden', '_approved', '_ip'));

        if(isset($phase)) {
            if(is_numeric($phase)) {
                $this->setId($phase);
                $this->init();
            } elseif(is_array($phase)) {
                $this->init($phase);
            }
        }
    }// END FUNC PHPWS_TimeLogPhase

    /**
     * Get label for list
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function getListlabel() {
        return $this->getLabel();
    }// END FUNC getListlabel

    /**
     * Get updated date for list
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function getListupdated() {
        return $this->getUpdated();
    }// END FUNC getListupdated

    /**
     * Get ordinal for list
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function getListordinal() {
        $result_min = $GLOBALS['core']->sqlSelect('mod_phpwstimelog_phases', NULL, NULL, 'ordinal asc', NULL, NULL, 1);
        $result_max = $GLOBALS['core']->sqlSelect('mod_phpwstimelog_phases', NULL, NULL, 'ordinal desc', NULL, NULL, 1);
        $up = $_SESSION['translate']->it('Up');
        $down = $_SESSION['translate']->it('Down');

        if ($result_min && ($result_min[0]['ordinal'] < $this->_ordinal)) {
            $up = '<a href="./index.php?module=phpwstimelog&amp;phase_op=moveup&amp;phase_id=' .
                  $this->getId() . '">' . $up . '</a>';
        }
        if ($result_max && ($result_max[0]['ordinal'] > $this->_ordinal)) {
            $down = '<a href="./index.php?module=phpwstimelog&amp;phase_op=movedown&amp;phase_id=' .
                    $this->getId() . '">' . $down . '</a>';
        }

        return $up . ' | ' . $down;
    }// END FUNC getListordinal

    /**
     * Get actions
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function getListactions() {
        $retVal = '<a href="./index.php?module=phpwstimelog&amp;phase_op=edit&amp;phase_id=' . $this->getId() .
                  '">' . $_SESSION['translate']->it('Edit') . '</a>';

        if(!$GLOBALS['core']->sqlSelect('mod_phpwstimelog_entries', 'phase', $this->getId())) {
            $retVal .= ' | <a href="./index.php?module=phpwstimelog&amp;phase_op=delete&amp;phase_id=' .
                       $this->getId() . '">' . $_SESSION['translate']->it('Delete') . '</a>';
        }

        return $retVal;
    }// END FUNC getListactions

    /**
     * Add/Edit phase form
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _edit() {
        if (!$_SESSION['OBJ_user']->allow_access('phpwstimelog', 'edit_settings')) {
            $message = $_SESSION['translate']->it('Access to edit was denied due to lack of proper permissions.');
            $error = new PHPWS_Error('phpwstimelog', 'PHPWS_TimeLogPhase::_edit()', $message, 'exit', 1);
            $error->message();
            return FALSE;
        }

        require_once PHPWS_SOURCE_DIR . 'core/EZform.php';

        $form = new EZForm();
        $form->add('module', 'hidden', 'phpwstimelog');
        $form->add('phase_op', 'hidden', 'save');
        if ($this->getId()) {
            $form->add('phase_id', 'hidden', $this->getId());
        }

        $form->add('phase', 'text', $this->_label);
        $form->setSize('phase', 35);

        $form->add('save', 'submit', $_SESSION['translate']->it('Save'));

        $tags = $form->getTemplate();
        $tags['MENU'] = $_SESSION['PHPWS_TimeLogManager']->menu();
        $tags['PHASE_LABEL'] = $_SESSION['translate']->it('Name');
        if ($this->getId()) {
            $tags['TITLE'] = $_SESSION['translate']->it('Edit Phase');
        }
        else {
            $tags['TITLE'] = $_SESSION['translate']->it('Add New Phase');
        }

        return PHPWS_Template::processTemplate($tags, 'phpwstimelog', 'phase_edit.tpl');
    }// END FUNC _edit

    /**
     * Save
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _save() {
        require_once PHPWS_SOURCE_DIR . 'core/Error.php';
        if (!$_SESSION['OBJ_user']->allow_access('phpwstimelog', 'edit_settings')) {
            $message = $_SESSION['translate']->it('Access to save was denied due to lack of proper permissions.');
            $error = new PHPWS_Error('phpwstimelog', 'PHPWS_TimeLogPhase::_save()', $message, 'exit', 1);
            $error->message();
            return FALSE;
        }

        $error = $this->setLabel($_POST['phase']);
        if (PHPWS_Error::isError($error)) {
            $error->message('CNT_timelog');
            return;
        }

        if (!$this->getId()) {
            $result = $GLOBALS['core']->sqlSelect('mod_phpwstimelog_phases', NULL, NULL, 'ordinal desc', NULL, NULL, 1);
            $this->_ordinal = $result[0]['ordinal'] + 1;
        }

        $this->commit();
        $_SESSION['PHPWS_TimeLogManager']->message = $_SESSION['translate']->it('Phase Saved!');

        $_REQUEST['phase_id'] = null;
        $_REQUEST['phase_op'] = null;
        $_REQUEST['op'] = 'getsettings';

        $_SESSION['PHPWS_TimeLogManager']->action();
    }// END FUNC _save

    /**
     * Move phase up/down
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _move() {
        require_once PHPWS_SOURCE_DIR . 'core/Error.php';
        if (!$_SESSION['OBJ_user']->allow_access('phpwstimelog', 'edit_settings')) {
            $message = $_SESSION['translate']->it('Access to move was denied due to lack of proper permissions.');
            $error = new PHPWS_Error('phpwstimelog', 'PHPWS_TimeLogPhase::_move()', $message, 'exit', 1);
            $error->message();
            return FALSE;
        }

        if ($_REQUEST['phase_op'] == 'moveup') {
            $result = $GLOBALS['core']->sqlSelect('mod_phpwstimelog_phases', 'ordinal', $this->_ordinal, 'ordinal desc', '<', NULL, 1);
            if ($result) {
                $GLOBALS['core']->sqlUpdate(array('ordinal'=>$this->_ordinal), 'mod_phpwstimelog_phases', 'id', $result[0]['id']);
                $GLOBALS['core']->sqlUpdate(array('ordinal'=>$result[0]['ordinal']), 'mod_phpwstimelog_phases', 'id', $this->getId());
                $_SESSION['PHPWS_TimeLogManager']->message = $_SESSION['translate']->it('Phase Moved!');
            }
        }
        elseif ($_REQUEST['phase_op'] == 'movedown') {
            $result = $GLOBALS['core']->sqlSelect('mod_phpwstimelog_phases', 'ordinal', $this->_ordinal, 'ordinal asc', '>', NULL, 1);
            if ($result) {
                $GLOBALS['core']->sqlUpdate(array('ordinal'=>$this->_ordinal), 'mod_phpwstimelog_phases', 'id', $result[0]['id']);
                $GLOBALS['core']->sqlUpdate(array('ordinal'=>$result[0]['ordinal']), 'mod_phpwstimelog_phases', 'id', $this->getId());
                $_SESSION['PHPWS_TimeLogManager']->message = $_SESSION['translate']->it('Phase Moved!');
            }
        }

        $_REQUEST['phase_id'] = null;
        $_REQUEST['phase_op'] = null;
        $_REQUEST['op'] = 'getsettings';

        $_SESSION['PHPWS_TimeLogManager']->action();
    }// END FUNC _move

    /**
     * Delete
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function _delete() {
        if (!$_SESSION['OBJ_user']->allow_access('phpwstimelog', 'edit_settings')) {
            $message = $_SESSION['translate']->it('Access to delete was denied due to lack of proper permissions.');
            $error = new PHPWS_Error('phpwstimelog', 'PHPWS_TimeLogPhase::_delete()', $message, 'exit', 1);
            $error->message();
            return FALSE;
        }

        if(!$GLOBALS['core']->sqlSelect('mod_phpwstimelog_entries', 'phase', $this->getId())) {
            $this->kill();
            $_SESSION['PHPWS_TimeLogManager']->message = $_SESSION['translate']->it('Phase Deleted!');
        }

        $_REQUEST['phase_id'] = null;
        $_REQUEST['phase_op'] = null;
        $_REQUEST['op'] = 'getsettings';

        $_SESSION['PHPWS_TimeLogManager']->action();
    }// END FUNC _delete

    /**
     * Action
     *
     * @author Greg Meiste <blindman1344@NOSPAM.users.sourceforge.net>
     */
    function action() {
        $content = NULL;

        switch($_REQUEST['phase_op']) {
            case 'edit':
            $content .= $this->_edit();
            break;

            case 'save':
            $content .= $this->_save();
            break;

            case 'delete':
            $content .= $this->_delete();
            break;

            case 'moveup':
            case 'movedown':
            $content .= $this->_move();
            break;
        }

        if (isset($content)) {
            $GLOBALS['CNT_timelog']['content'] .= $content;
        }
    }// END FUNC action
}

?>