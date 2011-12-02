<?php
/**
 * @package base
 * @version 0.4.0.0
 * @author Roman Konertz <konertz@open-lims.org>
 * @copyright (c) 2008-2011 by Roman Konertz
 * @license GPLv3
 * 
 * This file is part of Open-LIMS
 * Available at http://www.open-lims.org
 * 
 * This program is free software;
 * you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation;
 * version 3 of the License.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Base Request Class
 * @package base
 */
class BaseRequest
{	
	public static function ajax_handler()
	{
		switch($_GET[run]):
			
			case "login":
				require_once("ajax/login.ajax.php");
				echo LoginAjax::login($_POST[username], $_POST[password], $_POST[language]);
			break;
			
			case "logout":
				require_once("ajax/login.ajax.php");
				echo LoginAjax::logout();
			break;
			
		endswitch;
	}
	
	public static function io_handler()
	{
		switch ($_GET[run]):
		
			/**
			 * @todo IMPORTANT: remove
			 */
			case "myorgan":
				require_once("core/modules/organisation_unit/organisation_unit.io.php");
				OrganisationUnitIO::list_user_related_organisation_units();
			break;
			
			// BASE
			case "sysmsg":
				require_once("io/base.io.php");
				BaseIO::list_system_messages();
			break;
			
			case "system_info":
				require_once("io/base.io.php");
				BaseIO::system_info();
			break;
			
			case "software_info":
				require_once("io/base.io.php");
				BaseIO::software_info();
			break;
			
			case "license":
				require_once("io/base.io.php");
				BaseIO::license();
			break;
			
			
			// USER
			case "user_profile":
				require_once("io/user.io.php");
				UserIO::profile();
			break;
			
			case ("user_details"):
				require_once("io/user.io.php");
				UserIO::details();
			break;
			
			case("user_change_personal"):
				require_once("io/user.io.php");
				UserIO::change_personal();
			break;
			
			case("user_change_my_settings"):
				require_once("io/user.io.php");
				UserIO::change_my_settings();
			break;
			
			case("user_change_password"):
				require_once("io/user.io.php");
				UserIO::change_password();
			break;
			
			case("user_change_language"):
				require_once("io/user.io.php");
				UserIO::change_language();
			break;
			
			case("user_change_timezone"):
				require_once("io/user.io.php");
				UserIO::change_timezone();
			break;
			
		endswitch;
	}
}
?>