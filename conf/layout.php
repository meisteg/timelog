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
 * @version     $Id: layout.php,v 1.2 2004/12/22 14:59:19 blindman1344 Exp $
 */

/* Adding a content variable for display in the body */
$layout_info[] = array('content_var'  => 'CNT_timelog',
		       'transfer_var' => 'body');
                       
/* Adding a content variable for filter display */
$layout_info[] = array('content_var'  => 'CNT_timelog_filter',
		       'transfer_var' => 'left_col_top');

?>