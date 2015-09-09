<?php

namespace MartinMuzatko\FileHandler;

/**
* 
*/
class File
{

	public $path;

	private $content;

	public $handle;

	private $mode = 'r+';

	function __construct($path)
	{
		$path = $this->getResource($path);
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

	protected function getResource($target)
	{
		if (is_string($target))
		{
			return $target;
		}
		if ($target instanceof File)
		{
			return $target->path;
		}
		if (is_resource($target))
		{
			if (get_resource_type($target) == 'stream')
			{
				return stream_get_meta_data($target)['uri'];
			}
		}

		return false;
	}

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

	public function create()
	{
		$this->resolveMakeDir($this->path, true);
		FileHandler::create('./'.$this->path);
		$this->resolveInfo();
		return $this;
	}

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

	public function move($target)
	{
		$target = rtrim($target, '/').'/';
		$path = $this->resolveMakeDir($target);
		FileHandler::rename($this->path, $path.$this->basename);
		$this->path = $path.$this->basename;
		$this->resolveInfo(); 
		return $this;
	}


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

	public function concat($content, $separator = CRLF)
	{
		$read = $this->read();
		$read .= $read == '' ? '' : $separator;
		$content = $this->getContent($content);
		$content = $read.$content;
		$this->write($content);
		return $this;
	}

	public function write($content)
	{
		$content = $this->getContent($content);
		FileHandler::write($this->path, $content);
		return $this;
	}
}