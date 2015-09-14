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

	/**
	 * If more than one file is present when constructing, or a selection is made via find() or select()
	 * this property is filled with instances of the Files to these selections
	 */
	public $selection = [];

	#public $handle;

	#private $mode = 'r+';

	/**
	 * The constructor and some other methods accepts any of these:
	 * 
	 * Instance of File
	 * String (Paths)
	 * Resources (Handles retrieved by fopen())
	 * NULL (gets path from file the )
	 * Any other kind of File Stream
	 * @param mixed $path
	 */
	function __construct($resource = NULL)
	{
		$path = $this->getResource($resource);
		if ($path)
		{
			$this->path = $path;
			//$this->handle = fopen($this->path, $this->mode);
			// when working with handle that stays open,
			// this error is thrown:
			// The process cannot access the file because it is being used by another process. (code: 32)
			// So it is encouraged to close the handle after EACH action.
			$this->resolveInfo();
		}
	}

	function __destruct()
	{
		//fclose($this->handle);
	}

	private function getInfo()
	{
		return FileHandler::getInfo($this->path);
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
		/*
		if (stream_get_meta_data($this->handle)['uri'] != $this->path)
		{
			fclose($this->handle);
			$this->handle = fopen($this->path, $this->mode);
		}
		*/
	}

	/**
	 * Base for Construct.
	 * @see File::__construct
	 * @param string | resource | File $resource
	 * @return array | boolean
	 */
	protected function getResource($resource)
	{
		if (is_string($resource))
		{
			return $resource;
		}
		if ($resource instanceof File || is_array($resource))
		{
			$this->select($resource);
			return $this->selection;
		}
		if (is_resource($resource))
		{
			if (get_resource_type($resource) == 'stream')
			{
				return stream_get_meta_data($resource)['uri'];
			}
		}
		if (is_null($resource))
		{
			return dirname($_SERVER["SCRIPT_FILENAME"]).'/';
		}

		return false;
	}

	/**
	 * Selecting files by 
	 * 		paths
	 * 		array of paths
	 * 		array of Files
	 * 		array of File Selections
	 * You can also mix these
	 * Example:
	 * -------------------
	 * $file->select('file.png');
	 * $file->select(['file.png', 'another.jpg', 'file.avi']);
	 * $file->select([['file.png', 'another.jpg'], 'file.avi']);
	 * $file->select([$file->find(), 'customers/file.txt']);
	 * $file->select($file);
	 * -------------------
	 * Any array or array of arrays will be traversed down to create an one-dimensional array saved to public property $selection.
	 * Selections are retrievable by get()
	 * @param array | string | File -  $resoures
	 * @see File::__construct()
	 */
	public function select($resources = [])
	{
		$this->selection = [];
		$this->resolveSelect($resources);
		return $this;
	}

	public function resolvePath($file)
	{
		return is_dir($file) ? rtrim($file, '/').'/' : $file;
	}

	private function resolveSelect($resources)
	{
		if ($resources instanceof File)
		{
			if (count($resources->selection))
			{
				foreach ($resources->selection as $resource)
				{
					$this->selection[] = $this->resolvePath($resource);
				}
			}
			else
			{
				$this->selection[] = $this->resolvePath($resources->path);
			}
		}
		elseif (is_array($resources))
		{
			foreach ($resources as $resource) 
			{
				$this->resolveSelect($resource);
			}
		}
		else
		{
			$this->selection[] = $this->resolvePath($resources);
		}
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

	/**
	 * Find files in directory, regex enabled.
	 * By default, lists all files.
	 * EXAMPLES:
	 * --------------------
	 * Find by string:
	 * find('item.png')
	 * --------------------
	 * Find by Regex:
	 * find('/[\w]*$/')
	 * --------------------
	 * Find by Array:
	 * find(['mimetype' => 'image'])
	 * find(['size' => '>600KB'])
	 * find(['created' => '>15393837'])
	 * 
	 * --------------------
	 * returns this and saves found Items to public $selection.
	 * @param string|regex|array $lookup
	 * @return this
	 */
	public function find($lookup = '/.+/')
	{
	 	// TODO: recursive folder search+list

		$files = $this->isdir ? scandir($this->path) : scandir($this->dirname);
		$foundFiles = [];
		// Find one or more files
		if (is_string($lookup))
		{
			if (strpos($lookup, '/') === 0 
				&& strrpos($lookup, '/') + 1 == strlen($lookup))
			{
				foreach(preg_grep($lookup, $files) as $file)
				{
					$foundFiles[] = is_dir($file) ? $file.'/' : $file;
				}
			}
			// Find only one item
			else
			{
				$files = @$files[array_flip($files)[$lookup]];
				foreach($files as $file)
				{
					$foundFiles[] = is_dir($file) ? $file.'/' : $file;
				}
			}
		}
		else if(is_array($lookup))
		{
			$fileInfos = [];
			foreach ($files as $file)
			{
				$fileInfos[] = new File($file);
			}
			foreach ($fileInfos as $file)
			{
				foreach ($lookup as $attribute => $search) 
				{
					if (property_exists($file, $attribute))
					{
						$info = $file->$attribute;
						if ($this->resolveSearch($info, $search))
						{
							$foundFiles[] = $file->basename;
						}
					}
				}
			}
		}
		$this->selection = $foundFiles;
		return $this;
	}

	/**
	 * resolves search operators
	 */
	private function resolveSearch($value, $search)
	{
		$allowedOperators = ['<', '>', '<=', '>=', '!', '!='];
		$regex = '/[('.implode(')(', $allowedOperators).')]/';
		// TODO: PROBLEM WITH TYPE CASTING!
		$searchValue = preg_split($regex, (string) $search, -1, 1)[0];
		$operator = str_replace($searchValue, '', $search);
		// Operators can't be interpolated
		// Something like if ($search $operator $value){}
		// is NOT possible.
		switch ($operator)
		{
			case '<':
				return $value < $searchValue;
			case '>':
				return $value > $searchValue;
			case '<=':
				return $value <= $searchValue;
			case '>=':
				return $value >= $searchValue;
			case '!':
			case '!=':
				return $value != $searchValue;
			default:
				return $value == $searchValue;
		}
		return false;
	}
}