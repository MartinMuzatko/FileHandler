<?php

namespace MartinMuzatko\FileHandler;

/**
 * A very simple Filehandler.
 * @author Martin Muzatko
 * @copyright 2012
 * @license MIT license
 */

// tabulator
define('TAB', chr(9));
// linefeed
define('LF', chr(10));
// carriage return
define('CR', chr(13));
// Windows combination
define('CRLF', CR.LF);

class Handler
{

	/**
	 * Return fileinformation as array
	 * @param string $file
	 * @return array
	 */
	public static function getInfo($file)
	{
		$info 				= @pathinfo($file);
		$info['path']		= $file;
		$info['width']		= @getimagesize($file)[0];
		$info['height']		= @getimagesize($file)[1];
		$info['created']	= @filectime($file);
		$info['modified']	= @filemtime($file);
		$info['size']		= @filesize($file);
		$info['type']		= @filetype($file);
		$info['owner']		= @fileowner($file);
		$info['group']		= @filegroup($file);
		$info['perms']		= decoct(@fileperms($file));
		$info['writable']	= @is_writable($file);
		$info['readable']	= @is_readable($file);
		$info['exists']		= @file_exists($file);
		$info['isfile']		= @is_file($file);
		$info['isdir']		= @is_dir($file);
		$info['islink']		= @is_link($file);
		
		$info['mimetype'] = false;
		$info['encoding'] = false;
		if ($info['exists'])
		{
			if (class_exists('finfo'))
			{
				$finfo = new \finfo();
				$finfo->set_flags(FILEINFO_MIME_TYPE);
				$info['mimetype']	= $finfo->file($info['path']); 
				$finfo->set_flags(FILEINFO_MIME_ENCODING);
				if ($info['mimetype'] != 'application/octet-stream')
				{
					$info['encoding'] = $finfo->file($info['path']); 
				}
			}
		}
		return (object) $info;
	}

	/**
	 * Returns file content as array.
	 * @param string $file
	 * @return array
	 */
	public static function getAsArray($file)
	{
		$info = self::getInfo($file);
		if (!$info['exists'] || !$info['readable'])
		{
			throw new \Exception ('File does not exist or is not readable');
		}
		return file($file);

	}

	/**
	 * Return File as string if it exists
	 * @param string $file
	 * @return string
	 */
	public static function read($file)
	{
		$info = self::getInfo($file);
		if (!$info->exists)
		{
			throw new \Exception('File does not exist');
		}
		if (!$info->size)
		{
			return '';
		}
		$handle = fopen($file,'r');
		$output = fread($handle,filesize($file));
		fclose($handle);
		return $output;
	}

	/**
	 * Overwrites File with given string or array, returns true if succeeded.
	 * @param string $file
	 * @param array/string $file
	 * @return boolean
	 */
	public static function write($file, $content, $seperator = CRLF)
	{
		$info = self::getInfo($file);
		if (!$info->exists)
		{
			throw new \Exception ('File does not exist.');
		}
		if (!$info->writable)
		{
			throw new \Exception ('File is not writeable');
		}
		if (is_array($content))
		{
			implode($seperator, $content);
		}
		$handle = fopen($file,'w+');
		fwrite($handle, $content);
		fclose($handle);
		return true;
	}

	/**
	 * Creates File but does not overwrite existing files.
	 * Returns true if succeeded.
	 * @param string $file
	 * @param string $content = ''
	 * @return boolean
	 */
	public static function create($file, $content = '')
	{
		$info = self::getInfo($file);
		if ($info->exists)
		{
			return false;
		}
		if (strrpos($file, '/') + 1 == strlen($file))
		{
			mkdir($file);
		}
		else
		{
			$handle = fopen($file,'w+');
			fwrite($handle, $content);
			fclose($handle);
		}

		return true;
	}

	/**
	 * Delete file if existing.
	 * @param string $file
	 * @return boolean
	 */
	public static function delete($file)
	{
		$info = self::getInfo($file);
		if ($info->exists && unlink($file)) 
		{
			return true;
		}
		else
		{
			throw new \Exception ('File does not exist.');
		}
	}

	/**
	 * Rename a File from old path to new path
	 * @param old - path to new file
	 * @param new - path to new file
	 * @return boolean
	 */
	public static function rename($old, $new)
	{
		$info = self::getInfo($old);
		if ($info->exists) 
		{
			rename($old, $new);
			return true;
		}
		else
		{
			throw new \Exception ('File '.$old.' does not exist.');
		}
	}

	/**
	 * Appends $file to $targetFile with given seperator.
	 * @param string $targetFile
	 * @param string $file
	 * @param seperator = CRLF
	 * @return boolean
	 */
	public static function appendFile($targetFile, $file, $seperator = CRLF)
	{
		$targetInfo = self::getInfo($targetFile);
		$fileInfo = self::getInfo($file);
		if (!$fileInfo->exists || !$targetInfo['exists'])
		{
			throw new \Exception ('Targetfile or File does not exist.');
		}
		$targetFileContent = self::readFile($targetFile);
		$fileContent = self::readFile($file);
		self::writeFile($targetFile, $targetFileContent.$seperator.$fileContent);
		return true;
	}

	/**
	 * Appends $content to $targetFile with a defined $seperator
	 * If $content is array, it will append all array elements with defined $seperator.
	 * @param string $targetFile
	 * @param string/array $content
	 * @param string seperator = CRLF
	 * @return boolean
	 */
	public static function appendLine($targetFile, $content, $seperator = CRLF)
	{
		$info = self::getInfo($targetFile);
		if (!$info->exists)
		{
			throw new \Exception ('File does not exist');
		}
		if (is_array($content))
		{
			$content = implode($seperator, $content);
		}
		$targetFileContent = self::readFile($targetFile);
		self::writeFile($targetFile, $targetFileContent . $seperator . $content);
		return true;
	}

	/**
	 * Clears file from any content
	 * @param string $file
	 * @return boolean
	 */
	public static function clearFile($file)
	{
		self::writeFile($file,'');
		return true;
	}

	/**
	 * List all files within a valid directory as array
	 * @param string $file
	 * @param boolean $filesOnly - choose wether or not to only get files
	 * @return array or Exception
	 */
	public static function listFiles($path = '.', $includeFiles = true, $includeFolders = true)
	{
		if (!is_dir($path))
		{
			return false;
		}

		$dir = opendir($path);
		$files = [];
		while (false !== ($file = readdir($dir))) 
		{
			if ($includeFiles && is_file($path.'/'.$file))
			{
				$files[] = $file;
			}
			if ($includeFolders && is_dir($path.'/'.$file) 
				&& $file != '.' && $file != '..') 
			{
				$files[] = $file;
			}
		}

		return $files;
	}
}

?>
