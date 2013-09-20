<?php

/**
 * ownCloud - Documents App
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either 
 * version 3 of the License, or any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *  
 * You should have received a copy of the GNU Lesser General Public 
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 * 
 */


namespace OCA\Documents;

class Storage {

	public static function getDocuments() {
		$list = array_filter(
				\OCP\Files::searchByMime('application/vnd.oasis.opendocument.text'),
				function($item){
					//filter Deleted
					if (strpos($item['path'], '_trashbin')===0){
						return false;
					}
					return true;
				}
		);
		
		return $list;
	}
	
	/**
	 * @brief Retrieve path by fileId
	 * @param int $fileId
	 * @throws \Exception
	 */
	public static function getFilePath($fileId){
		if (!$fileId){
			throw new \Exception('No valid file has been passed');
		}
		
		$fileInfo = \OC\Files\Cache\Cache::getById($fileId);
		$path = @$fileInfo[1];

		if (!$path){
			throw new \Exception($fileId . ' can not be resolved');
		}
		
		$internalPath = preg_replace('/^\/?files/', '', $path);
		if (!\OC\Files\Filesystem::file_exists($internalPath)){
			$sharedInfo = \OCP\Share::getItemSharedWithBySource(
						'file', 
						$fileId,
						\OCP\Share::FORMAT_NONE,
						null,
						true
					);
			if (!$sharedInfo){
				throw new \Exception($path . ' can not be resolved in shared cache');
			}
			// this file is shared
			$internalPath = 'Shared' . $sharedInfo['file_target'];
		}
		
		if (!\OC\Files\Filesystem::file_exists($internalPath)){
			throw new \Exception($path . ' doesn\'t exist');
		}
		
		return 'files/' . $internalPath;
	}	

	/**
	 * @brief Cleanup session data on removing the document
	 * @param array
	 *
	 * This function is connected to the delete signal of OC_Filesystem
	 * to delete the related info from database
	 */
	public static function onDelete($params) {
		$info = \OC\Files\Filesystem::getFileInfo($params['path']);
		
		$fileId = @$info['fileid'];
		if (!$fileId){
			return;
		}
		
		$session = Session::getSessionByFileId($fileId);
		if (!is_array($session)){
			return;
		}
		
		Session::cleanUp($session['es_id']);
	}
}
