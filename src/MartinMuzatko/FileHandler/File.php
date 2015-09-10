<?php

namespace MartinMuzatko\FileHandler;

use MartinMuzatko\FileHandler\Handler as FileHandler;

/**
 * A very simple Filehandler, using Method Chaining style.
 * @author Martin Muzatko
 * @copyright 2015
 * @license MIT license
 */
class File
{

	public $path;

	private $content;

	#public $handle;

	#private $mode = 'r+';

	/**
	 * The constructor and some other methods accepts any of these:
	 * 
	 * Instance of File
	 * String (Paths)
	 * Resources (Handles retrieved by fopen())
	 * Any other kind of File Stream
	 * @param mixed $path
	 */
	function __construct($resource)
	{
		$path = $this->getResource($resource);
		if ($path)
		{
			$this->path = $path;
			//$this->handle = fopen($this->path, $this->mode);
			// when working with handle that keeps open,
			// this error is thrown:
			// The process cannot access the file because it is being used by another process. (code: 32)
			$this->resolveInfo();
		}
	}

	function __destruct()
	{
		//fclose($this->handle);
	}

	private function getInfo()
	{
		$file = $this->path;
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
			$finfo = new \finfo();
			$finfo->set_flags(FILEINFO_MIME_TYPE);
			$info['mimetype']	= $finfo->file($info['path']); 
			$finfo->set_flags(FILEINFO_MIME_ENCODING);
			if ($info['mimetype'] != 'application/octet-stream')
			{
				$info['encoding'] = $finfo->file($info['path']); 
			}
		}
		return (object)$info;
	}

	/**
	 * Similar to Filehandler::getInfo()
	 * Maps all information to the object, so you can access them via:
	 * $f = new File('ex'); $f->created; $f->path; etc.
	 * @return void
	 */
	protected function resolveInfo()
	{
		$infos = $this->getInfo();
		foreach ($infos as $key => $info)
		{
			$this->$key = $info;
		}
/*		if (stream_get_meta_data($this->handle)['uri'] != $this->path)
		{
			fclose($this->handle);
			$this->handle = fopen($this->path, $this->mode);
		}*/
	}

	/**
	 * Base for Construct.
	 * @see File::__construct
	 * @param string | resource | File $resource
	 * @return string | boolean
	 */
	protected function getResource($resource)
	{
		if (is_string($resource))
		{
			return $resource;
		}
		if ($resource instanceof File)
		{
			return $resource->path;
		}
		if (is_resource($resource))
		{
			if (get_resource_type($resource) == 'stream')
			{
				return stream_get_meta_data($resource)['uri'];
			}
		}

		return false;
	}
	/**
	 * Get Content of File regardless of contenttype
	 * @see File::__construct
	 * @param mixed $content
	 */
	protected function getContent($content)
	{
		if ($content instanceof File || is_resource($content))
		{
			$res = $this->getResource($content);
			$file = new File($res);
			if ($file->exists)
			{
				return $this->content = $file->read();
			}
			throw new \Exception('file does not exist');
		}
		return $this->content = $content;
	}

	/**
	 * Resolve Directories to be created 
	 * when moving or renaming a file to a non-existing path.
	 * @param string $target
	 * @param boolean $origin
	 * @return string path of the new end-directory
	 */
	protected function resolveMakeDir($target, $origin = false)
	{
		if (strpos($target, '/') != false)
		{
			$dir = $this->dirname.'/';
			$folders = explode('/', $target);

			$path = $dir;
			if ($origin)
			{
				$path = '';
				array_pop($folders);
			}
			foreach ($folders as $folder)
			{
				$path .= $folder . '/'; 
				if (!is_dir($path))
				{
					mkdir($path);
				}
			}
			return $path;
		}
	}

	/* FILE LEVEL METHODS */

	/**
	 * Creates File at destination, given by constructor
	 * Will create all folders needed for creation
	 * -------------------
	 * Known bugs:
	 * create only works with files, not with directories,
	 * which means that the last item after the last slash (/) will be a file.
	 * Using a slash as last item (e.g.: path/to/) it will throw E_NOTICE by fopen
	 * @return this
	 */
	public function create()
	{
		$this->resolveMakeDir($this->path, true);
		FileHandler::create('./'.$this->path);
		$this->resolveInfo();
		return $this;
	}
	/**
	 * Copy file to target
	 * @param mixed $target
	 * @return this 
	 */
	public function copy($target)
	{
		$target = $this->getResource($target);
		$file = new File($target);
		$file
			->create()
			->write($this->read());
		$this->path = $target;
		$this->resolveInfo();
		return $this;
	}

	/**
	 * Deletes File at destination, given by constructor
	 * @return this
	 */
	public function delete()
	{
		if ($this->exists && unlink($this->path)) 
		{
			$this->resolveInfo();
		}
		else
		{
			throw new \Exception('File '.$file.' does not exist for deletion');
		}
		return $this;
	}

	/**
	 * Renames a file to a new name, given by constructor
	 * It is possible to move files with this method and rename them.
	 * @return this
	 */
	public function rename($name)
	{
		$name = $this->getResource($name);
		$dir = $this->dirname.'/';
		$new = $dir.$name;

		if (strpos($name, '/') != false)
		{
			$moveTo = new File($name);
			$this->move($moveTo->dirname);
		}

		FileHandler::rename($this->path, $new);
		$this->path = $new;
		$this->resolveInfo();
		return $this;
	}

	/**
	 * Moves a file to a folder. The targetpath is consisting of folders ONLY!
	 * Doesn't care wether or not you add a trailing slash.
	 * example:
	 * $f = new File('file.php'); $f->move('to/another/folder')
 	 * @param string $target 
	 * @return this
	 */
	public function move($target)
	{
		$target = rtrim($target, '/').'/';
		$path = $this->resolveMakeDir($target);
		FileHandler::rename($this->path, $path.$this->basename);
		$this->path = $path.$this->basename;
		$this->resolveInfo(); 
		return $this;
	}

	/**
	 * Changes the permissions of a file, using chmod octets
	 * eg: 0777 (equals to 511) or 0644 (equals to 420)
	 * @param octet |int $octet
	 */
	public function chmod($octet)
	{
		chmod($this->path, $octet);
		$this->resolveInfo();
		return $this;
	}

	/* CONTENT LEVEL METHODS */
	/**
	 * Work in progress
	 */
	public function merge($target)
	{
		
	}

	/**
	 * Read contents of file.
	 * Method chain is broken after calling this method.
	 * @return string
	 */
	public function read()
	{
		$read = FileHandler::read($this->path);
		if ((string)(int) $read === $read)
		{
			return (int) $read;
		}
		if ((string)(float) $read == $read)
		{
			return (float) $read;
		}
		return $read;
	}

	/**
	 * Adding content to a file.
	 * @param mixed $content
	 * @param string $separator - defaults to newline+carriage return 
	 * @return this
	 */
	public function concat($content, $separator = CRLF)
	{
		$read = $this->read();
		$read .= $read == '' ? '' : $separator;
		$content = $this->getContent($content);
		$content = $read.$content;
		$this->write($content);
		return $this;
	}

	/**
	 * Write as in overwrite 
	 * use concat if you want to add to the file instead of overwriting
	 * @param mixed $content
	 * @return this
	 */
	public function write($content)
	{
		$content = $this->getContent($content);
		FileHandler::write($this->path, $content);
		return $this;
	}
}