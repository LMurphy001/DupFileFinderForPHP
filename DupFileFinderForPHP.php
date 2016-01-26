<?php
//header('Content-Type:text/plain'); // for debugging
?><!DOCTYPE html>
<html><head><title>Duplicate File Finder for PHP</title></head><body>
<strong>Duplicate File Finder for PHP</strong><br />
<strong>IMPORTANT:</strong> Read the bottom of this document for <a href="#bottomMatter">Usage, Examples, License and Source Code</a> information<br />

<?php
do_it();

function do_it()
{
date_default_timezone_set('America/New_York');
$EXCLUDEFOLDERS = array();
$FOLDERS = array();
$EXCLUDEEXTENSIONS = array();
$EXTENSIONS = array();
$LIMITEXTENSIONS = false;
$SIZES = array();
$HASHES = array();

getQueryStringOptions($EXCLUDEFOLDERS, $FOLDERS, $EXCLUDEEXTENSIONS, $EXTENSIONS, $LIMITEXTENSIONS);

if (count ($FOLDERS) > 0) {
	echo "Starting at " . date('Y-m-d H:i:s T', time()) . "<br /><br />\n";

	foreach ($FOLDERS as $foldername) {
		recursivelyAddFolder($foldername, $SIZES, $EXCLUDEFOLDERS, $EXCLUDEEXTENSIONS, $EXTENSIONS, $LIMITEXTENSIONS );
	}
	echo date('Y-m-d H:i:s T', time()) . " Finished adding file sizes.<br />\n<br />\n";

	setFileHashes($SIZES, $HASHES);
	echo date('Y-m-d H:i:s T', time()) . " Finished getting file hashes.<br />\n<br />\n";

	foreach ($HASHES as $key => $files) {
		if (count($files) > 1) { // ONLY A DUP if there are 2 or more files with same size and hash key
			$keypieces = explode("-", $key);
			echo "<br /><strong>DUPLICATES:</strong> " . number_format($keypieces[0]) . " bytes. " . number_format(count($files)) . " files<br />\n";
			echo "<table style='border-collapse:collapse;border-spacing:10px;'>";
			
			// create a temporary array and sort it by earliest modification date to most recent modification date
			$tempArr = array();
			foreach ($files as $file) {
					$tempArr[filemtime($file).'-'.$file] = $file; // adding filename to the key guarantees key will be unique
			}
			ksort($tempArr); // Sort oldest to newest (modified date & time)
			foreach ($tempArr as $file)
				echo "<tr><td style='padding:5px;'>" . date('Y-m-d H:i:s T', filemtime($file)) . "</td><td style='padding:5px;'>$file</td></tr>\n";
			echo "</table>\n";
			unset($tempArr);
		}
	}
	echo date ('Y-m-d H:i:s T', time()) . " Done.<br /><br />\n";
} // if the user specified any folders

} // end of do_it() function

	
?>
<hr>
<div id="bottomMatter">
	<strong>Duplicate File Finder for PHP</strong><br />
	<strong>Usage</strong>:<br />
<? echo $_SERVER["HTTP_HOST"] . $_SERVER['PHP_SELF'] ?>?folders=<em>folder-list</em>&amp;extensions=<em>ext-list</em>&amp;excludeFolders=<em>folders-to-exclude</em>&amp;excludeExtensions=<em>ext-to-exclude</em><br />
where<br />
 <em>folder-list</em> is a list of folders to search, separated by '|'<br />
 <em>ext-list</em> is a list of file extensions to search, separatedy by '|'<br />
 <em>folders-to-exclude</em> is a list of folders to ignore, separated by '|'<br />
 <em>ext-to-exclude</em> is a list of file extensions to ignore, separated by '|'<br /><br />
 
 
<strong>Examples:</strong><br />
?folders=c:\users\userid\documents<br />
Search for duplicate files in the folder c:\users\userid\documents<br /><br />

?folders=d:\|e:\&amp;excludeFolders=d:\mozilla<br />
Search for duplicates in the d: and e: drives, excluding the subfolder d:\mozilla<br /><br />

?folders=c:|d:|e:&amp;extensions=jpg|png|gif<br />
Search for duplicates in the c:, d: and e: drives, only reporting jpg, png, and gif duplicates<br /><br />

?folders=c:&amp;excludeExtensions=jpg|png|gif<br />
Search for duplicate files in the c: drive, ignoring jpg, png and gif files<br /><br />


<strong>Copyright, License:</strong><br />
<p><em>Duplicate File Finder for PHP</em> Copyright (c) 2016 Linda Murphy</p>

<p>Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), for non-commercial purposes to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, and/or distribute copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:</p>

<p>The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.</p>

<p>This software was developed using <a href="http://www.zachsaw.com/" target="_blank">QuickPHP by Zachsaw</a>. That license stipulates that the "Free version is not to be used for commercial purposes (including developing commercial websites)". Therefore, if you wish to include the "Duplicate File Finder for PHP" in any commercial website, 
<a target="_blank" href="http://www.zachsaw.com/?pg=quickphp_commercial_license">please obtain a license for QuickPHP here.</a></p> 

<p>THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.</p>


<strong>Source Code Repository:</strong><br />
You can get a copy of <a href="https://github.com/LMurphy001/DupFileFinderForPHP" target="_blank">DupFileFinderForPHP</a> at GitHub.
<hr>

</div>
</body></html>
<?

// IgnorantRecursiveDirectoryIterator by antennen, php.net example documentation
class IgnorantRecursiveDirectoryIterator extends RecursiveDirectoryIterator { // Use just like the normal RecursiveDirectoryIterator
	function getChildren() {
		try {
			return new IgnorantRecursiveDirectoryIterator($this->getPathname());
		} catch (UnexpectedValueException $e) {
			return new RecursiveArrayIterator(array());
		}
	}
}

function getQueryStringOptions(&$EXCLUDEFOLDERS, &$FOLDERS, &$EXCLUDEEXTENSIONS, &$EXTENSIONS, &$LIMITEXTENSIONS ) {
	$qrystr = $_SERVER['QUERY_STRING'];
	if (strlen($qrystr) > 0)
		echo "Query String: $qrystr<br />\n";
	parse_str($qrystr, $qrypairs );
	
	if (isset($qrypairs["excludeFolders"])) {
		$EXCLUDEFOLDERS = explode('|', $qrypairs["excludeFolders"]);
		$EXCLUDEFOLDERS = addSuffixToEachElement($EXCLUDEFOLDERS, DIRECTORY_SEPARATOR);
		$EXCLUDEFOLDERS = replacePathsWithRealPaths($EXCLUDEFOLDERS);
	}
	
	if (isset($qrypairs["folders"])) {
		$FOLDERS = explode('|', $qrypairs["folders"]);
		$FOLDERS = addSuffixToEachElement($FOLDERS, DIRECTORY_SEPARATOR);
		$FOLDERS = replacePathsWithRealPaths($FOLDERS);
	}
	
	if (isset($qrypairs["excludeExtensions"])) {
		$EXCLUDEEXTENSIONS = explode("|", $qrypairs["excludeExtensions"]);
		$EXCLUDEEXTENSIONS = addPrefixToEachElement($EXCLUDEEXTENSIONS, ".");
	}
	
	if (isset($qrypairs["extensions"])) {
		$EXTENSIONS = explode("|", $qrypairs["extensions"]);
		$EXTENSIONS = addPrefixToEachElement($EXTENSIONS, ".");
	}
	
	$LIMITEXTENSIONS = count($EXTENSIONS) > 0;
	
	if (count($FOLDERS) > 0) {
		echo "Folders: <br />\n";
		foreach($FOLDERS as $folder)
			echo "&nbsp;&nbsp;$folder<br />\n";
		echo "<br />\n";
	}
	
	if (count($EXCLUDEFOLDERS) > 0) {
		echo "Excluded Folders: <br />\n";
		foreach($EXCLUDEFOLDERS as $folder)
			echo "&nbsp;$folder<br />\n";
		echo "<br />\n";
	}
	
	if (count($EXTENSIONS) > 0) {
		echo "Extensions: <br />\n";
		foreach($EXTENSIONS as $ext)
			echo "&nbsp;$ext<br />\n";
		echo "<br />\n";
	}
	
	if (count($EXCLUDEEXTENSIONS) > 0) {
		echo "Excluded Extensions: <br />\n";
		foreach($EXCLUDEEXTENSIONS as $ext)
			echo "&nbsp;$ext<br />\n";
		echo "<br />\n";
	}
	echo "<br />\n";
}

function replacePathsWithRealPaths($pathArr) {
	$tempArr = array();
	foreach ($pathArr as $pathName) {
		array_push($tempArr, realpath($pathName));
	}
	return $tempArr;
}

function addSuffixToEachElement($arr, $suffix) { // if array element does not end with suffix, add it to element. return whole array
	$tempArr = array();
	foreach ($arr as $key => $val) {
		if (endsWith($val, $suffix))
			array_push($tempArr, $val);
		else
			array_push($tempArr, $val . $suffix);
	}
	return $tempArr;
}

function addPrefixToEachElement($arr, $prefix) {
	$tempArr = array();
	foreach($arr as $key => $val) {
		if (beginsWith($val, $prefix))
			array_push($tempArr, $val);
		else
			array_push($tempArr, $prefix . $val);
	}
	return $tempArr;
}

function strEndsWithOneInArray($str, $arr) {
	//echo "str=" . $str . ", arr=" . $arr . "<br />\n";
	foreach ($arr as $val) {
		if (endsWith($str, $val))
			return true;
	}
	return false;
}

function strBeginsWithOneInArray($str, $arr) {
	foreach ($arr as $val) {
		if (beginsWith($str, $val))
			return true;
	}
	return false;
}

function recursivelyAddFolder($folderName, &$sizes_array, $EXCLUDEFOLDERS, $EXCLUDEEXTENSIONS, $EXTENSIONS, $LIMITEXTENSIONS ) {
	//$EXCLUDEFOLDERS, $EXCLUDEEXTENSIONS, $EXTENSIONS, $LIMITEXTENSIONS;
	
	$folderName = realpath($folderName);

	//echo date('Y-m-d H:i:s T', time()) . " Adding sizes for folder " . $folderName . ".<br />\n";
	$numberAppended = 0;
	$numberCreated = 0;
	
	try {
		$iter = new IgnorantRecursiveDirectoryIterator($folderName);
		$objects = new RecursiveIteratorIterator($iter, RecursiveIteratorIterator::SELF_FIRST);

		foreach($objects as $name => $object) {
			if (!is_file($name)) {
				continue; // ignore if not a regular file
			}
			
			if (strBeginsWithOneInArray($name, $EXCLUDEFOLDERS )) {
				// echo "Ignoring excluded folder: " . $name . "<br />\n";
				continue; // ignore this one because it is in excluded folder
			}
			
			if (strEndsWithOneInArray($name, $EXCLUDEEXTENSIONS)) {
				// echo "Ignoring excluded extension: " . $name . "<br />\n";
				continue; // ignore this one because it ends in an excluded file extension
			}
			
			if ($LIMITEXTENSIONS && (!strEndsWithOneInArray($name, $EXTENSIONS))) {
				//echo "Ignoring, desired extension not found: " . $name . "<br />\n";
				continue; // ignore this one because it does NOT end in a desired file extension
			}
			
			if (!is_readable($name)) {
				echo "Unreadable file: " . $name . "<br />\n";
				continue;
			}
				
			$fsize = filesize($name);
			if ($fsize < 1) {
				continue; // ignore empty files
			}
			
			//array_push($FILENAMES, $name);
			if (array_key_exists ( $fsize, $sizes_array )) {
				//echo "$fsize exists as a key already in sizes_array - $name<br />\n";
				$numberAppended++;
				array_push( $sizes_array[$fsize], $name); 
			}
			else {
				//echo "Adding $fsize as a key in sizes_array - $name<br />\n";
				$numberCreated++;
				$sizes_array[$fsize] = array($name); // create a new entry in GL_SIZES which is an array consisting of this name
			}
		}
	} catch (Exception $e) {
		echo "Exception: " . $e->getMessage() . "<br />\n";
	}
	//echo date('Y-m-d H:i:s T', time()) . " Number created:$numberCreated, Number appended:$numberAppended<br />\n<br />\n";
}

function endsWith($string, $test) {
	$strlen = strlen($string);
	$testlen = strlen($test);
	if ($testlen > $strlen) return false;
	return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
}

function beginsWith($string, $test) {
	$testlen = strlen($test);
	if ($testlen > strlen($string)) return false;
	return substr_compare($string, $test, 0, $testlen) === 0;
}

function setFileHashes(&$SIZES, &$HASHES) {
	krsort($SIZES); // sort the sizes array by sizes, biggest size to smallest size, maintain key-value associations
	
	foreach ($SIZES as $size => $filenames) {
		if (count($filenames) > 1) { // there is more than 1 file with this size
			foreach ($filenames as $filename) {
				$newkey = $size . "-" . hash_file("sha256", $filename);
				if (array_key_exists($newkey, $HASHES)) {	
					array_push( $HASHES[$newkey], $filename); // This size and hash already exists. DUPLICATE FOUND
				}
				else {
					$HASHES[$newkey] = array( $filename ); // this size and hash is new. create new array of filenames
				}
			}
		}
	}
}


?>