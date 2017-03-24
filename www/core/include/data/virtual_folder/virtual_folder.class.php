<?php
/**
 * @package data
 * @version 0.4.0.0
 * @author Roman Konertz <konertz@open-lims.org>
 * @copyright (c) 2008-2016 by Roman Konertz
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
 * 
 */
require_once("interfaces/virtual_folder.interface.php");

if (constant("UNIT_TEST") == false or !defined("UNIT_TEST"))
{
	if ($GLOBALS['autoload_prefix'])
	{
		$path_prefix = $GLOBALS['autoload_prefix'];
	}
	else
	{
		$path_prefix = "";
	}
	
	require_once("events/virtual_folder_delete_event.class.php");

	require_once($path_prefix."core/include/data/access/data.wrapper.access.php");
	require_once("access/virtual_folder.access.php");
}

/**
 * Virtual Folder Management Class
 * @package data
 */
class VirtualFolder extends DataEntity implements VirtualFolderInterface
{
	protected $virtual_folder_id;
	protected $virtual_folder;
	
	private $ci_folder_id;
	private $ci_name;
	
	/**
	 * @see VirtualFolderInterface::__construct()
	 * @param integer $virtual_folder_id
	 */
	function __construct($virtual_folder_id)
	{
		if (is_numeric($virtual_folder_id))
		{
			if (VirtualFolder_Access::exist_virtual_folder_by_virtual_folder_id($virtual_folder_id) == true)
			{
				$this->virtual_folder_id 	= $virtual_folder_id;
				$this->virtual_folder		= new VirtualFolder_Access($virtual_folder_id);
				parent::__construct($this->virtual_folder->get_data_entity_id());
			}
			else
			{
				throw new VirtualFolderNotFoundException();
			}
		}
		else
		{
			$this->virtual_folder_id 	= null;
			$this->virtual_folder 		= new VirtualFolder_Access(null);
			parent::__construct(null);
		}
	}
	
	function __destruct()
	{
		if ($this->virtual_folder_id)
		{
			unset($this->virtual_folder_id);
			unset($this->virtual_folder);
		}
	}
	
	/**
	 * @see VirtualFolderInterface::create()
	 * @return integer
	 * @throws VirtualFolderCreateFailedException
	 * @throws VirtualFolderCreateFolderNotFoundException
	 * @throws VirtualFolderCreateIDMissingException
	 */
	public final function create()
	{
		global $transaction;
		
		if (is_numeric($this->ci_folder_id) and $this->ci_name)
		{
			$transaction_id = $transaction->begin();
			
			try
			{
				$folder = Folder::get_instance($this->ci_folder_id);
				
				if ($folder->exist_folder() == false)
				{
					throw new VirtualFolderCreateFolderNotFoundException();
				}
				
				parent::ci_set_owner_id(1);
				$data_entity_id = parent::create();
				parent::set_as_child_of($folder->get_data_entity_id());
				
				if (($vfolder_id = $this->virtual_folder->create($data_entity_id, $this->ci_name)) == null)
				{
					throw new VirtualFolderCreateFailedException();
				}
			}
			catch(BaseException $e)
			{
				if ($transaction_id != null)
				{
					$transaction->rollback($transaction_id);
				}
				throw $e;
			}
			
			if ($transaction_id != null)
			{
				$transaction->commit($transaction_id);
			}
			
			self::__construct($vfolder_id);
			
			return $vfolder_id;	
		}
		else
		{
			throw new VirtualFolderCreateIDMissingException();
		}
	}
	
	/**
	 * Injects $folder_id into create()
	 * @param integer $folder_id
	 */
	public function ci_set_folder_id($folder_id)
	{
		$this->ci_folder_id = $folder_id;
	}
	
	/**
	 * Injects $name into create()
	 * @param string $name
	 */
	public function ci_set_name($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @see VirtualFolderInterface::delete()
	 * @return bool
	 */
	public final function delete()
	{
		global $transaction;

		if ($this->virtual_folder_id and $this->virtual_folder)
		{
			$transaction_id = $transaction->begin();
			
			if ($this->unset_children() == false)
			{
				if ($transaction_id != null)
				{
					$transaction->rollback($transaction_id);
				}
				return false;
			} 

			$virtual_folder_delete_event = new VirtualFolderDeleteEvent($this->virtual_folder_id);
			$event_handler = new EventHandler($virtual_folder_delete_event);
			
			if ($event_handler->get_success() == false)
			{
				if ($transaction_id != null)
				{
					$transaction->rollback($transaction_id);
				}
				return false;
			}
			
			if (parent::delete() == false)
			{
				if ($transaction_id != null)
				{
					$transaction->rollback($transaction_id);
				}
				return false;
			}
			
			if ($this->virtual_folder->delete() == true)
			{
				if ($transaction_id != null)
				{
					$transaction->commit($transaction_id);
				}
				return true;
			}
			else
			{
				if ($transaction_id != null)
				{
					$transaction->rollback($transaction_id);
				}
				return false;
			}
		}
		else
		{
			return false;
		}	
	}
	
	/**
	 * @see VirtualFolderInterface::link_folder()
	 * @param integer $folder_id
	 * @return bool
	 */
	public function link_folder($folder_id)
	{		
		if (is_numeric($folder_id))
		{
			$folder = new Folder($folder_id);
			$data_entity_id = $folder->get_data_entity_id();
			
			if ($data_entity_id)
			{
				return $folder->set_as_child_of($this->data_entity_id);
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * @see VirtualFolderInterface::unlink_folder()
	 * @param integer $folder_id
	 * @return bool
	 */
	public function unlink_folder($folder_id)
	{
		global $transaction;
		
		if (is_numeric($folder_id))
		{
			$folder = new Folder($folder_id);
			$data_entity_id = $folder->get_data_entity_id();
			
			if ($data_entity_id)
			{
				return $folder->unset_child_of($this->data_entity_id);
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}
		
	/**
	 * @see VirtualFolderInterface::get_name()
	 * @return string
	 */
	public function get_name()
	{
		if ($this->virtual_folder_id and $this->virtual_folder)
		{
			return $this->virtual_folder->get_name();
		}
		else
		{
			return null;
		}
	}
		
	
	/**
	 * @see VirtualFolderInterface::get_virtual_folder_id_by_data_entity_id()
	 * @param integer $data_entity_id
	 * @return integer
	 */
	public static function get_virtual_folder_id_by_data_entity_id($data_entity_id)
	{	
		return VirtualFolder_Access::get_entry_by_data_entity_id($data_entity_id);
	}
	
	/**
	 * @see VirtualFolderInterface::exist_vfolder()
	 * @param integer $virtual_folder_id
	 * @return bool
	 */
	public static function exist_vfolder($virtual_folder_id)
	{
		if (is_numeric($virtual_folder_id))
		{
   			return VirtualFolder_Access::exist_virtual_folder_by_virtual_folder_id($virtual_folder_id);
   		}
   		else
   		{
   			return false;
   		}
	}
	
	/**
	 * @see VirtualFolderInterface::list_entries_by_folder_id()
	 * @param integer $folder_id
	 * @return array
	 */
	public static function list_entries_by_folder_id($folder_id)
	{
		return Data_Wrapper_Access::list_virtual_folders_by_folder_id($folder_id);
	}
	
}

?>