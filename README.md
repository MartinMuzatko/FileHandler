# FileHandler
As lazy I am, when it comes to php function redundancy, I prefer to create my own tools rather than retyping the entire file open call over and over again.

I am using this self-made FileHandler for years now, and I still can't find a viable alternative.
There are some extra file tools out there, like this here: https://github.com/electrical/php-filehandler/blob/master/filehandler.class.php

# Optional Requirements

Make sure to enable finfo (fileinfo) php extension to get ```mimetype``` and ```encoding``` in ```FileHandler::getInfo```

# Installing

**Via Composer:**
```
php composer.phar require martinmuzatko/filehandler
```
or add it as dependency:
```
"require": {
	"martinmuzatko/filehandler": "*"
}
```


# Usage

Include the files or use autoloader.
```php
use martinmuzatko\filehandler\File;
use martinmuzatko\filehandler\Handler as FileHandler;
```

Using the methods below, you can easily create, write, rename, read files.
While **FileHandler** is a set of static methods, **File** serves a convenient way of quickly modifying files.

## FileHandler Examples

**Checking Image Dimensions**
```php
$file = 'path/to/image.jpg';
$info = FileHandler::getInfo($file);
if ($info->width > 1920 || $info->height > 1080)
{
  echo 'File '.$info->basename.' is too big, resize it to 1920x1080'; 
}
```

## File Examples

**Batch creating customer directories**
```php
$customers = ['01-jake', '02-mike', '03-francis', '04-martin', '05-jane'];
foreach ($customers as $customer)
{
	$file = new File($customer.'/info.json');
	$file
		->create()
		->write('[{ title: "Read instructions."}]')
		->chmod(0644);
}
```

# Constants

* TAB
* Linefeed (LF)
* Carriage Return (CR)
* Windows Combination (CRLF)

# Methods 

## FileHandler (Static)
Methods are called statically (FileHandler::getInfo())
  
### getInfo($file)
Return fileinformation as object 
Example: 
```php 
$info = FileHandler::getInfo($file); echo $info->writable;
```
Returns these informations:
**Paths**
 * dirname
 * basename
 * extension
 * filename
 * path
**Dimensions**
 * width
 * height
**Timestamps and other Properties**
 * created
 * modified
 * size
 * type
 * mimetype (only with finfo enabled)
 * encoding (only with finfo enabled)
**Permissions**
 * owner
 * group
 * perms
**Checks - Boolean**
 * writable
 * readable
 * exists
 * isfile
 * isdir
 * islink

### getAsArray($file)
Returns file content as array.

### read($file)
Return File as string if it exists

### write($file, $content, $seperator = CRLF)
Overwrites File with given string or array, returns true if succeeded.

### create($file, $content = '')
Creates File but does not overwrite existing files.

### delete($file)
Delete file if existing.

### rename($old, $new)
Rename a File from old path to new path

### appendFile($targetFile, $file, $seperator = CRLF)
Appends $file to $targetFile with given seperator.

### appendLine($targetFile, $content, $seperator = CRLF)
Appends $content to $targetFile with a defined $seperator

### clearFile($file)
Clears file from any content

### listFiles($path = '.', $includeFiles = true, $includeFolders = true)
List all files within a valid directory as array

## File (Method Chaining)

The constructor and some other methods accepts any of these:
* Instance of File
* String (Paths)
* Resources (Handles retrieved by ```fopen()```)
* Any other kind of File Stream

After constructing, the File contains ANY info retrievable via ```FileHandler::getInfo()```
e.g. 
```php
$f = new File('index.php'); $f->writable; 
```

###create()
Create file, accepts no param, file is entered via constructur:```new File('path/to/file');```
###copy($target)

###delete()

###rename($name)

###move($target)
Moving file to desired location, will automatically create all directories needed for new location
example:
```php
$f = new File('index.php');
$f->move('path/to/new/');
```
will move index.php to ```path/to/new``` and will create the folders ```./path```, ```./path/to``` and ```./path/to/new```

###chmod($octet)
Changing the file permissions from 0111 to 0777
###merge($target) *work in progress*

###read()
Get file contents. Method chaining after calling this method is not possible anymore.  
###concat($content, $separator = CRLF)
Adding content to a file.
###write($content)
Write as in overwrite (use concat if you want to add to the file instead of overwriting)

