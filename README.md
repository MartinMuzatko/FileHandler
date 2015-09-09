# FileHandler
As lazy I am, when it comes to php function redundancy, I prefer to create my own tools rather than retyping the entire file open call over and over again.

I am using this self-made FileHandler for years now, and I still can't find a viable alternative.
There are some extra file tools out there, like this here: https://github.com/electrical/php-filehandler/blob/master/filehandler.class.php

# Constants

TAB
Linefeed (LF)
Carriage Return (CR)
Windows Combination (CRLF)

# Methods

### getInfo($file)
Return fileinformation as array

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
