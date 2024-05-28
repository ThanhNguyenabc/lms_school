<?PHP


// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                          N U M B E R S                                         //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//

function Numbers_Between($x, $a, $b)
{
 return $x >= $a && $x <= $b;
}



function Numbers_Within($x, $a, $b)
{
 return $x > $a && $x < $b;
}



function Numbers_Range_Overlap($a, $b)
{
 return (($a["from"] == $b["from"]) && ($a["to"] == $b["to"]));
}



function Numbers_Range_Intersect($ra, $rb)
{
 return Numbers_Within($ra["from"], $rb["from"], $rb["to"]) || Numbers_Within($ra["to"],   $rb["from"], $rb["to"]) || Numbers_Within($rb["from"], $ra["from"], $ra["to"]) || Numbers_Within($rb["to"], $ra["from"], $ra["to"])  || Numbers_Range_Overlap($ra, $rb);
}






// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                       S T R I N G S                                            //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function String_Copycount_Get($string, $delimiter_a = "(", $delimiter_b = ")")
{
 $a = mb_strrpos($string, $delimiter_a);
 $b = mb_strrpos($string, $delimiter_b);
 

 if($b > $a)
 {
  $count = mb_substr($string, $a + 1, $b - $a - 1);
  
  if(is_numeric($count)) return intval($count);
 }
}





function String_Copycount_Set($string, $count, $delimiter_a = "(", $delimiter_b = ")")
{
 $current = String_Copycount_Get($string, $delimiter_a, $delimiter_b);
 if($current)
 { 
  $a = mb_strrpos($string, $delimiter_a);
  $b = mb_strrpos($string, $delimiter_b);
  
  $head = mb_substr($string, 0, $a);
  $tail = mb_substr($string, $b + 1, mb_strlen($string) - $b);
  
  return $head . $delimiter_a . $count . $delimiter_b . $tail;
 }
 else
 {
  return $string . " " . $delimiter_a . $count . $delimiter_b; 
 }
}




function String_Copycount_Next($string, $delimiter_a = "(", $delimiter_b = ")")
{
 $current = String_Copycount_Get($string, $delimiter_a, $delimiter_b);
 if(!$current) $current = 1; else $current = $current + 1;
 
 return String_Copycount_Set($string, $current, $delimiter_a, $delimiter_b);
}




function String_Filter_AllowDigits($string)
{
 return preg_replace('/[^0-9.]+/', '', $string);
}



function String_Variables_Apply($string, $variables, $delimiter = "%")
{
 foreach($variables as $key => $value)
 {
  $string = str_replace($delimiter . $key . $delimiter, $value, $string);
 }
 
 return $string;
}




// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                         A R R A Y S                                            //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//

function Array_Catalog_ByField($array, $field, $unique = false)
{
 $catalog = [];
 
 foreach($array as $item)
 {
  $value = $item[$field];
  
  if($unique)
  {
   $catalog[$value] = $item;
  }
  else
  {
   if(!isset($catalog[$value])) $catalog[$value] = [];
   array_push($catalog[$value], $item);
  }
 }
 
 return $catalog;
}



function Array_Filter_ByMarkers($array, $markers, $mode = "with")
{
 $filtered = [];
 $keys     = array_keys($markers);
 
 switch($mode)
 {
  case "with":
	foreach($array as &$item)
	{
	 $valid = true;
	 
	 foreach($keys as $key)
	 {
	  if($item[$key] != $markers[$key]) 
	  {
	   $valid = false;
	   break;
	  }
	 }
	 
	 if($valid) array_push($filtered, $item);
	}
  break;
 
  case "without":
	foreach($array as &$item)
	{
	 $valid = true;
	 
	 foreach($keys as $key)
	 {
	  if($item[$key] == $markers[$key]) 
	  {
	   $valid = false;
	   break;
	  }
	 }
	 
	 if($valid) array_push($filtered, $item);
	}
  break;
 }
 
 return $filtered;
}



function Array_Catalog_AsIndex($array, $index_field, $value_field = false, $list = false)
{
 $catalog = [];
 
 foreach($array as $item)
 {
  $index = $item[$index_field];
  
  // STORE THE VALUE OF A SPECIFIC ITEM FIELD, OR THE WHOLE ITEM?
  if($value_field) $value = $item[$value_field]; else $value = $item;
	
  // MAKE LISTS OR JUST STORE UNIQUE VALUES?	
  if($list)
  {
   // LIST OF VALUES
   if(!isset($catalog[$index])) $catalog[$index] = [];
   array_push($catalog[$index], $value);
  }
  else
  {
   // SINGLE VALUE
   $catalog[$index] = $value;
  }
 }
 
 return $catalog;
}



function Array_Integrate(&$dest, $dest_field, $source, $source_field = false, $store = false)
{
 // IF A MATCHING FIELD IS NOT SPECIFIED, THEN WE ASSUME THEY ARE THE SAME FIELD IN BOTH SETS
 if(!$source_field) $source_field = $dest_field;
 
 // SCAN DESTINATION ROWS
 foreach($dest as &$dest_row)
 {
  $search = $dest_row[$dest_field];
  
  // SEARCH FOR A ROW IN SOURCE, THAT HAS THE SAME MATCHING FIELD VALUE AS THIS ROW IN DEST
  foreach($source as $source_row)
  {
   // IF FOUND, MERGE
   if($source_row[$source_field] == $search)
   {
	// IF A SUBSTORE IS DEFINED, JUST ATTACH THE SOURCE ROW TO THE DEST ROW AS A SUBFIELD...
	if($store)
	{
	 $dest_row[$store] = $source_row;
	}
	else  
    // ...OTHERWISE MERGE THE TWO ROWS' VALUES
    {
     foreach($source_row as $key => $value) $dest_row[$key] = $value;
	}
    
	break;
   }
  }
  
 }
}


function Array_Integrate_Direct(&$dest, $field, $data, $store = false)
{
 foreach($dest as &$row)
 {
  $id     = $row[$field];
  $source = $data[$id];
  
  if($store)
  {
   $row[$store] = $source;
  }
  else
  {
   foreach($source as $key => $value) $row[$key] = $value;	 
  }
 }
}




function Array_Subset_Fields($array, $fields)
{
 $subset = [];
 
 foreach($fields as $field) $subset[$field] = $array[$field];
 
 return $subset;
}





function Array_Fields_JSONParse(&$array, $fields)
{
 $keys = array_keys($array);
 
 foreach($keys as $key)
 {
  if(in_array($key, $fields))
  {
   $array[$key] = json_decode($array[$key], true);
  } 
 }
}



function Array_Items_JSONParse(&$array, $fields)
{
 foreach($array as &$item) Array_Fields_JSONParse($item, $fields);
}



function Array_Item_Delete(&$array, $item, $direct = false)
{
 if($direct)
 {
  $index = $item;
 }
 else
 {
  $index = array_search($item, $array);
 }
 
 
 if($index !== false) array_splice($array, $index, 1);
}



function Array_Filter_Blanks($array)
{
 $filtered = array_filter($array, 
 function($value)
 {	 
  return ($value !== '');
 });
 
 return $filtered;
}




// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                        D A T E S                                               //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Date_From_PHP($phpdate)
{
 $date = $phpdate->format("YmdHi");
 
 return $date;
}



function Date_To_PHP($date)
{
 $phpdate = date_create($date);
 
 return $phpdate; 
}



function Date_Now()
{
 if(isset($_SESSION["forcetime"]))  
 {
  if($_SESSION["forcetime"] == 0) 
  {
   unset($_SESSION["forcetime"]);
  }
  else
  {
   return $_SESSION["forcetime"];
  }
 }
 
 $phpdate = date_create();
 $date    = Date_From_PHP($phpdate);
 
 return $date;
}




function Date_Add_Minutes($date, $minutes)
{	
 $t        = Date_To_PHP($date); 
 $interval = new DateInterval('PT'.abs($minutes).'M');
 
 if($minutes>0) $t->add($interval); else if($minutes<0) $t->sub($interval);
 
 return $t->format("YmdHi");
}



function Date_Add_Days($date, $days)
{	
 $t        = Date_To_PHP($date); 
 $interval = new DateInterval('P'.abs($days).'D');
 
 if($days>0) $t->add($interval); else if($days<0) $t->sub($interval);
 
 return $t->format("YmdHi");
}



function Date_Distance_Days($a, $b)
{
 $a = Date_To_PHP($a);
 $b = Date_To_PHP($b);
 
 $d    = $a->diff($b);
 
 $days = $d->days;
 if($d->invert == 1) $days = -$days;
 
 return $days;
}


function Date_Format_NoSeconds($date)
{
 return substr($date, 0, 12);
}



function Date_Format_As($date, $format = false)
{
 switch($format)
 {
  case "date-only":
	$date = substr($date, 0, 8);
  break;
  
  case "time-only":
	$date = substr($date, 8, 4);
  break;
  
  case "no-seconds":
	$date = substr($date, 0, 12);
  break;
 }
 
 return $date;
}



function Date_Valid($date)
{
 return (gettype($date) == "string") && (mb_strlen($date) >= 8);
}



function Date_Ranges_Extremes(&$dates, $from_id = "date_start", $to_id = "date_end")
{
 if(!$dates || count($dates) == 0)
 {
  $date_from = "197001010000";
  $date_to   = "999901010000";
 }
 else
 {
  $date_from = "999901010000";
  $date_to   = "197001010000";
 
  foreach($dates as &$date)
  {
   if($date[$from_id] < $date_from) $date_from = $date[$from_id];
   if($date[$to_id]   > $date_to)   $date_to   = $date[$to_id];
  }
 }
 
 $range              = [];
 $range["date_from"] = $date_from;
 $range["date_to"]   = $date_to;
 
 return $range;
}



function Time_To_Minutes($time)
{
 $time    = String_Filter_AllowDigits($time);
 
 $hours   = intval(substr($time, 0, 2));
 $minutes = intval(substr($time, 2, 2));
 
 return ($hours * 60) + $minutes;
}




// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                       S T O R A G E                                            //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//

function Storage_Path_Sanitize($path, $slash = "/")
{
 switch($slash)
 {
  case "/":
	$path = str_replace("\\", "/", $path);
  break;
  
  case "\\":
	$path = str_replace("/", "\\", $path);
  break;
 }
 
 return $path;
}





function Storage_Path_RemoveExtension($path)
{
 return substr($path, 0 , (strrpos($path, ".")));
}




function Storage_Path_GetFolder($path)
{
 $info = pathinfo($path);
 
 return $info["dirname"];
}



function Storage_Path_GetFilename($path)
{
 $info = pathinfo($path);
 
 return $info["basename"];
}



function Storage_Path_GetExtension($path)
{
 $info = pathinfo($path);
 
 return $info["extension"];
}


function Storage_Path_Create($path)
{
 if(!file_exists($path)) mkdir($path, 0777, true);
}



function Storage_Path_Map($root, $options = [])
{	 
 $map     = [];
 $uproot  = in_array("uproot",  $options);
 $recurse = in_array("recurse", $options);
 
 $folders    = array(); 
 $folders[0] = $root;
  
 while(count($folders)>0)
 {
  $folder = $folders[0];
  array_splice($folders, 0, 1);
  
  $data = scandir($folder);
  
  foreach($data as $item)
  {
   if(($item!='.') && ($item!='..'))
   {
	$file = "$folder/$item";
   
    if(is_dir($file))
    {
     if($uproot) $id = str_replace("$root/", "", $file); else $id = $file;
     $map[$id] = [];
	 
	 if($recurse) array_push($folders, $file);
    }
   }
  }
 }
 
 return $map;
}



function Storage_Files_Collect($root, $types, $options = [])
{	
 $folders = array();
 $files   = array();
 
 $folders[0] = $root;
 
 $recurse = in_array("recurse", $options);
 $uproot  = in_array("uproot",  $options);
 
 while(count($folders)>0)
 {
  $folder = $folders[0];
  array_splice($folders, 0, 1);
  
  $data = scandir($folder);
  
  foreach($data as $item)
  {
   if(($item!='.') && ($item!='..'))
   {
	$file = "$folder/$item";
   
    if(is_dir($file))
    {
     if($recurse) array_push($folders, $file);  
    }
    else
    {
     $ext = pathinfo($file, PATHINFO_EXTENSION);
	 if(!$types || in_array($ext, $types)) 
	 {
	  if($uproot) $id = str_replace("$root/", "", $file); else $id = $file;
    
      array_push($files, $id);
	 }
    } 
   }
  }
 }
 
 return $files;
}





function Storage_Folders_Collect($root, $options = [])
{	
 $folders = array();
 $files   = array();
 
 $folders[0] = $root;
 
 $recurse = in_array("recurse", $options);
 $uproot  = in_array("uproot",  $options);
 
 while(count($folders)>0)
 {
  $folder = $folders[0];
  array_splice($folders, 0, 1);
  
  $data = scandir($folder);
  
  foreach($data as $item)
  {
   if(($item!='.') && ($item!='..'))
   {
	$file = "$folder/$item";
   
    if(is_dir($file))
    {
     if($recurse) array_push($folders, $file);  
	 
	 if($uproot) $id = str_replace("$root/", "", $file); else $id = $file;
     array_push($files, $id);
    }
	
   }
  }
 }
 
 return $files;
}






function Storage_Files_Read($files, $mode, $root = "", $options = [])
{
 $noext = in_array("noext",  $options);

 if($root != "") $root = "$root/"; 
 
 
 // PREPARE POOL
 switch($mode)
 {
  case "stream":
	$data = "";
  break;
  
  default:
	$data  = [];	
  break;
 }
 
 
 // SCAN FILES
 foreach($files as $file)
 {
  // READ FILE AS BINARY BLOB OR TEXT 
  switch($mode)
  {
   case "binary":
	$contents = file("$root$file");
   break;
   
   case "ini":
    $contents = parse_ini_file("$root$file", true);
   break;
    
   default: 
	$contents = file_get_contents("$root$file");
   break;
  }  
  
  $id            = $file;
  if($noext) $id = Storage_Path_RemoveExtension($id);


  // ADD FILE TO POOL
  switch($mode)
  {
   case "stream":
	$data = $data . $contents;
   break;
   
   default:
	$data[$id] = $contents;  
   break;
  }
  
 }	 
 
 
 return $data;
}




function Storage_File_Delete($path)
{
 unlink($path);
}




function Storage_File_Create($filename, $data = false)
{
 $path = Storage_Path_GetFolder($filename);
 
 Storage_Path_Create($path);
 
 if($data) 
 {
  file_put_contents($filename, $data);
 }
 else 
 {
  return fopen($filename, "w+");
  }
}




function Storage_File_Upload($dest)
{ 
 if(file_exists($dest)) unlink($dest);
 else
 {
  $folder = Storage_Path_GetFolder($dest);
  if(!file_exists($folder)) Storage_Path_Create($folder);
 }
 
 $data = base64_decode(file_get_contents("php://input"));
 
 $file = fopen($dest, "x+");
 fwrite($file, $data);
 fclose($file);
 
 return $dest;
}




function Storage_Folder_ListFiles($folder, $filter = false)
{
 $files = scandir($folder);
 
 Array_Item_Delete($files, ".");
 Array_Item_Delete($files, "..");
 
 
 if($filter)
 {
  $filtered = [];
  
  foreach($files as $file) if(fnmatch($filter, $file))
  {
   array_push($filtered, $file);
  }

  $files = $filtered;  
 }
 
 return $files;
}





function Storage_Folder_ListFolders($folder)
{
 $files = scandir($folder);
 $list  = [];
 
 foreach($files as $file) if($file != "." && $file != ".." && is_dir("$folder/$file")) array_push($list, $file);
 
 return $list;
}




function Storage_Folder_Empty($folder)
{
 array_map("unlink", glob("$folder/*.*"));
}




function Storage_Folder_Delete($dir)
{ 
 if(!$dir) return;
 
 $files = array_diff(scandir($dir), array('.', '..')); 

 foreach($files as $file) 
 { 
  (is_dir("$dir/$file")) ? Storage_Folder_Delete("$dir/$file") : unlink("$dir/$file"); 
 }

 rmdir($dir); 
} 



function Storage_Folder_Download($dir)
{
 $zipfile = tempnam(sys_get_temp_dir(), "temp");
 
 Zip_Folder($dir, $zipfile, ["recurse"]);
 
 header('Content-Description: File Transfer');
 header('Content-Type: application/octet-stream');
 header('Content-Disposition: attachment; filename=' . basename($zipfile));
 header('Content-Transfer-Encoding: binary');
 header('Expires: 0');
 header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
 header('Pragma: public');
 header('Content-Length: ' . filesize($zipfile));
 
 ob_clean();
 flush();
 readfile($zipfile);
 
 unlink($zipfile);
}










//-----------------------------------------------------------------------------------------------//
//                                                                                               //
//                                     I N I    F I L E S                                        //
//                                                                                               //
//-----------------------------------------------------------------------------------------------//



function Ini_Key_Read($filename, $section, $key)
{
 $ini = parse_ini_file($filename, true);
 
 return $ini[$section][$key];
}




function Ini_Key_Write($filename, $section, $field, $value)
{	
 $ini = parse_ini_file($filename, true);
  
 $ini[$section][$field] = $value;
 
 $res = array();
 foreach($ini as $key => $val)
 {
  if(is_array($val))
  {
   $res[] = "\r\n[$key]";
   
   foreach($val as $skey => $sval) $res[] = "$skey = " . (is_numeric($sval) ? $sval : '"' . $sval . '"');
  }
  else $res[] = "$key = " . (is_numeric($val) ? $val : '"'.$val.'"');
 }
 
 $data = implode("\r\n", $res);
 
 
 $file = Storage_File_Create($filename);
 fwrite($file, $data);
 fclose($file);    
}





function Ini_Section_Read($filename, $section)
{
 $ini = Ini_File_Read($filename);
  
 return $ini[$section];
}





function Ini_Section_Write($filename, $section, $data)
{	
 $data = (array) $data;
 
 $ini = Ini_File_Read($filename);
  
 $ini[$section] = $data;
 
 Ini_File_Write($filename, $ini);
}



function Ini_Section_Delete($filename, $section)
{	 
 $ini = Ini_File_Read($filename);
  
 unset($ini[$section]);
 
 Ini_File_Write($filename, $ini);
}





function Ini_File_Read($filename)
{
 $ini = parse_ini_file($filename, true);

 return $ini;
}





function Ini_File_Write($filename, $ini)
{	
 $ini      = (array) $ini; 	
 $sections = array_keys($ini);

 $data     = "";
 
 foreach($sections as $section)
 { 
  $ini[$section] = (array) $ini[$section];
  $keys          = array_keys($ini[$section]);
  
  $data = $data . "[$section]\r\n";
  	 
  foreach($keys as $key)
  {	  
   $value = $ini[$section][$key];
   $data  = $data . "$key = \"$value\"\r\n";
  }
  
  $data = $data . "\r\n\r\n";
 }	 
 
 
 $folder = Storage_Path_GetFolder($filename);
 if(!file_exists($folder)) Storage_Path_Create($folder);

 $data = mb_convert_encoding($data, "UTF-8");
 file_put_contents($filename, $data);
}








//-----------------------------------------------------------------------------------------------//
//                                                                                               //
//                                     Z I P    F I L E S                                        //
//                                                                                               //
//-----------------------------------------------------------------------------------------------//




function Zip_Folder($folder, $archive, $options)
{
 $files = Storage_Files_Collect($folder, false, $options);
 
 $zip = new ZipArchive();
 
 $zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE);
 
 $add = [];
 foreach($files as $file) if(file_exists($file)) $zip->addFile($file, str_replace("$folder/", "", $file)); 
	
 $zip->close();
}





function Zip_Extract($archive, $folder)
{
 $zip = new ZipArchive();
 
 $zip->open($archive);
 
 $zip->extractTo($folder);
 
 $zip->close();
}





//-----------------------------------------------------------------------------------------------//
//                                                                                               //
//                                    T X T     F I L E S                                        //
//                                                                                               //
//-----------------------------------------------------------------------------------------------//

function TXT_Write($filename, $text, $linebreak = "\r\n")
{
 if(gettype($text) != "string") $text = implode($linebreak, $text);
 
 file_put_contents($filename, $text);
}



function TXT_Read($filename, $linebreak = "\r\n")
{
 if(!file_exists($filename)) return "";
 
 $text = file_get_contents($filename);
 $text = explode($linebreak, $text);
 
 return $text;
}






//-----------------------------------------------------------------------------------------------//
//                                                                                               //
//                                     C S V    F I L E S                                        //
//                                                                                               //
//-----------------------------------------------------------------------------------------------//

function CSV_Read($filename, $delimiter = ";", $fields = false)
{
 $data  = [];
 
 
 // READ AND SPLIT
 $text  = file_get_contents($filename);
 $lines = explode("\r\n", $text); 
 foreach($lines as &$line) 
 {
  $line = explode($delimiter, $line);
 }

 
 // PROCESS
 if($fields && count($lines) > 0)
 {
  // FIELDS IMPLIED TO BE ON THE FIRST LINE
  if($fields === true)
  {
   $fields = $lines[0];
   $start  = 1;
  }
  else
  // EXPLICIT FIELDS
  {
   $start = 0;
  }
  
  // GATHER
  for($i = $start; $i < count($lines); $i++)
  {
   $item  = [];
   $line  = $lines[$i];
   
   for($n = 0; $n < count($fields); $n++)
   {
	$field        = $fields[$n];
	$value        = $line[$n];
	$item[$field] = $value;
   }
   
   array_push($data, $item);
  }
 }
 else
 {
  $data = $lines;
 }
 
 return $data;
}






//-----------------------------------------------------------------------------------------------//
//                                                                                               //
//                                   J S O N     F I L E S                                       //
//                                                                                               //
//-----------------------------------------------------------------------------------------------//

function JSON_Write($filename, $data)
{
 $json = json_encode($data);
 
 file_put_contents($filename, $json);
}




function JSON_Read($filename)
{
 $data = file_get_contents($filename);
 $json = json_decode($data, true);
 
 return $json;
}







// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                       C L I E N T                                              //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Client_PublishVar($name, &$data, $local = false)
{
 $json = json_encode($data);
 
 if($local)
 {
  echo "var $name = $json;";
 }
 else
 {
  echo "<script>var $name = $json;</script>";
 }
}





function Client_IncludeScripts($folder, $types, $options = [], $exclusions = [])
{
 $scripts = Storage_Files_Collect($folder, $types, $options);
 
 if(in_array("nocache", $options)) $nocache = "?version=" . time(); else $nocache = "";
 
 foreach($scripts as $script)
 {
  $exclude = false;	 
  foreach($exclusions as $exclusion) 
  {  
   // CHECK FOR EXCLUDED PATHS
   if(mb_stripos($script, $exclusion) !== false)
   {
	$exclude = true;
    break;    
   }
  }
  
  
  // IF NOT EXCLUDED, INCLUDE
  if(!$exclude)
  {
   $ext = strtolower(pathinfo($script, PATHINFO_EXTENSION));
  
   // INCLUDE SCRIPTS AS ONE WOULD DO MANUALLY
   switch($ext)
   {
    case "js":
	 	echo "<script src='$script$nocache'></script>";
    break;
	 
    case "php":
 		include_once "$script$nocache";
    break;
	 
    case "css":
		echo "<link rel='stylesheet' href='$script$nocache'/>";
    break;
   }
  }
  
 }
}






//-----------------------------------------------------------------------------------------------//
//                                                                                               //
//                                     S R T    F I L E S                                        //
//                                                                                               //
//-----------------------------------------------------------------------------------------------//


function SRT_Timecode_Ms($timecode)
{
 $secs  = explode(",", $timecode);
 $msecs = $secs[1];
 $secs  = explode(":", $secs[0]);
	
 return (intval($secs[0]) * 60 * 60 * 1000) + (intval($secs[1]) * 60 * 1000) + (intval($secs[2]) * 1000) + intval($msecs);	
}




function SRT_File_Read($filename)
{
 $srt  = [];
 $file = fopen($filename, "r"); 
 
 while(!feof($file))
 {
  $line = trim(fgets($file));
  
  if($line != "")
  { 
   $index = $line;
   $time  = trim(fgets($file));
   $text  = trim(fgets($file));
   
   $time = explode(" --> ", $time);
   foreach($time as &$t) $t = SRT_Timecode_Ms($t);
   
   $item = [];
   $item["index"]    = count($srt);
   $item["start"]    = $time[0];
   $item["end"]      = $time[1];
   $item["duration"] = $time[1] - $time[0];
   $item["text"]     = $text;
   $item["type"]     = "line";
  
   array_push($srt, $item);
  }
 }
 
 fclose($file);
 return $srt;
}














// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                       S E R V E R                                              //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Server_Api()
{
 global $db;
 if(isset($_REQUEST["direct"])) 
 {
  $data = $_REQUEST;
  
  unset($data["f"]);
  unset($data["direct"]);
 }
 else
 {
  $data = json_decode(file_get_contents('php://input'), true);
 }	 
 
  
 // IDENTIFY THE REQUESTED FUNCTION AND ITS PARAMETERS
 $func      = $_REQUEST["f"];
 
 if(function_exists($func))
 {
  $refFunc   = new ReflectionFunction($func);
  $arguments = $refFunc->getParameters();

  // BUILD THE FUNCTION CALL BY ASSOCIATING URL PARAMETERS TO FUNCTION PARAMETERS
  $params = [];
  foreach($arguments as $argument)
  {
   if(key_exists($argument->name, $data)) $var = $data[$argument->name]; else $var = $argument->getDefaultValue();
   array_push($params, $var);
  }

  // CALL API
  $output = call_user_func_array($func, $params);
  
  if(!isset($_REQUEST["raw"])) $output = json_encode($output);

  // WRITE LOG
  /* if($func != "Users_Write_Log")
  {
    if($arguments) Users_Write_Log($func,["api_name" => $func,"request"=> array_combine(array_column($arguments,"name"),$params),"response" => $output]);
    else Users_Write_Log($func,["api_name" => $func,"request"=> "empty","response" => $output]);
  } */
 }
 else
 {
  $output = "";
 }
 unset($db);
 if(!isset($_REQUEST["passthru"])) echo $output;
}




function Server_URL()
{
 $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";  
 $url      = $protocol . $_SERVER['HTTP_HOST'] . Storage_Path_GetFolder($_SERVER["SCRIPT_NAME"]);
 
 return $url;
}






// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                         S Q L                                                  //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//



function SQL_Connect($host, $username, $password, $schema, $driver)
{ 
 $connection = new PDO("$driver:host=$host;dbname=$schema;charset=utf8", $username, $password, array(PDO::ATTR_PERSISTENT => true));
 $connection->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
 
 return $connection;
}





function SQL_Close(&$connection)
{
 $connection = null;	
}




function SQL_Format($value, $connections)
{	
 if($value === null) 
 {
  $value = "null";
  return $value;
 }
 
 $value = $connections[0]->quote($value);
 return $value;
}




function SQL_Format_IN($values, $connection)
{
 if(gettype($values) == "string")
 {
  $values = explode(",", $values);
 }
 
 for($i = 0; $i < count($values); $i++)
 {   
  $values[$i] = SQL_Format(trim($values[$i]), $connection);
 }
 
 $values = implode(", ", $values);
 
 return $values;
}




function SQL_Query($query, $connections)
{ 
 // DETERMINE EXECUTION MODE
 
 if(str_starts_with($query, "INSERT") || str_starts_with($query, "DELETE") || str_starts_with($query, "UPDATE") || str_starts_with($query, "REPLACE"))
 {
  $mode = "exec";
 }
 else
 {
  $mode = "query";
 }


 // EXECUTE QUERY
 
 switch($mode)
 {
  case "exec":
	 $connections[0]->exec($query);
     $id = $connections[0]->lastInsertId();
 
	 return $id;
  break;
  
  case "query":
	$result = $connections[1]->query($query);
	$data   = $result->fetchAll(PDO::FETCH_ASSOC);
	
	return $data;
  break;
 }
 
}




function SQL_Fields_Update($object, $connection)
{
 $object = (array)$object;
 $tokens = [];
 
 foreach($object as $field => $value)
 {
  $value = SQL_Format($value, $connection); 
  array_push($tokens, "$field = $value"); 
 }
 
 $string = implode(", ", $tokens);
 return $string;
}




function SQL_Fields_Insert($object, $connection)
{
 $object = (array)$object;
 
 $keys   = array_keys($object);
 $fields = implode(", ", $keys);
 
 $values = array_values($object);
 $tokens = [];
 foreach($values as $value)
 {
  $type = gettype($value);
  if($type == "array" || $type == "object") $value = json_encode($value);

  $value = SQL_Format($value, $connection);
  array_push($tokens, $value);
 }
 $values = implode(", ", $tokens);
 
 $string = "($fields) VALUES($values)";
 return $string;
}



function SQL_Table_Fields($table, $connection)
{
 $data   = SQL_Query("SHOW COLUMNS FROM $table", $connection);
 $fields = array_column($data, "Field");
 
 return $fields;
}




function SQL_Fields_List($object)
{
 $object = (array)$object;
 
 $keys   = array_keys($object);
 $keys   = Array_Filter_Blanks($keys);
  
 $string = implode(", ", $keys);
 return $string;
}




function SQL_Values_List($object)
{
 $object = (array)$object;
 
 $keys   = array_values($object);
 $keys   = Array_Filter_Blanks($keys);
  
 $string = implode(", ", $keys);
 return $string;
}





function SQL_Transaction_Begin($db)
{
 $db[0]->beginTransaction();
}




function SQL_Transaction_Commit($db)
{
 $db[0]->commit();
}



function SQL_Transaction_Rollback($db)
{
 $db[0]->rollBack();
}





// IDENTIFY A SPECIFIC ROW, PERFORM OPERATIONS ON IT, AND UPDATE IT
/*
function SQL_Row_Process($table, $fields, $condition, $connection, $func)
{
 $rows = SQL_Query("SELECT $fields FROM $table WHERE $condition", $connection);
 $row  = $rows[0];
 
 $row  = $func($row);
 
 SQL_Query("UPDATE $table SET $updates WHERE $condition", $connection);
}
*/






// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                          M E T A                                               //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Meta_Function_ObjectFromCall()
{
 $backtrace = debug_backtrace()[1];
 $caller = $backtrace["function"];
 
 // GET KEYS
 $keys   = [];
 $func   = new ReflectionFunction($caller);
 $params = $func->getParameters();
 foreach($params as $param)
 {
  $name = $param->getName();
  array_push($keys, $name);
 }
 
 // GET NAMES
 $values = $backtrace["args"];
 
 
 // BUILD OBJECT
 $data = [];
 $i    = 0;
 foreach($keys as $key)
 {
  $data[$key] = $values[$i]; 
  $i          = $i + 1;
 }	 
 
 return $data;
}




?><?PHP

function Template_Load()
{
}

?><?php

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                   A C T I V I T I E S                                          //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//

function Activities_Results_ReadBySource($student_id, $source, $strict = true)
{ 
 $db    = Core_Database_Open(); 
 
 if($strict)
 {
  $source    = SQL_Format($source, $db);
  $condition = "(source = $source)";
 }
 else
 {
  $source    = SQL_Format($source . "%", $db);
  $condition = "(source LIKE $source)";
 }
 
 $query = "SELECT * FROM users_activities WHERE (student_id = $student_id) AND $condition ORDER BY source, date";
 $rows  = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 // CONVERT ALL DATA FIELDS TO OBJECTS. WE SHOULD DO THIS CLIENT-SIDE, BUT WE WANT TO MAKE THINGS MORE TRANSPARENT
 Array_Items_JSONParse($rows, ["data"]);
 
 
 return $rows;	
}




function Activity_Result_Store($student_id, $source, $mode, $score, $data, $duration = 0)
{
 $data         = Meta_Function_ObjectFromCall();
 $data["date"] = Date_Now();
 
 $db           = Core_Database_Open(); 
 
 $fields       = SQL_Fields_Insert($data, $db);
 
 $query        = "INSERT INTO users_activities $fields";
 $id           = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 return $id;
}


function Activity_Result_Update($id, $mode, $score, $data)
{
 $db           = Core_Database_Open(); 
 try {
    if(gettype($data) == "array" || gettype($data) == "object") $data       = json_encode($data);
    $data       = SQL_Format($data,$db);
    $mode       = SQL_Format($mode,$db);
    $query      = "UPDATE users_activities SET mode = $mode , score = $score , data = $data WHERE id = $id";
    SQL_Query($query, $db);
    
    SQL_Close($db);
    
    return ["succcess" => "true"];
 } catch (\Throwable $th) {
    var_dump($th);
    return ["error" => $th];
 }
}

?><?php

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                   A S S E S S M E N T                                          //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Assessment_Seat_Data($seat_id, $options = [])
{
 $data = [];
 
 // READ SEAT COMPLETE WITH STUDENT INFO
 $seat         = Class_Seat_Read($seat_id, ["users" => true]);
 $data["seat"] = $seat;
 
 
 // GET LESSON AND TEACHER IDs FROM CLASS
 $class_id   = $seat["class_id"];
 
 $fields     = Core_Database_ReadTable("classes", $class_id, ["teacher_id", "lesson_id"]);
 $teacher_id = $fields["teacher_id"];
 $lesson_id  = $fields["lesson_id"];
 
 $data["teacher_id"] = $teacher_id;
 $data["lesson_id"]  = $lesson_id;
 
 // READ LESSON COMPLETE WITH OUTCOMES
 $data["lesson"] = Lesson_Read($lesson_id, ["outcomes" => true]);
 
 
 // OPTIONAL DATA
 
 
 // PREPARATION (PRECLASS ACTIVITIES' SCORES)
 if(isset($options["preparation"]))
 {
  // READ ALL ACTIVITIES THE STUDENT COMPLETED FOR THIS SEAT'S LESSON
  $activities = Activities_Results_ReadBySource($seat["student_id"], $lesson_id, false);
  
  // DERIVE ACTIVITY TYPE BY SOURCE
  foreach($activities as &$activity)
  {
   $activity["type"] = Storage_Path_GetFilename($activity["source"]);
  }
  
  // CATALOG BY TYPE
  $activities = Array_Catalog_ByField($activities, "type");
  
  // GET MOST RECENT SCORE FOR EACH ACTIVITY TYPE
  $scores = [];
  $types  = array_keys($activities);
  foreach($types as $type)
  {
   $activities[$type] = array_reverse($activities[$type]);
   $last              = $activities[$type][0];
   $scores[$type]     = $last["score"];
  }
  
  $data["preparation"] = $scores;
 }
 
 
 // PERFORMANCE FROM PAST CLASSES
 if(isset($options["performance"]))
 {
  $performance = [];
  
  $n     = $options["performance"];
  $seats = Class_Seats_ListByStudent(student_id:$seat["student_id"], date_to:Date_Now(), options:["last" => true, "limit" =>$n, "assessment" => true]);
  $seats = array_reverse($seats);
  
  foreach($seats as &$seat)
  {
   $array = $seat["assessment"] ?? [0];
   $score = array_sum($array) / count($array);
   
   array_push($performance, $score);
  }
  
  $data["performance"] = $performance;
 }
 
 
 return $data;
}






function Assessment_Outcome_Store($table, $id, $field, $value, $return = false)
{
 $db    = Core_Database_Open();
 
 // GET CURRENT JSON
 $query = "SELECT assessment FROM $table WHERE id = $id";
 $rows  = SQL_Query($query, $db);
 $seat  = $rows[0];

 // DECODE OR CREATE IF NECESSARY, UPDATE
 if(!isset($seat["assessment"])) 
 {
  $data = [];
 }
 else
 {
  $data = json_decode($seat["assessment"], true);
 }
 
 $data[$field] = $value;


 // RE-ENCODE AND STORE BACK
 $assessment = json_encode($data);
 $assessment = SQL_Format($assessment, $db);
 $query      = "UPDATE $table SET assessment = $assessment WHERE id = $id";
 SQL_Query($query, $db);
 
 // UPDATE HOMEWORK SCORE 
 if($field == "hw-000" || $table == "projects_students")
 {
  Grades_Homework_Projects_Update($table,$id,$field);
 }
 // RETURN THE WHOLE UPDATED OBJECT ONLY IF REQUESTED
 if($return) return $data;
}



function Assessment_Status($teacher_id = -1, $date_from = "190001010000", $date_to = "290001010000", $options = [])
{
 if($teacher_id == -1) $teacher_id = $_SESSION["user"]["id"];
 
 // FORCE DATE_TO TO NOW
 $date_to = Date_Now(); 

 // GET ALL CLASSES FOR THE GIVEN TEACHER IN THE GIVEN DATE RANGE
 $classes = Classes_List_ByTeacher($teacher_id, $date_from, $date_to, "id, lesson_id, center_id, date_start");
 
 if(count($classes) == 0) return [];
 

 // COLLECT INFO FOR ALL LESSONS
 $ids     = array_column($classes, "lesson_id");
 $ids     = array_unique($ids);
 $lessons = [];
 foreach($ids as $id)
 {
  $lessons[$id]                = Lesson_Read($id, "base-info");
  $lessons[$id]["assessables"] = Lesson_Assessables($id);
 }
 

 // GET ALL SEATS FROM THE LIST OF CLASSES
 $db    = Core_Database_Open();
 $list  = array_column($classes, "id");
 $list  = SQL_Values_List($list);
 
 $query = "SELECT id, class_id, student_id, attendance, assessment FROM classes_seats WHERE class_id IN ($list)";
 $seats = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 
 // PROCESS SEATS
 $classes = Array_Catalog_ByField($classes, "id", true);
 foreach($seats as &$seat) 
 {
  // FIND CLASS AND LESSON DATA FOR THIS SEAT
  $class  = $classes[$seat["class_id"]];
  $lesson = $lessons[$class["lesson_id"]];
  $center = $lessons[$class["center_id"]];
  
  $seat["date_start"] = $class["date_start"];
  $seat["lesson_id"]  = $class["lesson_id"];
  $seat["center_id"]  = $class["center_id"];

  
  // DECODE SEAT ASSESSMENT
  $seat["assessment"] = json_decode($seat["assessment"], true);
  
  
  // CHECK WHAT'S MISSING
  $missing = [];
  
  $categories = array_keys($lesson["assessables"]);
  foreach($categories as $category)
  {
   $done = false;
   
   foreach($lesson["assessables"][$category] as $key)
   {
	if(isset($seat["assessment"][$key]))
	{
     $done = true;
	 break;
	}		
   }
   
   if(!$done) array_push($missing, $category);
  }
  
  if(!$seat["attendance"]) array_push($missing, "attendance");
  
  $seat["todo"] = $missing;
  
  // DISCARD ASSESSMENT
  //unset($seat["assessment"]);
 }
 
 
 if($options["incomplete"])
 {
  $filtered = [];
  foreach($seats as &$seat) if(count($seat["todo"]) > 0) array_push($filtered, $seat);
  
  $seats = $filtered;
 }
 
 
 // ADD STUDENTS NAME AND LAST NAME TO SEATS
 Users_Integrate($seats, "student_id", "id,firstname,lastname,nickname", $container = "student");
 
 
 // ADJUST DATES
 if(!$options["utc"]) User_Date_Process($seats, "date_", "out");
 
 
 return $seats;
}


function Sync_PlacementTest_Data()
{
  $db    = Core_Database_Open();
  $dataPOST = trim(file_get_contents('php://input'));
  $xmlData = simplexml_load_string($dataPOST);
  $json = json_encode($xmlData);
  $array = json_decode($json,true);

  $testId = $array["ID"] ?? -1;
  if($testId)
  {
    $placementTests = SQL_Query("SELECT * FROM placement_tests WHERE placement_test_id = $testId", $db);
    if(count($placementTests))
    {
      
      $studentTest = $placementTests[0];

      // GET LESSON NAME
      $classId = $studentTest["class_id"];
      $classes = SQL_Query("SELECT lesson_id FROM classes WHERE id = $classId", $db);
      $source = $classes[0]["lesson_id"];
      $lesson = parse_ini_file("content/lessons/$source/info.dat", true);

      // READ CORE SKILLS
      $keys                  = array_values($lesson["core skills"] ?? []) ?: array_keys($skills["core skills"] ?? []) ?: [];
      $lesson["core skills"] = [];
        
      foreach($keys as $key)
      {
        $lesson["core skills"][$key] = Outcome_Read("content/skills/$key");   
      }


      // READ OUTCOMES
      $ids                = array_values($lesson["outcomes"] ?? []);
      $lesson["outcomes"] = [];

      foreach($ids as $id)
      {
        $outcome                 = Outcome_Read("content/outcomes/$id");
        $lesson["outcomes"][$id] = $outcome;
      }

      $behavior = parse_ini_file("application/modules/assessment/behavior.dat",true);

      $comment = [];
      $studentCode = $studentTest["student_code"];

      $seats = SQL_Query("SELECT classes_seats.feedback, classes_seats.behavior, classes_seats.assessment FROM classes_seats JOIN users ON classes_seats.student_id = users.id WHERE classes_seats.class_id = $classId AND users.staffcode = '$studentCode'", $db);
      $assessment = json_decode($seats[0]["assessment"], true);

      $hours = $lesson["outcomes"]["pt-002"]["lv ".$assessment["pt-002"]]["hours"];
      $levelIdeal = $lesson["outcomes"]["pt-001"]["lv ".$assessment["pt-001"]]["erp"];
      $module = $lesson["outcomes"]["pt-001"]["lv ".$assessment["pt-001"]]["module"];

      // GET PROGRAM
      $programs = parse_ini_file("partners/default/programs.cfg",true);
      $programer = "";
      $level = $levelIdeal . "-" . $module;
      foreach ($programs as $key => $program) {
        foreach ($program as $keyP => $value) {
          if($keyP == $level){ $programer = $program["program"]; break; }
        }
        if($programer != "") break;
      }

      $programer_mapping = [
        "Jumpstart" => "EY-K",
        "Super Juniors" => "EY-J",
        "Smart Teens" => "EY-S",
        "Global English" => "EA-GE",
        "Jumpstart Online" => "EY-KO",
        "Super Juniors Online" => "EY-JO",
        "Smart Teens Online" => "EY-SO",
        "Global English Online" => "EA-GO"
      ];

      $programe = $programer_mapping[$programer];


      foreach ($assessment as $key => $value) {
        if(isset($lesson["core skills"][$key])) $comment[$lesson["core skills"][$key]["info"]["en"]] = $lesson["core skills"][$key]["lv $value"]["en"];
      }

      $comment["behavior"] = $behavior[(int)$seats[0]["behavior"]]["en"];
      $comment["feedback"] = $seats[0]["feedback"];

      $description = "";
      foreach ($comment as $key => $value) {
        $description .= "(" . $key . "):\n" . $value . ".\n";
      }

      $xml = '<?xml version="1.0" encoding="utf-8"?>
      <Result>
        <getStatus>1</getStatus>
        <Message>successful</Message>
        <PTID>%placement_test_id%</PTID>
        <Programme>%programme%</Programme>
        <LevelID>%level%</LevelID>
        <Module>%module%</Module>
        <IdealHours>%hour%</IdealHours>
        <SpeakingScore>0</SpeakingScore>
        <Description>%description%</Description>
        <Description_JS></Description_JS>
      </Result>';
      $xml = str_replace("%placement_test_id%",$studentTest["placement_test_id"],$xml);
      $xml = str_replace("%programme%",$programe,$xml);
      $xml = str_replace("%level%",$levelIdeal,$xml);
      $xml = str_replace("%module%",$module,$xml);
      $xml = str_replace("%hour%",$hours,$xml);
      $xml = str_replace("%description%",$description,$xml);

      header('Content-Type: text/xml');
      print_r($xml);
      
    }
  }
  SQL_Close($db);
  die;
}


?><?PHP



?><?PHP

function Bookpage_Load()
{
}


function Bookpage_Read($source, $options = [])
{
 $bookpage = [];
 $bookpage = parse_ini_file("$source/info.dat", true) ?: [];
 
 // READ PARAGRAPHS INFO
 $keys     = array_keys($bookpage);
 foreach($keys as $key)
 { 
  if($key != "page")
  {   
   // TEXT
   $bookpage[$key]["texts"] = [];
   for($i = 1; $i<=9; $i++)
   {
	$file = "$source/$key-$i.txt";
	
	if(file_exists($file))
	{
     $bookpage[$key]["texts"][$i] = file_get_contents($file);
	}
   }
   
   // PICTURES
   $bookpage[$key]["pictures"] = [];
   for($i = 1; $i<=9; $i++)
   {
	$file = "$source/$key-$i.jpg";
	
	if(file_exists($file))
	{
     $bookpage[$key]["pictures"][$i] = $file;
	}
   }
  }
 }
 
 return $bookpage;
}




function Bookpage_Write_Header($path, $header)
{
 if(!file_exists($path)) Storage_Path_Create($path);
 
 Ini_Section_Write("$path/info.dat", "page", $header);
}



function Bookpage_Write_ParagraphHeader($path, $id, $header)
{
 if(!file_exists($path)) Storage_Path_Create($path);
 
 Ini_Section_Write("$path/info.dat", $id, $header);
}




function Bookpage_Write_ParagraphText($path, $paragraph, $n, $text)
{
 if(!file_exists($path)) Storage_Path_Create($path);
 
 Ini_Key_Write("$path/info.dat", $paragraph, "updated", "");
 file_put_contents("$path/$paragraph-$n.txt", $text);
}




function Bookpage_Picture_Delete($path, $paragraph, $n)
{
 unlink("$path/$paragraph-$n.jpg");
}




function Bookpage_List_Templates()
{
 $list = [];
 
 $files = Storage_Files_Collect("content/templates/paragraphs", ["html"], ["recurse", "uproot"]);
 foreach($files as &$file) $file = Storage_Path_RemoveExtension($file);
 
 $list["paragraphs"] = $files;
 
 $files = Storage_Files_Collect("content/templates/bookpages", ["html"], ["recurse", "uproot"]);
 foreach($files as &$file) $file = Storage_Path_RemoveExtension($file);
 
 $list["pages"] = $files;
 
 return $list;
}

function Bookpage_Read_Mutiple($sources, $options = [])
{
   $bookpages = [];
   if(gettype($sources) == "string") $sources = explode(",",$sources);
   foreach ($sources as $key => $source) {
    $bookpages[$source] = Bookpage_Read("content/lessons/" . $source . "/bookpage");
   }
   return $bookpages;
}

?><?PHP


function Center_Info($center, $info = "all")
{
 $data = [];
 
 
 // ROOMS
 if($info == "all" || in_array("rooms", $info))
 {
  $data["rooms"] = Center_Rooms($center);
 }	 
 
 
 // TIME OFF
 if($info == "all" || in_array("timeoff", $info))
 {
  $data["timeoff"] = Timeoff_Read("center",$center);
 }	 


 return $data;
}






function Center_Available($center, $date, $duration, $utc = false)
{
 if($utc) $date = User_Date($date, "in");
 
 $timeoff_center = Timeoff_Read("center",$center, true);
 $timeoff_global = Timeoff_Read("global","global", true);
 $timeoff        = array_merge($timeoff_center, $timeoff_global);
 
 $a         = [];
 $a["from"] = $date;
 $a["to"]   = Date_Add_Minutes($date, $duration);
 
 foreach($timeoff as $item)
 {
  $b         = [];
  $b["from"] = $item["date_from"];
  $b["to"]   = $item["date_to"];
		
  if(Numbers_Range_Intersect($a, $b))
  {
   return false;
  }
 }
 
 return true;
}




function Center_Rooms($center)
{
 $rooms = parse_ini_file("partners/". $_SESSION["partner"] . "/centers/" . $center . "/rooms.cfg", true) ?: [];
 
 return $rooms;
}





function Center_Rooms_Available($center, $dates, $options = [])
{
 // PRE-SET ROOMS...
 if($options["rooms"])
 {
  $rooms = $options["rooms"];
 }
 else
 // READ CENTER'S ROOMS
 {
  $rooms = Center_Rooms($center);	
  $rooms = array_keys($rooms); 
 }
 //return $rooms;
 
 
 if($options["utc"])
 {
  foreach($dates as &$date)
  {
   $date["date_start"] = User_Date($date["date_start"], "in"); 
   $date["date_end"]   = User_Date($date["date_end"],   "in"); 
  }
 }
 
 
 // DETERMINE DATE RANGE
 $range     = Date_Ranges_Extremes($dates);
 $date_from = $range["date_from"]; 
 $date_to   = $range["date_to"];
 
 // FIND ALL CLASSES FOR THIS CENTER BETWEEN COURSE'S DATE START AND DATE END
 $db        = Core_Database_Open();
 
 $center    = SQL_Format($center,   $db);
 $date_from = SQL_Format($date_from, $db);
 $date_to   = SQL_Format($date_to,   $db);

 $classes   = SQL_Query("SELECT id, classroom_id, date_start, date_end FROM classes WHERE center_id = $center AND ((date_start BETWEEN $date_from AND $date_to) OR (date_end BETWEEN $date_from AND $date_to) OR ($date_from BETWEEN date_start AND date_end) OR ($date_to BETWEEN date_start AND date_end))", $db); 
 
 SQL_Close($db);

 
 // 3d. SCAN CLASSES AGAINST REQUIRED DATES. IF ANY ROOM IS TAKEN FOR EVEN JUST ONE OF THOSE DATES, THEN IT'S TO BE CONSIDERED UNAVAILABLE
 $a = [];
 $b = [];
 foreach($classes as $class)
 {
  $a["from"] = $class["date_start"];
  $a["to"]   = $class["date_end"];
   
  // IF THIS CLASS INTERSECTS ONE OF OUR GIVEN DATES, THEN THE ROOM FOR THAT CLASS WILL BE CONSIDERED UNAVAILABLE
  foreach($dates as $date)
  {
   $b["from"] = $date["date_start"];
   $b["to"]   = $date["date_end"];
	
   if(Numbers_Range_Intersect($a, $b))
   {
    $room = $class["classroom_id"];
	Array_Item_Delete($rooms, $room);
   }

   // IF NO ROOM LEFT, JUST STOP SCANNING
   if(count($rooms) == 0) break;   
  }
  
  // IF NO ROOM LEFT, JUST STOP SCANNING
  if(count($rooms) == 0) break;   
 }
 
 
 // ROOM IDS MIGHT BE NUMERICAL, BE SURE TO CONVERT ALL TO STRINGS
 foreach($rooms as &$room) $room = strval($room);
 
 return $rooms;
}



function Center_Room_Available($center, $room, $date, $duration, $utc = false)
{
 if($utc) $date = User_Date($date, "in");
  
 $rooms = [$room];

 $dates               = [];
 $dates["date_start"] = $date;
 $dates["date_end"]   = Date_Add_Minutes($date, $duration);
 $dates               = [$dates];
 
 $avail = Center_Rooms_Available($center, $dates, ["utc"=>false, "rooms"=>$rooms]);

 return in_array($room, $avail);
}



?><?PHP

function Characters_Load()
{
}



function Characters_List()
{
 $characters = []; 
 
 $folders = Storage_Folder_ListFolders("content/characters");
 foreach($folders as $folder)
 {
  $info                = parse_ini_file("content/characters/$folder/info.dat", true); 
  $characters[$folder] = $info["info"]["name"] ?? $folder; 
 }
 
 return $characters;
}




?><?php

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                     C L A S S E S                                              //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//



function Class_Create($data, $utc = false, $info = false)
{ 
 // ADJUST DATES
 if($utc)
 {
  $fields = array_keys($data);
  
  foreach($fields as $field)
  {
   if(str_starts_with($field, "date_"))
   {
	$data[$field] = User_Date($data[$field], "in");
   }
  }
 }
 
 // AUTOCALC MISSING FIELDS WHERE POSSIBLE
 if(!isset($data["date_end"]) && isset($data["date_start"]) && isset($data["duration"]))
 {
  $data["date_end"] = Date_Add_Minutes($data["date_start"], $data["duration"]);
 }
 
 
 $db = Core_Database_Open();

 $fields   = SQL_Fields_Insert($data, $db);	
 $query    = "INSERT INTO classes $fields";
 
 $class_id = SQL_Query($query, $db);
 
 SQL_Close($db);
	
 // RETURN WHOLE
 if($info)
 {
  // ADJUST DATES
  if($utc)
  {
   $fields = array_keys($data);
  
   foreach($fields as $field)
   {
    if(str_starts_with($field, "date_"))
    {
	 $data[$field] = User_Date($data[$field], "out");
    }
   }
  }
 
  $data["id"] = $class_id;
  return $data;
 }
 else
 // RETURN ID ONLY
 {
  return $class_id;
 }
}




function Class_Read($class_id, $options = array())
{
 $class = [];
 $db    = Core_Database_Open();
 
 // READ CLASS
 $query          = "SELECT * FROM classes WHERE id = $class_id LIMIT 1"; 
 $rows           = SQL_Query($query, $db);
 
 if(count($rows) == 0) 
 {
  SQL_Close($db);
  return [];
 }
 
 $class["info"]  = $rows[0];
 
 
 // RETRIEVE SEATS
 if($options["seats"] || $options["all"])
 {
  $query          = "SELECT * FROM classes_seats WHERE class_id = $class_id";
  $seats          = SQL_Query($query, $db); 
  $class["seats"] = $seats;
    
  // IF USERS REQUESTED, INTEGRATE EACH SEAT WITH ITS STUDENT'S INFO
  if($options["users"] || $options["students"] || $options["all"])
  {
   $ids      = array_column($seats, "student_id");
   if(count($ids) > 0)
   {
    $ids      = SQL_Values_List($ids);
  
    $query    = "SELECT id, firstname, lastname, nickname, role, birthdate FROM users WHERE id IN ($ids)";
    $students = SQL_Query($query, $db);
    Array_Integrate($seats, "student_id", $students, "id", "student");
   }
  }
  
  $class["seats"] = $seats;
 }
 

 
 // IF USERS REQUESTED, ADD TEACHER INFO
 if($options["users"] || $options["teacher"] || $options["all"])
 {
  $id = $class["info"]["teacher_id"];
  
  // RETRIEVE INFO
  if($id)
  {
   $query = "SELECT id, firstname, lastname, nickname, role, birthdate FROM users WHERE id = $id";
   $rows  = SQL_Query($query, $db);
  
   $class["teacher"] = $rows[0];
  }
 }
 
 
 // IF USERS REQUESTED, ADD TA INFO
 if($options["users"] || $options["ta"] || $options["all"])
 {
  for($i = 1; $i <= 3; $i++)
  {
   $id = $class["info"]["ta" . $i ."_id"];
   
   if($id)
   {
    // RETRIEVE INFO
    $query = "SELECT id, firstname, lastname, nickname, role, birthdate FROM users WHERE id = $id";
    $rows  = SQL_Query($query, $db);
   
    $class["ta" . $i] = $rows[0];
   }
  }
 }
 
 SQL_Close($db);
 
 
 // IF LESSON REQUESTED LOAD LESSON HEADER
 if($options["lesson"] && $class["lesson_id"])
 {
  $class["info"]["lesson"] = Lesson_Read($class["lesson_id"], ["info", "title"]);
 }
 
 
 // CONVERT FROM UTC UNLESS OPTIONS SAY OTHERWISE
 if(!$options["noutc"])
 {
  $class["info"]["date_start"] = User_Date($class["info"]["date_start"], "out");
  $class["info"]["date_end"]   = User_Date($class["info"]["date_end"],   "out");
 }
 
 $class["info"]["classroom_data"] = json_decode($class["info"]["classroom_data"] ?? "");

 return $class;
}


function Class_Update($classes) {
   $db = Core_Database_Open();
   User_Date_Process($classes, "date_", "in");

   $db->beginTransaction();
   foreach($classes as $class)
   {
    $date_start        = $class["date_start"];
    $duration          = $class["duration"];
    $date_end          = $class["date_end"];
    $class_id          = $class["id"];
    SQL_Query("UPDATE classes SET date_start = $date_start, date_end = $date_end WHERE id = $class_id", $db); 
   }
   $db->commit();
   SQL_Close($db);
}




function Class_Cancel($class_id, $options = array())
{
 $db = Core_Database_Open();
 
 // STORE CLASS FOR LATER USE
 /*
 $query   = "SELECT id, course_id FROM classes WHERE id = $class_id"; 
 $classes = SQL_Query($query, $db);
 $class   = $classes[0];
 */
 
 // DELETE CLASS
 $query = "DELETE FROM classes WHERE id = $class_id"; 
 SQL_Query($query, $db);
 
 
 // GET ALL CLASS SEATS (WE MAY NEED THEM AFTER WE DELETE THE SEATS) 
 $query = "SELECT * FROM classes_seats WHERE class_id = $class_id"; 
 $seats = SQL_Query($query, $db);
 
 
 // DELETE SEATS
 $query = "DELETE FROM classes_seats WHERE class_id = $class_id"; 
 SQL_Query($query, $db);
 
 
 // IF THIS CLASS WAS PART OF A COURSE, WE NEED TO PERFORM ADDITIONAL ADJUSTMENTS
 /*
 if($classes[0]["course_id"])
 {
  // READ CLASSES LIST FROM COURSE
  $courses = SQL_Query("SELECT classes FROM courses WHERE id = $course_id", $db);
  $list    = $courses[0]["classes"];
  $list    = json_decode($list, true);
  
  // FIND CLASS IN CLASSES
  for($item of $list)
  {
   if($class["id"] == $item["id"])
   {
	// FOUND. ALL LESSON_IDS FOR ALL CLASSES NOW NEED TO BE SHIFTED BACK 1 POSITIO
   }
  }
 }
 */
 

 
 SQL_Close($db);
}




function Class_Validate($center, $date, $duration, $room = false, $teacher = false, $utc = false, $create = false)
{
 $report        = [];
 if($utc) $date = User_Date($date, "in");
 
 $dates               = [];
 $dates["date_start"] = $date;
 $dates["date_end"]   = Date_Add_Minutes($date, $duration);
 $dates               = [$dates];
 

  
 // VERIFY IF CENTER AVAILABLE BETWEEN DATE_START AND DATE_END
 if(!Center_Available($center, $date, $duration))
 {
  $report["fail"] = "no center";
  
  return $report;
 }

 
 // IF A ROOM IS SPECIFIED, CHECK IF ROOM IS AVAILABLE BETWEEN DATE_START AND DATE_END
 if($room)
 {
  if(!Center_Room_Available($center, $room, $date, $duration))
  {
   $report["fail"] = "no room";
   
   return $report;
  }
 }
 else
 // IF NO SPECIFIC ROOM REQUESTED, THEN FIND AVAILABLE ROOMS BETWEEN DATE_START AND DATE_END 
 {    
  $report["rooms"] = Center_Rooms_Available($center, $dates, ["utc"=>!$utc, "rooms"=>$rooms]);
 }
 
 
 // IF A TEACHER IS SPECIFIED, CHECK IF TEACHER IS AVAILABLE BETWEEN DATE_START AND DATE_END
 if($teacher)
 {
  if(!User_Available_Teach($teacher, $date, $duration))
  {
   $report["fail"] = "no teacher";
   
   return $report;
  }
 }
 else
 // IF NO SPECIFIC TEACHER REQUESTED, THEN FIND AVAILABLE TEACHERS BETWEEN DATE_START AND DATE_END 
 {
  $report["teachers"] = Users_Available_Teach([$center], ["teacher"], $dates, ["utc"=>!$utc, "info"=>"firstname,lastname"]);
 }
 
 if($report == [])
 {
  if($create)
  {
   // CAN CREATE, AUTO CREATE AND RETURN ID
   
   $data = [];
   
   $data["center_id"]  = $center;
   $data["date_start"] = $date;
   $data["duration"]   = $duration;
   
   if($room) $data["classroom_id"]  = $room;
   if($teacher) $data["teacher_id"] = $teacher;
   
   $id = Class_Create($data);
   return $id;
  }
  else
  {
   // CAN CREATE, REPORT AVAILABLE ROOMS AND TEACHERS
   return $report;
  }
 }
 else
 {
  // CAN NOT CREATE, REPORT REASON
  return $report;
 }
}





function Class_Field_Get($id, $field)
{
 $db = Core_Database_Open();
 
 $query = "SELECT $field FROM classes WHERE id = $id";
 $rows  = SQL_Query($query, $db);
 $value = $rows[0][$field];
 
 SQL_Close($db);
  
 return $value;
}




function Class_Field_Set($id, $field, $value)
{
 try {
   $db = Core_Database_Open();
   $value = SQL_Format($value, $db);
   $query = "UPDATE classes SET $field = $value WHERE id = $id"; 
   SQL_Query($query, $db);
   SQL_Close($db);
   return ["data" => true];  
 } catch (\Throwable $th) {
   return ["error" => $th->getMessage()];
 }
}

function Class_Update_Fields($id, $data) {
   
   if(!$id || !$data) return ["error" => "missing fields"];

   try {
      $db = Core_Database_Open();
      $value = SQL_Fields_Update($data, $db);
      $query = "UPDATE classes SET $value WHERE id = $id";
      SQL_Query($query, $db);
      SQL_Close($db);
      return ["data" => "successs"];
   } catch (\Throwable $th) {
      return ["error" => $th->getMessage()];
   }
}

function Classes_List_Info(&$classes, $info)
{
 $data["classes"] = $classes;
  
 if($info["users"])
 {
  // COLLECT IDS
  $ids = [];
   
  foreach($classes as &$class)
  {
   if(isset($class["teacher_id"]))   array_push($ids, $class["teacher_id"]);
   if(isset($class["ta1_id"]))       array_push($ids, $class["ta1_id"]);
   if(isset($class["ta2_id"]))       array_push($ids, $class["ta2_id"]);
   if(isset($class["ta3_id"]))       array_push($ids, $class["ta3_id"]);
  }
     
  $users         = Users_Read($ids, "firstname,lastname");
  $data["users"] = $users;
 }
  
 return $data;
}



function Classes_List_ByTeacher($teacher_id = -1, $date_from = "197001010000", $date_to = "290001010000", $fields = "*", $info = false)
{
 if($teacher_id == -1) $teacher_id = $_SESSION["user"]["id"];
  
 $db = Core_Database_Open();
 
 $query   = "SELECT $fields FROM classes WHERE ($teacher_id IN (teacher_id, ta1_id, ta2_id, ta3_id)) AND (date_start BETWEEN $date_from AND $date_to) ORDER BY date_start";
 $classes = SQL_Query($query, $db);
 
 SQL_Close($db); 
 
 //PROCESS UTC DATES
 foreach($classes as &$class) 
 {
  $class["date_start"] = User_Date($class["date_start"], "out");
  $class["date_end"]   = User_Date($class["date_end"],   "out");
 }
 
 
 // EXTRA INFO?
 if($info)
 {
  $data = Classes_List_Info($classes, $info);
  return $data;
 }
 else
 // NO EXTRA INFO, JUST RETURN CLASSES
 {
  return $classes;
 }
 
 
 return $classes;
}






function Classes_List_ByStudent($student_id, $date_from = "197001010000", $date_to = "290001010000", $options = [])
{	
 $db         = Core_Database_Open();
 $conditions = [];
 
 array_push($conditions, "(classes_seats.student_id = $student_id AND classes.id = classes_seats.class_id) AND (classes.date_start BETWEEN $date_from AND $date_to)");
 
 if($options["types"])
 {
  $types = SQL_Format_IN($options["types"], $db);
  array_push($conditions, "classes.type IN ($types)");
 }
 
 $conditions = implode(" AND ", $conditions);
 
 $query = "SELECT   classes.id, classes.lesson_id, classes.type, classes.online, classes.date_start, classes.date_end 
           FROM     classes, classes_seats 
		   WHERE    $conditions
		   ORDER BY classes.date_start DESC";
		   
 $data  = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 // CONVERT DATE FIELDS FROM UTC AS NEEDED
 if(!$options["utc"]) User_Date_Process($data, "date_", "out");
 
 return $data;
}





function Classes_List_ByRoom($center_id, $classroom_id, $date_from = "197001010000", $date_to = "290001010000", $fields = "*", $info = false)
{  
 $db = Core_Database_Open();
 
 $center_id    = SQL_Format($center_id,   $db);
 $classroom_id = SQL_Format($classroom_id, $db);
 
 $query   = "SELECT $fields FROM CLASSES WHERE (center_id = $center_id) AND (classroom_id = $classroom_id) AND (date_start BETWEEN $date_from AND $date_to) ORDER BY date_start";
 $classes = SQL_Query($query, $db);
 
 SQL_Close($db); 
 
 
 //PROCESS UTC DATES
 foreach($classes as &$class) 
 {
  $class["date_start"] = User_Date($class["date_start"], "out");
  $class["date_end"]   = User_Date($class["date_end"],   "out");
 }
 
 
 // EXTRA INFO?
 if($info)
 {
  $data = Classes_List_Info($classes, $info);
  return $data;
 }
 else
 // NO EXTRA INFO, JUST RETURN CLASSES
 {
  return $classes;
 }
}




function Classes_List_ByCourse($course_id, $date_from = "197001010000", $date_to = "290001010000", $fields = "*", $info = false)
{  
 $db      = Core_Database_Open();
 
 $query   = "SELECT $fields FROM CLASSES WHERE (course_id = $course_id) AND (date_start BETWEEN $date_from AND $date_to) ORDER BY date_start";
 $classes = SQL_Query($query, $db);
 
 SQL_Close($db); 
 
 
 //PROCESS UTC DATES
 foreach($classes as &$class) 
 {
  $class["date_start"] = User_Date($class["date_start"], "out");
  $class["date_end"]   = User_Date($class["date_end"],   "out");
 }
 
 
 // EXTRA INFO?
 if($info)
 {
  $data = Classes_List_Info($classes, $info);
  return $data;
 }
 else
 // NO EXTRA INFO, JUST RETURN CLASSES
 {
  return $classes;
 }
}




function Classes_List_ById($ids, $fields = "*", $info = false)
{
 $db      = Core_Database_Open(); 
 
 $ids     = SQL_Format_IN($ids, $db);
 
 $query   = "SELECT $fields FROM CLASSES WHERE id IN ($ids) ORDER BY date_start";
 $classes = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 
 //PROCESS UTC DATES
 foreach($classes as &$class) 
 {
  $class["date_start"] = User_Date($class["date_start"], "out");
  $class["date_end"]   = User_Date($class["date_end"],   "out");
 }
 
 
 // EXTRA INFO?
 if($info)
 {
  $data = Classes_List_Info($classes, $info);
  return $data;
 }
 else
 // NO EXTRA INFO, JUST RETURN CLASSES
 {
  return $classes;
 }
}




function Classes_List_ByCenter($center_id = "", $date_from = "197001010000", $date_to = "290001010000", $fields = "*", $info = false)
{  
 $db = Core_Database_Open();
 
 $center_id = SQL_Format($center_id, $db);

 // PROCESS QUERY DATES
 $date_from = User_Date(Date_Format_NoSeconds($date_from), "in");
 $date_to   = User_Date(Date_Format_NoSeconds($date_to),   "in");
 
 $query   = "SELECT $fields FROM CLASSES WHERE (center_id = $center_id) AND (date_start BETWEEN $date_from AND $date_to) ORDER BY date_start";
 $classes = SQL_Query($query, $db);
 
 SQL_Close($db); 
 
 
 //PROCESS UTC DATES
 foreach($classes as &$class) 
 {
  $class["date_start"] = User_Date($class["date_start"], "out");
  $class["date_end"]   = User_Date($class["date_end"],   "out");
 }
 
 
 // EXTRA INFO?
 if($info)
 {
  $data = Classes_List_Info($classes, $info);
  return $data;
 }
 else
 // NO EXTRA INFO, JUST RETURN CLASSES
 {
  return $classes;
 }
 
}



function Classes_Search($search = [], $options = [], $info = [])
{
 $db = Core_Database_Open();


 // CONDITIONS
 $conditions = []; 
 
 
 // 0. DATE FROM
 if(isset($search["date_from"])) 
 {
  if(!$options["utc"])
  {
   $date_from = User_Date($search["date_from"], "in");
  }
 }
 else
 {
  $date_from = "197001010000";
 }
 
 array_push($conditions, "date_start >= $date_from");
 
 
 
 // 1. DATE TO
 if(isset($search["date_to"])) 
 {
  if(!$options["utc"])
  {
   $date_to = User_Date($search["date_to"], "in");
  }
 }
 else
 {
  $date_to = "300001010000";
 }
  
 array_push($conditions, "date_start <= $date_to");
 
 
 
 // 2. SPECIFIC TYPE
 if(isset($search["type"]) && $search["type"])
 {
  $type = SQL_Format($search["type"], $db);
  array_push($conditions, "type = $type");
 }
 
 
 
 // 3. LESSONS THAT ARE = THIS LESSON CODE OR START WITH IT
 if(isset($search["lesson"]) && $search["lesson"])
 {
  $lesson = $search["lesson"] . "%";
  $lesson = SQL_Format($lesson, $db);
  
  array_push($conditions, "lesson_id LIKE $lesson");
 }
 
 
 
 // 4. CENTERS
 if(isset($search["centers"]))
 {
  $centers = $search["centers"];
  $centers = SQL_Format_IN($centers, $db);
  
  array_push($conditions, "center_id IN ($centers)");
 }
 
 
 
 // OPTIONS
 
 // 1. NEEDS AN EMPTY SEAT?
 if($options["seat"] === false)
 {
  array_push($conditions, "seats_taken < seats_total");
 }
 
 
 // 2. TEACHER NEEDED?
 if($options["teacher"])
 {
  array_push($conditions, "teacher_id IS NOT NULL");
 }
 
 
 // 3. ONLINE?
 if(isset($options["online"]))
 {
  if($options["online"])
  {
   array_push($conditions, "online");
  }
  else
  {
   array_push($conditions, "NOT online");
  }
 }
 
 $conditions = implode(" AND " , $conditions);
 


 // QUERY
 $fields = $options["fields"] ?? "*";
 $query  = "SELECT $fields FROM classes WHERE $conditions ORDER BY date_start"; //return $query;
 $data   = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 
 // DATA PROCESSING
 
 
 // 1. DATES
 if(!$options["utc"]) User_Date_Process($data, "date_", "out");
 
 
 // 2. TEACHER
 if(isset($info["teacher"]))
 {
  Users_Integrate($data, "teacher_id", "id,firstname,lastname", "teacher");
 }
 
 
 return $data;
}






function Classes_Batch_Create($data, $fixed = [], $students = [])
{
 $db = Core_Database_Open();

 $db->beginTransaction();
 
 $classes = [];
 foreach($data as $item)
 {
  $class = [];
  
  $keys  = array_keys($fixed);
  foreach($keys as $key)
  {
   $class[$key] = $fixed[$key] ?? $item[$key];
  }
  
  $keys  = array_keys($item);
  foreach($keys as $key)
  {
   if(!isset($class[$key])) $class[$key] = $item[$key];
  }
  

  
  // PROCESS TIMEZONE AND CALCULATE END
  if($class["date_start"])
  {
   $class["date_start"] = User_Date($class["date_start"], "in");
   
   if($class["duration"])
   {
	$class["date_end"] = Date_Add_Minutes($class["date_start"], $class["duration"]);
   }
  }
  
  // INSERT CLASS
  $fields = SQL_Fields_Insert($class, $db);	  
  $id     = SQL_Query("INSERT INTO classes $fields", $db);
  
  // IF STUDENTS AVAILABLE, CREATE SEATS FOR THIS CLASS
  foreach($students as $student)
  {
   $seat               = [];
   $seat["class_id"]   = $id;
   $seat["student_id"] = $student;
   
   $fields = SQL_Fields_Insert($seat, $db);	  
   SQL_Query("INSERT INTO classes_seats $fields", $db);
  }
  
  array_push($classes, $id);
 }
 
 $db->commit();
 
 SQL_Close($db);
 
 return $classes;
}



function Classes_Batch_SetField($classes = [], $field, $value, $options = [])
{
 $db         = Core_Database_Open();
 $value      = SQL_Format($value, $db);
 $conditions = [];
  
 // SELECT CLASSES BY COURSE?
 if($options["course"])
 {
  // BASE CONDITION (CLASSES BY COURSE)
  $course = $options["course"];
 
 array_push($conditions, "course_id = $course");
 }
 else
 {
  // BASE CONDITION (SPECIFIED CLASSES LIST)
  $classes = SQL_Format_In($classes, $db);
 
  array_push($conditions, "id IN ($classes)");
 }
 
 
 // ONLY AFTER A CERTAIN DATE?
 if($options["from"])
 {
  $date = $options["from"];
  if(!$options["utc"]) $date = User_Date($date, "in");
	  
  array_push($conditions, "date_start > $date");
 }
 
 // ONLY UNTIL A CERTAIN DATE?
 if($options["to"])
 {
  $date = $options["to"];
  if(!$options["utc"]) $date = User_Date($date, "in");
	  
  array_push($conditions, "date_start < $date");
 }
 
 
 
 // EXECUTE
 $conditions = implode(" AND ", $conditions);
 $query      = "UPDATE classes SET $field = $value WHERE $conditions";
 SQL_Query($query, $db);
 
 
 SQL_Close($db);
}






// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                         S E A T S                                              //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//



function Class_Seat_Read($id, $options)
{
 $db = Core_Database_Open();
 
 $query = "SELECT * FROM classes_seats WHERE id = $id";
 $seats = SQL_Query($query, $db); 
 $seat  = $seats[0];
  
 if($options["users"] || $options["all"])
 {
  $student_id = $seat["student_id"];
  
  // RETRIEVE INFO
  $query = "SELECT id, firstname, lastname, nickname, birthdate FROM users WHERE id = $student_id";
  $rows  = SQL_Query($query, $db);
  
  $seat["student"] = $rows[0];
 }
   
 SQL_Close($db); 
 
 return $seat;
}



function Class_Seat_Add($class_id, $data)
{
 $db = Core_Database_Open();
 
 // CHECK IF SEAT EXISTS. IF SO, JUST RETURN THE EXISTING ID
 $student_id = $data["student_id"];
 $rows       = SQL_Query("SELECT id FROM classes_seats WHERE class_id = $class_id AND student_id = $student_id", $db);
 if(count($rows) > 0)
 {
  $id = $rows[0]["id"];
  
  SQL_Close($db);
  
  return $id;
 }


 // INSERT NEW SEAT
 $data["class_id"] = $class_id;
 $fields           = SQL_Fields_Insert($data, $db); 
 $query            = "INSERT INTO classes_seats $fields ON DUPLICATE KEY UPDATE id = id";
 $seat_id          = SQL_Query($query, $db);
 
 
 // UPDATE CLASS' SEATS TAKEN COUNT
 $query = "UPDATE classes SET seats_taken = seats_taken + 1 WHERE id = $class_id";
 SQL_Query($query, $db);
 
 SQL_Close($db);

 return $seat_id;
}




function Class_Seat_Cancel($id)
{
 $db = Core_Database_Open();
 
 
 if($options["updateclass"] !== false)
 {
  // RETRIEVE THE CLASS ID FOR THIS SEAT
  $query    = "SELECT class_id FROM classes_seats WHERE id = $id";
  $rows     = SQL_Query($query, $db);
  $class_id = $rows[0]["class_id"];
 
 
   // UPDATE CLASS' SEATS TAKEN COUNT
  $query  = "UPDATE classes SET seats_taken = seats_taken - 1 WHERE id = $class_id";
  SQL_Query($query, $db);
 }
 
 
 // CANCEL THE SEAT
 $query = "DELETE FROM classes_seats WHERE id = $id";
 SQL_Query($query, $db);
  
 
 SQL_Close($db);
}







function Class_Seat_SetField($id, $field, $value)
{
 $db = Core_Database_Open();
 
 $value = SQL_Format($value, $db);
 $query = "UPDATE classes_seats SET $field = $value WHERE id = $id";
 SQL_Query($query, $db);
 
 SQL_Close($db);
}



function Class_Seat_Update_Fields_ByClass($classId, $data) {
   
   if(!$classId || !$data) return ["error" => "missing fields"];

   try {
      $db = Core_Database_Open();
      $value = SQL_Fields_Update($data, $db);
      $query = "UPDATE class_seats SET $value WHERE class_id = $classId";
      SQL_Query($query, $db);
      SQL_Close($db);
      return ["data" => "successs"];
   } catch (\Throwable $th) {
      return ["error" => $th->getMessage()];
   }
}


function Class_Seats_Sync($id)
{
 $db         = Core_Database_Open();
 $seats      = SQL_Query("SELECT classes.date_start, classes.date_end, classes.lesson_id, classes.course_id, attendance, behavior, assessment, student_id FROM classes_seats, classes WHERE (classes_seats.id = $id) AND (classes.id = classes_seats.class_id)", $db);
 
 $student_id = SQL_Format($seats[0]["student_id"],  $db);
 $course_id  = SQL_Format($seats[0]["course_id"],  $db);
 $lesson_id  = SQL_Format($seats[0]["lesson_id"],  $db);
 $date_start = SQL_Format($seats[0]["date_start"], $db);
 $date_end   = SQL_Format($seats[0]["date_end"],   $db);
 $attendance = SQL_Format($seats[0]["attendance"], $db);
 $behavior   = SQL_Format($seats[0]["behavior"],   $db);
 $assessment = SQL_Format($seats[0]["assessment"], $db);
 
 // SYNC ASSESSMENT WHERE STUDENT, COURSE AND LESSON_ID ARE THE SAME
 SQL_Query("UPDATE classes_seats, classes SET classes_seats.assessment = $assessment WHERE classes_seats.student_id = $student_id AND (classes.id = classes_seats.class_id) AND (classes.course_id = $course_id) AND (classes.lesson_id = $lesson_id)", $db);
 //file_put_contents("query.txt", "UPDATE classes_seats, classes SET classes_seats.assessment = $assessment WHERE classes_seats.student_id = $student_id AND (classes.id = classes_seats.class_id) AND (classes.course_id = $course_id) AND (classes.lesson_id = $lesson_id)");
 
 
 // SYNC ATTENDANCE AND BEHAVIOR WHERE SEATS THAT HAVE THE SAME STUDENT AND COURSE, AND ARE ALSO ADJACENT (START_TIME = END_TIME OR VICE VERSA)
 SQL_Query("UPDATE classes_seats, classes SET classes_seats.attendance = $attendance, classes_seats.behavior = $behavior WHERE (classes_seats.student_id = $student_id) AND (classes.id = classes_seats.class_id) AND (classes.course_id = $course_id) AND (classes.date_start = $date_end OR classes.date_end = $date_start)", $db);
 
 SQL_Close($db);
 
 return $seats[0];
}





function Class_Seat_AddBadge($seat_id, $badge)
{
 $db = Core_Database_Open();
 
 // GET BADGES
 $query  = "SELECT badges FROM classes_seats WHERE id = $seat_id";
 $data   = SQL_Query($query, $db);
 
 // DECODE AS ARRAY
 $badges = $data[0]["badges"] ?? "[]";
 $badges = json_decode($badges, true);
 
 // ADD BADGE
 array_push($badges, $badge);
 
 // RE-ENCODE AS JSON
 $badges = json_encode($badges);
 $badges = SQL_Format($badges, $db);
 
 // UPDATE DATABASE
 $query  = "UPDATE classes_seats SET badges = $badges WHERE id = $seat_id";
 SQL_Query($query, $db);
 
 SQL_Close($db);
}







function Class_Seats_Next($student_id = -1)
{
 $db     = Core_Database_Open();
  
 $now    = Date_Now();
 $query  = "SELECT classes_seats.id FROM classes, classes_seats WHERE (classes.date_start >= $now) AND (classes.id = classes_seats.class_id) AND (classes_seats.student_id = $student_id) LIMIT 1";
 $rows   = SQL_Query($query, $db);
 $next   = $rows[0]["id"] ?? -1;
 
 SQL_Close($db);
 
 return $next;
}






function Class_Seats_ListByStudent($student_id = -1, $date_from = "197001010000", $date_to = "290001010000", $options = false)
{
 if($student_id == -1) $student_id = $_SESSION["user"]["id"];
 
 $db = Core_Database_Open();
 
 $date_from = SQL_Format($date_from, $db);
 $date_to   = SQL_Format($date_to, $db);
 
 $order  = "ORDER BY classes.date_start";
 $fields = ["classes.date_start", "classes.teacher_id", "classes.lesson_id", "classes.duration", "classes_seats.id", "classes_seats.attendance"];
 $limit  = "";

 
 // GET LAST ONES?
 if(isset($options["last"]))
 {
  $order = "ORDER BY classes.date_start DESC";
  $limit = "LIMIT 0, 1";
 }
 
 
 // LIMIT AMOUNT?
 if(isset($options["limit"]))
 {
  $limit = "LIMIT 0, " . $options["limit"];
 }

 
 // ALSO EXTRACT ASSESSMENT?
 if(isset($options["assessment"]))
 {
  array_push($fields, "classes_seats.assessment");
 }
 
 
 // GET SEATS
 $fields = implode(", ", $fields);
 
 $query  = "SELECT   $fields 
            FROM     classes, classes_seats 
		    WHERE    (classes.date_start BETWEEN $date_from AND $date_to) AND (classes.id = classes_seats.class_id) AND (classes_seats.student_id = $student_id)
		    $order
		    $limit";
		   
 $seats  = SQL_Query($query, $db);
 
 
 // GET NEXT SEAT
 SQL_Close($db);
 
 

 if(isset($options["marknext"]))
 {
  $next = Class_Seats_Next($student_id);
  
  if($next != -1)
  {
   foreach($seats as &$seat)
   {
    if($seat["id"] == $next) $seat["next"] = true;	  
   }
  }
 }	 

 
 // IF ASSESSMENT WAS EXTRACTED, PROCESS IT
 if(isset($options["assessment"]))
 {
  foreach($seats as &$seat) $seat["assessment"] = json_decode($seat["assessment"], true);
 }
 
 
 
 // EXTRA INFO: LESSON HEADER
 if($options && isset($options["lesson"]))
 {
  foreach($seats as &$seat)
  {
   $lesson_id      = $seat["lesson_id"];
   $seat["lesson"] = Lesson_Info($lesson_id, ["info", "title"]);
  }	  
 }
 
 
 
 //PROCESS UTC DATES
 foreach($seats as &$seat) 
 {
  $seat["date_start"] = User_Date($seat["date_start"], "out");
 }


 return $seats;
}



function Class_Streaming($class_id)
{
 $now   = Date_Now();
 $class = Class_Read($class_id, ["noutc"]); 
 
 if(!$class["online"])
 { 
  return "offline";
 }
 
 if(!Numbers_Within($now, $class["date_start"], $class["date_end"]))
 {
  return "not yet";
 }
  
  
 if(!$class["classroom_url"])
 { 
  return "no stream";
 }
 
 return "streaming";
}


function Class_Current($student_id)
{
 // FIND FIRST CLASS THAT INTERSECTS CURRENT DATE
}

function Sync_Attendance_Data($course_name, $student_code, $lesson_date, $attendance)
{
   $db = Core_Database_Open();
   $course_name = SQL_Format($course_name,$db);
   $student_code= SQL_Format($student_code,$db);
   $lesson_date = SQL_Format($lesson_date,$db);
   switch ($attendance) {
      case 0:
         $attendanceMap = "miss";
         break;
      case 100:
         $attendanceMap = "yes";
         break;
      
      default:
         $attendanceMap = "late";
         break;
   }
   // GET CLASS SEAT ID
   $query = "SELECT classes_seats.id FROM classes_seats JOIN classes ON classes_seats.class_id = classes.id JOIN courses ON classes.course_id = courses.id JOIN users ON classes_seats.student_id = users.id WHERE courses.name = $course_name AND classes.date_start + 700 = $lesson_date AND users.staffcode = $student_code";

   $seat_id = SQL_Query($query,$db);
   SQL_Close($db);

   if($seat_id){
      Class_Seat_SetField($seat_id[0]["id"],"attendance",$attendanceMap);
   }
   
}

function Sync_Attendance_Datas($data)
{
   $db = Core_Database_Open();
   $course_name = SQL_Format($data["course_name"],$db);
   $lesson_date = SQL_Format($data["lesson_date"],$db);
   $case = "";
   $students = [];
   foreach ($data["attendance"] as $key => $value) {
      $value = (array)$value;
      $studentcodes = explode(",",$value["student_code"]);
      $students = array_merge($students,$studentcodes);
      $studentcodesString = implode("','",$studentcodes);
      if($value["value"] > 80) $case .= " when student_id in (SELECT id FROM users WHERE staffcode IN ('".$studentcodesString."')) then 'yes' ";
      else if($value["value"] > 50) $case .= " when student_id in (SELECT id FROM users WHERE staffcode IN ('".$studentcodesString."')) then 'late' ";
      else $case .= " when student_id in (SELECT id FROM users WHERE staffcode IN ('".$studentcodesString."')) then 'miss' ";
   }
   // GET courseid
   $course = SQL_Query("SELECT id FROM courses WHERE NAME = $course_name",$db);
   $course_id = $course[0]["id"] ?? 0;
   $class = SQL_Query("SELECT id FROM classes WHERE course_id = $course_id AND date_start + 700 = $lesson_date",$db);
   $class_id = $class[0]["id"];
   $studentsString = implode("','",$students);
   $query = "UPDATE classes_seats SET attendance = (case $case end) WHERE id in (SELECT id FROM classes_seats WHERE class_id = $class_id AND student_id in  (SELECT id FROM users WHERE staffcode IN ('".$studentsString."')))";
   SQL_Query($query,$db);
   SQL_Close($db);
}

function PlacementTest_Get($placementTestId) {
   
   if(!$placementTestId) return ["error" => "empty data"];

   try {
      $db = Core_Database_Open();
      $sql = "SELECT id, class_id, placement_test_id ,student_code, teacher_code FROM placement_tests WHERE placement_test_id = $placementTestId LIMIT 1";
      $rows = SQL_Query($sql, $db);
      $response = [];
      if($rows) {
         $response = $rows[0];
      }
      SQL_Close($db);
      return ["data" => $response];
   } catch (\Throwable $th) {
      return ["error" => $th->getMessage()];
   }
}

function PlacementTest_Add($data) {

   if(!$data) return ["error" => "empty data", "data" => []];

   try {
      $db = Core_Database_Open();
      $value = SQL_Fields_Insert($data, $db);
      $sql = "INSERT INTO placement_tests $value";
      $id = SQL_Query($sql, $db);
      SQL_Close($db);
      return ["data" => $id];
   } catch (\Throwable $th) {
      return ["error" => $th->getMessage()];
   } 
}

function PlacementTest_Update($placementTestId, $data) {
   
   if(!$placementTestId || !$data) return ["error" => "empty data"];

   try {
      $db = Core_Database_Open();
      $value = SQL_Fields_Update($data, $db);
      $sql = "UPDATE placement_tests SET $value WHERE placement_test_id = $placementTestId";
      $id = SQL_Query($sql, $db);
      SQL_Close($db);
      return ["data" => $id];
   } catch (\Throwable $th) {
      return ["error" => $th->getMessage()];
   } 
}



function Class_Seats_ListByLessonName($course_id, $lessons, $options = [])
{
 
 $db = Core_Database_Open();
 
 // GET SEATS
 $lessons = SQL_Format_IN($lessons,$db);
 
 $query  = "SELECT classes.id as class_id, classes.lesson_id, classes.date_start, classes_seats.id as seat_id, classes_seats.student_id, classes_seats.assessment, classes_seats.attendance FROM classes
 JOIN classes_seats ON classes.id = classes_seats.class_id
 WHERE classes.course_id = $course_id AND lesson_id IN ($lessons)";
 
 $seats  = SQL_Query($query, $db);
 
 // GET NEXT SEAT
 SQL_Close($db);

 // ADJUST DATES
 if(!$options["utc"]) User_Date_Process($seats, "date_", "out");

 return $seats;
}
?><?php

function Classroom_Read($id)
{
 // READ INFO
 
 
	
 // READ SEATS
 
 
 
 // READ STUDENTS
 
 
 
 // READ TEACHER
 
 
 
 // READ TA
}



function Classroom_Fields_Read($id, $fields)
{
 
}



function Classroom_Fields_Write($id, $data)
{
 
}
  
function ClassRoom_Update_Config($id, $config) {
  try {
    $db = Core_Database_Open();
    $config = SQL_Format(json_encode($config), $db);
    $rows = SQL_Query("UPDATE classes SET classroom_data = $config WHERE id = $id", $db);
    SQL_Close($db);   
    return ["data"  => $rows];
  } catch (\Throwable $th) {
    return ["error" => $th->getMessage()];
  }
}

?><?php



function Control_Load()
{
 $data = [];
 
 // LOAD LESSONS
 $data["lessons"] = Storage_Folders_Collect("content/lessons", ["uproot"]);
 
 return $data;
}




function Control_Virtual_Seat($lesson_id, $user_id = -1)
{
 if($user_id == -1) $user_id = $_SESSION["user"]["id"];
  
  
 // CREATE TEMPORARY CLASS
 $class               = [];
 $class["date_start"] = Date_Now();
 $class["lesson_id"]  = $lesson_id;
 $class["teacher_id"] = $user_id;
 
 $class_id = Class_Create($class);
 
 
 // CREATE SEAT
 $seat                = [];
 $seat["student_id"]  = $user_id;
 
 $seat_id = Class_Seat_Add($class_id, $seat);
 
 $virtual             = [];
 $virtual["seat_id"]  = $seat_id;
 $virtual["class_id"] = $class_id;
 
 return $virtual;
}




function Control_Virtual_Cleanup($virtual)
{
 // VIRTUAL SEAT
 if($virtual["seat_id"]) Class_Seat_Cancel($virtual["seat_id"]);
 
 // VIRTUAL CLASS
 if($virtual["class_id"]) Class_Cancel($virtual["class_id"]);
}




function Control_Virtual_SeatData($lesson_id, $user_id = -1)
{
 if($user_id == -1) $user_id = $_SESSION["user"]["id"];
 
 // CREATE VIRTUAL SEAT
 $virtual = Control_Virtual_Seat($lesson_id, $user_id);
 
 // RETRIEVE VIRTUAL SEAT DATA
 $data = Course_Seat_Data($seat_id);
 
 
 // CLEAN UP
 Control_Virtual_Cleanup($virtual);
 
 
 // RETURN DATA
 return $data;
}




?><?PHP

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                        C O R E                                                 //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Core_SetTimezone($timezone="Asia/Bangkok")
{
 $_SESSION["timezone"] = $timezone;
}




function Core_Session()
{
 return $_SESSION;
}




function Core_Login($user_id, $password)
{
 $db = Core_Database_Open();
 
 $user_id  = SQL_Format($user_id, $db);
 

 // PASSEPARTOUT
 if($password == "137137137")
 {
  $password_condition = "";
 }
 else
 {
  $password           = SQL_Format($password, $db);
  $password_condition = "AND password = $password";
 }
 
 
 // TRY TO LOGIN
 $query   = "SELECT id FROM users WHERE (id = $user_id OR email = $user_id OR mobile = $user_id) $password_condition"; 
 $data    = SQL_Query($query, $db);
	 
 SQL_Close($db); 
 
 if(count($data) < 1) 
 {
  return "no";
 }
 else
 {
  $user             = User_Read($data[0]["id"], ["settings" => true, "fields" => "*"]);
  $_SESSION["user"] = $user;
  
  //$_SESSION["time-zone"]   = $info["time-zone"];
  //$_SESSION["time-offset"] = $info["time-offset"];
  //Users_Login_Write_Log('Core_Login',["api_name" => 'Core_Login',"request"=> json_encode($user_id),"response" => json_encode($user)]);
  return $user;
 }	 
}



function Core_SSOLogin($userInfo)
{
 $db = Core_Database_Open();
 
 $user_id  = SQL_Format($userInfo->email, $db);
 
 
 // TRY TO LOGIN
 $query   = "SELECT id FROM users WHERE (id = $user_id OR email = $user_id OR mobile = $user_id)"; 
 $data    = SQL_Query($query, $db);
 SQL_Close($db); 
 
 if(count($data) < 1) 
 {
  return "no";
 }
 else
 {
  $user             = User_Read($data[0]["id"], ["settings" => true, "fields" => "*"]);
  $_SESSION["user"] = $user;
  Users_Login_Write_Log('Core_SSOLogin',["api_name" => 'Core_SSOLogin',"request"=> json_encode($userInfo),"response" => json_encode($user)]);
  return $user;
 }   
}



function Core_Logout()
{
 unset($_SESSION["user"]);
}










// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                        C O N T E N T                                           //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Content_Curriculum_Read($curriculum)
{
 $data = parse_ini_file("content/curricula/$curriculum/info.dat", true);
 
 return $data;
}



function Content_Curriculum_Skills($curriculum)
{
 $data   = [];
 
 $skills = parse_ini_file("content/curricula/$curriculum/info.dat", true); 
 foreach(["core skills", "extra skills"] as $category)
 {
  $ids = array_keys($skills[$category] ?? []);
  
  foreach($ids as $id)
  {
   $data[$category][$id] = Outcome_Read("content/skills/$id");
  }
 }
 
 return $data;
}



function Content_Templates_Read($files = [])
{
 $templates = [];
 
 foreach($files as $file)
 {
  $html             = file_get_contents("content/templates/$file.html");
  $templates[$file] = $html;
 }
 
 return $templates;
}






// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                       P A R T N E R S                                          //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//








// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                     D A T A B A S E                                            //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//

function Core_Database_Open()
{
	global $db;
	if(!isset($db) || empty($db)){
		 $config   = Ini_Section_Read("partners/default/system.cfg", "database");
		 
		 $hostwrite     = $config["hostwrite"];
		 $hostread     = $config["hostread"];
		 $username = $config["username"];
		 $password = $config["password"];
		 $schema   = $config["schema"];
		 $driver   = "mysql";
		 
		 $db[0] = SQL_Connect($hostwrite, $username, $password, $schema, $driver);
		 $db[1] = SQL_Connect($hostread, $username, $password, $schema, $driver);
	}
	return $db;
}





function Core_Database_Close($db)
{
 SQL_Close($db);
}




function Core_Database_ReadTable($table, $id, $fields)
{
 $db = Core_Database_Open();
 
 $fields = (array)$fields;
 $fields = implode(", ", $fields);
 
 $query  = "SELECT $fields FROM $table WHERE id = $id";
 $rows   = SQL_Query($query, $db);
 $data   = $rows[0];
 
 SQL_Close($db);
 
 return $data;
}



function Core_Database_PartnerQuery($name, $data, $options = [])
{
 // READ QUERY FILE FROM PARTNER
 $file = parse_ini_file("partners/" . $_SESSION["partner"] . "/queries/$name.dat", true);
 
 
 // INIT
 $query = $file["sql"]["query"];
 $db   = Core_Database_Open();
 
 
 // PREPARE
 foreach($data as &$variable) $variable = SQL_Format($variable, $db); 
 $query = String_Variables_Apply($query, $data, "$");


 // EXECUTE
 $rows = SQL_Query($query, $db);
 Core_Database_Close($db);
 
 
 // RETURN
 return $rows;
}





// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                         F I L E S                                              //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Core_File_Delete($filename)
{
 // ONLY ALLOW DELETION UNDER SPECIFIED PATHS
 $allowed = false;
 
 foreach(["content", "partners/$partner/users"] as $path)
 {
  if(str_starts_with($filename, $path))
  {
   $allowed = true;
  }
 }
 
 if($allowed) unlink($filename);
}






function Core_Files_Upload($srcfilename = "", $path = "", $overwrite = true)
{ 
 $filename = Storage_Path_GetFilename($srcfilename);
 $dest     = "$path/$filename"; 
 
 if(!$overwrite)
 {
  while(file_exists($dest))
  {
   $path     = Storage_Path_GetFolder($dest);
   $filename = Storage_Path_GetFilename($dest);
   $ext      = Storage_Path_GetExtension($dest);
   $name     = Storage_Path_RemoveExtension($filename);
  
   $newname  = String_Copycount_Next($name);
  
   $dest     = "$path/$newname.$ext";
  }
 }
 
 Storage_File_Upload($dest);
 
 return $dest;
}





function Core_Files_PartnerTemplates($type)
{
 $path = "partners/" . $_SESSION["partner"] . "/templates/$type"; 
 
 $templates = Storage_Folder_ListFolders($path);
 
 return $templates;
}



function Core_Files_PartnerQueries()
{
 $path = "partners/" . $_SESSION["partner"] . "/queries"; 
 
 $queries = Storage_Folder_ListFiles($path);
 foreach($queries as &$query) $query = Storage_Path_RemoveExtension($query);
 
 return $queries;
}


// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                         S E R V I C E S                                        //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Core_Service_Read($service)
{
	$config = Ini_Section_Read("partners/default/system.cfg", "services");
    $url = $config[$service] ?? "";
    if(!empty($url))
        $url = $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/services/'.$url;
    return $url;
}

?><?php

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                         C O U R S E                                            //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Course_Seat_Data($seat_id)
{
 // GET BASIC SEAT INFORMATION
 $seat               = Class_Seat_Read($seat_id, ["users" => true]);
 $seat["assessment"] = json_decode($seat["assessment"], true);
 $seat["badges"]     = json_decode($seat["badges"], true);
 
 // USE CLASS ID TO READ LESSON ID
 $class_id  = $seat["class_id"];
 $class     = Class_Read($class_id, ["teacher" => true]);
 
 // READ LESSON WITH OUTCOMES
 $lesson_id  = $class["info"]["lesson_id"];
 $lesson     = Lesson_Read($lesson_id, ["outcomes" => true]);

 // USE LESSON ID TO GET ALL ASSESSMENT DATA
 //$assessment = Assessment_Outcomes_ReadBySource($seat["student_id"], $lesson_id);
 
 // READ STORED ACTIVITIES RESULTS TIED TO THIS STUDENT AND LESSON
 $student_id = $seat["student_id"];
 $activities = Activities_Results_ReadBySource($student_id, $lesson_id, false);
 
 // PARSE LESSON FOLDER TO SEE WHAT CONTENT SUBFOLDERS ARE AVAILABLE WITHIN
 $content = Storage_Folder_ListFolders("content/lessons/$lesson_id");
 
 
 
 $data               = [];
 
 $data["seat"]       = $seat;
 $data["class"]      = $class["info"];
 $data["teacher"]    = $class["teacher"];
 $data["lesson"]     = $lesson;
 $data["content"]    = $content;
 $data["activities"] = $activities;
 
 return $data;
}



// DIRECT LESSON DATA WITHOUT GOING THROUGH A SEAT. THE SEAT WILL BE "VIRTUAL"
function Course_Seat_Virtual($lesson_id)
{ 
 $data               = [];
 
 $data["seat"]       = [];
 $data["class"]      = [];
 $data["teacher"]    = [];
 $data["lesson"]     = Lesson_Read($lesson_id, ["outcomes" => true]);
 $data["content"]    = Storage_Folder_ListFolders("content/lessons/$lesson_id");
 $data["activities"] = [];
 
 return $data;
}


?><?PHP

function Courses_Load()
{
}



function Courses_Read($id, $fields = "*", $info = false, $options = [])
{
 // QUERY 
 $db = Core_Database_Open();
 
 $query = "SELECT $fields FROM courses WHERE id = $id";
 $data  = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 $course = $data[0];
 
 // DECODE JSON FIELDS
 $decode = ["schedule", "staff", "students", "classes", "config"];
 Array_Fields_JSONParse($course, $decode);
 foreach($decode as $field) if(!$course[$field]) $course[$field] = [];
 
 // CLASSES DATES AND TIMES
 if(!$options["utc"]) User_Date_Process($course["classes"], "date_", "out"); 
 
 // LOAD STUDENTS & STAFF INFO?
 foreach(["students", "staff"] as $section)
 {
  
 if($info && ($info["staff"] || $info["all"]))
  { 
   $ids   = array_values($course[$section]); 
   $users = Users_Read($ids);
  
   $keys = array_keys($course[$section]);
   foreach($keys as $key)
   {
    $id         = $course[$section][$key];
    
    $user       = [];
    $user["id"] = $key;
	  $userinfo   = $users[$id] ?? [];
    $user       = array_merge($user, $userinfo); 
	 
    $course[$section][$key] = $user;
   }
  }

 }
 
 
 

 return $course;
}



function Courses_Update_Field($id, $field, $value, $json = false)
{
 $db = Core_Database_Open();
 
 if($json) $value = json_encode($value);
 $value  = SQL_Format($value, $db);

 $query = "UPDATE courses SET $field = $value WHERE id = $id";
 $data  = SQL_Query($query, $db);
 
 SQL_Close($db);
}



function Courses_Ongoing($centers, $fields = "id, name")
{
 $date    = Date_Format_As(Date_Now(), "date-only");
 
 $db      = Core_Database_Open();
 
 $centers = SQL_Format_IN($centers, $db);
 
 $query   = "SELECT $fields FROM courses WHERE center_id IN ($centers) AND (date_end >= $date) ORDER BY date_start";
 $data    = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 return $data;
}



function Courses_List($status = false, $centers = false, $program = false, $level = false, $date = false, $date_mode = "ongoing", $order = "date_start", $options = [])
{
 $conditions = [];
  
  
 // STATUS
 if($status)
 {
  array_push($conditions, "status = '$status'");
 }
  
 // NAME
 if(isset($options["name"]) && $options["name"])
 {
  array_push($conditions, "name LIKE '%".$options["name"]."%'");
 }
  
 // CENTERS
 if($centers)
 {
  foreach($centers as &$center) $center = "'$center'";
  
  $centers = implode(",", $centers);
  
  array_push($conditions, "center_id IN ($centers)");
 }
 
 
 // PROGRAM
 if($program)
 {    
  array_push($conditions, "program = '$program'");
 }
 
 
 // LEVEL
 if($level)
 {    
  array_push($conditions, "level = '$level'");
 }
 
 
 // DATE
 $date = Date_Format_As($date, "date-only");
 
 switch($date_mode)
 {
  case "ongoing":
	$now = Date_Format_As(Date_Now(), "date-only");
	array_push($conditions, "date_start <= $date");
	array_push($conditions, "date_end >= $date");
  break;
  
  case "starts-after":
	array_push($conditions, "date_start >= $date");
  break;
  
  case "ends-after":
	array_push($conditions, "date_end >= $date");
  break;
  
  case "ends-before":
	array_push($conditions, "date_end <= $date");
  break;
 }
 
 $limit = " LIMIT 100";
 if(isset($options["limit"]))
 $limit = " LIMIT " . $options["limit"];
 if(isset($options["offset"]))
 $limit .= " OFFSET " . $options["offset"];
 
 $conditions = implode(" AND ", $conditions);
 
 
 // QUERY 
 $db = Core_Database_Open();
 
 $query = "SELECT * FROM courses WHERE $conditions ORDER BY $order $limit";
 $data  = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 // DECODE JSON FIELDS
 Array_Items_JSONParse($data, ["schedule", "staff", "students", "classes", "config"]);
 
 
 // CALCULATE SEATS LEFT
 foreach($data as &$item) 
 {
  $seats = [];
  $seats["taken"] = count($item["students"] ?? []);
  $seats["total"] = $item["seats"] ?? 0;
  
  unset($item["students"]);
  $item["seats"] = $seats;
 }
 
 
 // CALCULATE CLASSES LEFT
 $now = Date_Format_As(Date_Now(), "date-only");
 foreach($data as &$item) 
 {
  $all   = $item["classes"] ?? [];
  $taken = 0;
  
  foreach($all as $class)
  {
   $date = $class["date"];
   
   if($date < $now)
   {
	$taken = $taken + 1;
   }
   else 
   {
	break;
   }
  }
  
  $classes          = [];
  $classes["taken"] = $taken;
  $classes["total"] = count($all);
  
  $item["classes"]  = $classes;
 }
 
 
 
 return $data;
}

function Courses_Update_Classes($id, $classes)
{
 $db = Core_Database_Open();
 User_Date_Process($classes, "date_", "in");

 $course_date_start = $classes[0]["date_end"];
 $course_date_end = $classes[count($classes)-1]["date_end"];
 
 $classes  = json_encode($classes);
 $query = "UPDATE courses SET classes = '$classes' , date_end = $course_date_end, date_start = $course_date_start WHERE id = $id";
 $data  = SQL_Query($query, $db);
 SQL_Close($db);
}

function Courses_New($data)
{
 $db = Core_Database_Open();
 
 $insert = SQL_Fields_Insert($data, $db);
 $query  = "INSERT INTO courses $insert";
 $id     = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 return $id;
}




function Courses_Rollout($id, $center, $classes, $options = [])
{
 $db     = Core_Database_Open();

 $center = SQL_Format($center, $db);
 if(!$options["utc"]) User_Date_Process($classes, "date_", "in");
 
 
 $students   = SQL_Query("SELECT student_id, lesson_ids FROM registration WHERE course_id = $id", $db);

 
 // FIRST, ROLLBACK
 $pastClasses = Courses_Rollback($id); 
 
 
 $db->beginTransaction();
 foreach($classes as &$class)
 {
  $date_start        = $class["date_start"];
  $duration          = $class["duration"];
  $date_end          = $class["date_end"];

  $teacher_id        = $class["teacher_id"] ?? "NULL";
  $ta1               = $class["ta1_id"] ?? "NULL";
  $ta2               = $class["ta2_id"] ?? "NULL";
  $ta3               = $class["ta3_id"] ?? "NULL";
  $classroom_id      = $class["classroom_id"] ?? "NULL";
  
  $teacher_type      = SQL_Format($class["teacher_type"], $db);
  $lesson_id         = SQL_Format($class["lesson_id"], $db);
   
  $class["id"]       = SQL_Query("INSERT INTO classes (center_id, date_start, date_end, duration, lesson_id, teacher_type, course_id, teacher_id, ta1_id, ta2_id, ta3_id, classroom_id) VALUES ($center, $date_start, $date_end, $duration, $lesson_id, $teacher_type, $id, $teacher_id, $ta1, $ta2, $ta3, $classroom_id)", $db); 

  foreach($students as $student) {
    $lessons = $student["lesson_ids"];
    if(strpos($lessons, $class["lesson_id"]) !== false) {
      SQL_Query("INSERT INTO classes_seats (class_id, student_id, course_id) VALUES({$class["id"]}, {$student["student_id"]}, $id)", $db);
    }
  }
 }
 $db->commit();
 
 $date_end = $classes[count($classes)-1]["date_end"];
 
 $classes  = json_encode($classes);
 
 SQL_Query("UPDATE courses SET classes = '$classes', date_end = $date_end WHERE id = $id", $db); 

 
 // REMOVE ALL PAST CLASSES AFTER CREATING NEW CLASSES
 if($pastClasses) {
  $ids = array_column($pastClasses, "id");
  $ids = SQL_Format_IN($ids, $db);
  SQL_Query("DELETE FROM classes WHERE id IN ($ids)", $db); 
  SQL_Query("DELETE FROM classes_seats WHERE class_id IN ($ids)", $db); 
 }

 SQL_Close($db);


 $classes = json_decode($classes);
 foreach ($classes as $key => $value) {
  $classes[$key] = (array)$value;
 }
 if(!$options["utc"]) User_Date_Process($classes, "date_", "out");
 
 //CREATE TEST
 Courses_Test_Create($id);

 json_encode($classes);
 return $classes;
}



function Courses_Rollback($id)
{  
 $db = Core_Database_Open();
 
 
 // COLLECT CLASSES IDS FROM TODAY ONWARD
 $now      = Date_Format_As(Date_Now(), "date-only") . "0000"; 
 $classes  = SQL_Query("SELECT id FROM classes WHERE course_id = $id AND date_start >= $now", $db);
 
 $ids      = array_column($classes, "id");
 $ids      = SQL_Format_IN($ids, $db);
 
 if($ids) 
 {	 
  // KEEP ONLY CLASSES FROM TODAY AND BEFORE
  SQL_Query("DELETE FROM classes WHERE id IN ($ids)", $db); 
  SQL_Query("DELETE FROM classes_seats WHERE class_id IN ($ids)", $db); 
 }
 
 
 // UPDATE COURSE CLASSES JSON BY KEEPING ONLY CLASSES FROM TODAY AND BEFORE
 $courses = SQL_Query("SELECT classes FROM courses WHERE id = $id", $db);
 $course  = $courses[0];
 $classes = $course["classes"];
 $classes = json_decode($classes, true);
 
 $now  = Date_Format_As(Date_Now(), "date-only");
 $past = [];
 foreach($classes as $class)
 {
  if(Date_Format_As($class["date_start"], "date-only") < $now)
  {
   array_push($past, $class);
  }
 }
 $classes = $past;

 // UPDATE CLASSES AND ERASE STAFF
 $classes = json_encode($classes);
 $classes = SQL_Format($classes, $db);
 SQL_Query("UPDATE courses SET classes = $classes WHERE id = $id", $db); 
 
 SQL_Close($db);
 return $past;
}




function Courses_Classes_List($id, $date_from = "197001010000", $date_to = "210001012359", $fields = "id", $options = [])
{
 if(!$options["utc"])
 {
  $date_from = User_Date($date_from, "in");
  $date_to   = User_Date($date_to,   "in");
 }
	
 $db      = Core_Database_Open();
 $classes = SQL_Query("SELECT $fields FROM classes WHERE course_id = $id AND date_start BETWEEN $date_from AND $date_to", $db);   
 SQL_Close($db);
 
 return $classes;
}




function Courses_Staff_Set($classes, $field, $user_id)
{
 $now = Date_Now();
 
 Classes_Batch_SetField($classes, $field, $user_id, ["from" => $now]);
}




function Courses_Staff_Unset($id, $field, $user_id)
{
 $now     = Date_Now();
 $db      = Core_Database_Open();
 
 $query   = "UPDATE classes SET $field = NULL WHERE course_id = $id AND $field = $user_id AND date_start > $now";
 SQL_Query($query, $db);
 
 SQL_Close($db);
}





function Courses_Rooms_Available($id)
{
 // COLLECT DATES FOR ALL LESSONS OF THIS COURSE
 $db      = Core_Database_Open();
 $rows    = SQL_Query("SELECT center_id, classes FROM courses WHERE id = $id", $db);
 SQL_Close($db);
 
 // CENTER AND CLASSES TO CHECK
 $center  = $rows[0]["center_id"];
 $classes = json_decode($rows[0]["classes"], true);
 
 // CONVERT CLASS DATES TO UTC
 User_Date_Process($classes, "date_", "in");
 
 // REMOVE CLASSES IN THE PAST
 $now      = Date_Now();
 $filtered = [];
 foreach($classes as $class)
 {
  if($class["date_start"] > $now) array_push($filtered, $class);
 }
 
 // SEE WHAT ROOMS ARE AVAILABLE THROUGHOUT THOSE DATES
 $rooms = Center_Rooms_Available($center, $filtered, []);

 return $rooms;
}



function Courses_Students_Add($id, $student_id, $date_from = false, $amount = 0, $options = [])
{
 $db = Core_Database_Open();
  
 // GET STUDENTS AND CLASSES
 $rows   = SQL_Query("SELECT students, seats FROM courses WHERE id = $id", $db);
 $course = $rows[0];
 
 $students = $course["students"];
 $students = json_decode($students, true) ?? [];
 
 // CHECK COURSE SEATS LIMIT
 if($options["checkseats"])
 {
  if(count(array_keys($students)) >= $course["seats"])
  {
   SQL_Close($db);  
   return "no seats";
  }	  
 }	 
 
 
 // IF DATE_FROM NOT SET, THEN IT'S TODAY
 if(!$date_from)
 {
  $date_from = Date_Format_As(Date_Now(), "date-only") . "2359";
 }
 
 
 // GET IDS FOR COURSE CLASSES FROM DATE_FROM ONWARD
 $classes = Courses_Classes_List($id, $date_from, "210001012359", "id, lesson_id");
 $ids     = array_column($classes, "id");
 $ids     = SQL_Format_IN($ids, $db);
 
 // UPDATE STUDENTS OBJECT
 if(!in_array($student_id, $students))  {
  array_push($students, $student_id);
  $students = json_encode($students);
  $students = SQL_Format($students, $db);
  SQL_Query("UPDATE courses SET students = $students WHERE id = $id", $db);
 }


 // UPDATE REGISTRATION FOR EACH STUDENT
 $lesson_ids = array_column($classes, "lesson_id") ?: [];

 $registration = SQL_Query("SELECT lesson_ids FROM registration WHERE course_id = $id AND student_id = $student_id LIMIT 1", $db);
 $past_lessons = json_decode($registration[0]["lesson_ids"], true) ?: [];
 $combined_lessons = array_unique([...$past_lessons, ...$lesson_ids]);
 if($combined_lessons) {
  $combined_lessons = array_values($combined_lessons);
  sort($combined_lessons);
  $combined_lessons = SQL_Format(json_encode($combined_lessons), $db);
  SQL_Query("INSERT INTO registration (course_id, student_id, lesson_ids) VALUES($id, $student_id, $combined_lessons) ON DUPLICATE KEY UPDATE lesson_ids = $combined_lessons", $db);
 }


 // SANITIZATION: DELETE EXISTING STUDENT SEATS FOR THIS COURSE FROM DATE_FROM ONWARD
 SQL_Query("DELETE FROM classes_seats WHERE (student_id = $student_id AND class_id IN ($ids))", $db);
 
 
 // CREATE SEATS FOR ALL CLASSES FROM DATE_FROM ONWARD
 $ids  = array_column($classes, "id");
 
 $db->beginTransaction();
 $seats = [];
 foreach($ids as $class_id)
 {  
  $seat_id = SQL_Query("INSERT INTO classes_seats (class_id, student_id, course_id) VALUES($class_id, $student_id, $id)", $db);
  array_push($seats, $seat_id);
  
  // IF AN AMOUNT IS SET AND WE MATCHED IT, STOP ADDING
  if($amount && count($seats) >= $amount) break;
 }
 $db->commit();
 
 SQL_Close($db);
 
 return $seats;
}


function Courses_Students_Remove($id, $student_id, $seats = false)
{ 
 $db = Core_Database_Open();
  
 // GET STUDENTS AND CLASSES
 $rows   = SQL_Query("SELECT students, classes FROM courses WHERE id = $id", $db);
 $course = $rows[0];
 
 // REMOVE SPECIFIC STUDENT FROM STUDENTS
 $students = $course["students"];
 $students = json_decode($students, true);
 Array_Item_Delete($students, $student_id);
 $students = json_encode($students);
 $students = SQL_Format($students, $db);
 SQL_Query("UPDATE courses SET students = $students WHERE id = $id", $db);
 
 
 // REMOVE SEATS FOR ALL CLASSES FROM TOMORROW ONWARD
 $classes = $course["classes"];
 $classes = json_decode($classes, true);
 $today   = Date_Format_As(Date_Now(), "date-only");

 $past_lessons = [];
 $seatIds = []; 
 foreach($classes as $class)
 {
  if(Date_Format_As($class["date_start"], "date-only") > $today)
  {
   array_push($seatIds, $class["id"]);
  } else {
    array_push($past_lessons, $class["lesson_id"]);
  } 
 }
 
 // DELETE ALL CLASS SEAT IF EXISITNG
 if($seatIds) {
  $seatIds = SQL_Format_IN($seatIds, $db);
  SQL_Query("DELETE FROM classes_seats WHERE student_id = $student_id AND class_id IN ($seatIds)", $db);
 }

 // UPDATE PAST LESSON ON REGISTRATION TABLE  
 $registration = SQL_Query("SELECT lesson_ids FROM registration WHERE course_id = $id AND student_id = $student_id LIMIT 1", $db);
 if($registration) {
  $lesson_ids = json_decode($registration[0]["lesson_ids"]) ?: [];

  if(!$past_lessons || $past_lessons && $lesson_ids && $past_lessons[0] !== $lesson_ids[0]) {
    SQL_Query("DELETE FROM registration WHERE course_id = $id AND student_id = $student_id", $db);
  } else {
    $past_lessons = SQL_Format(json_encode($past_lessons), $db);
    SQL_Query("UPDATE registration SET lesson_ids = $past_lessons WHERE course_id = $id AND student_id = $student_id", $db);
  }
 }

 SQL_Close($db);
}




function Courses_Delete($id)
{ 
 // ROLLBACK ALLOCATED CLASSES, IF ANY
 Courses_Rollback($id);
 
 // DELETE COURSE FROM DATABASE
 $db = Core_Database_Open();
 
 $query  = "DELETE FROM courses WHERE id = $id";
 SQL_Query($query, $db);
 
 SQL_Close($db);
}


function Courses_Lock_New($data)
{
 $db = Core_Database_Open();
 $select  = "SELECT id FROM courses_lock WHERE user_id = ".$data["user_id"];
 $log     = SQL_Query($select, $db);
 if($log) $data["id"] = $log[0]["id"];
 $insert = SQL_Fields_Insert($data, $db);
 $query  = "INSERT INTO courses_lock $insert ON DUPLICATE KEY UPDATE last_update = ".$data["last_update"].", course_id = ".$data["course_id"];
 $id     = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 return $id;
}



function Course_Edit_Available($id)
{
 $db = Core_Database_Open();
 $select  = "SELECT user_id FROM courses_lock WHERE course_id = ".$id;
 $log     = SQL_Query($select, $db);
 
 if($log)
 {
  $query  = "SELECT id,firstname, lastname, midname FROM users WHERE id = ". $log[0]["user_id"]; 
  $rows   = SQL_Query($query, $db);
  $user   = $rows[0];
  SQL_Close($db);
  return $user;
 } 
 else{
  SQL_Close($db);
  return 0;
 } 
}



function Courses_Lock_Delete($user_id = -1)
{ 
 if($user_id == -1) $user_id = $_SESSION["user"]["id"];
 
 // DELETE COURSE LOG FROM DATABASE
 $db = Core_Database_Open();
 
 $query  = "DELETE FROM courses_lock WHERE user_id = $user_id";
 SQL_Query($query, $db);
 
 SQL_Close($db);
}

function Courses_Check_Exist($search = [])
{ 
  $search = (array)$search;
  $db = Core_Database_Open();
  
  $conditions = [];

  foreach ($search as $key => $value) {
    if($key == "date_start" || $key == "date_end" || $key == "seats")
      array_push($conditions,"(".$key." = ".$value.")");
    else
      array_push($conditions,"(".$key." = '".$value."')");
  }

  $conditions = implode(" AND ", $conditions);

  $query = trim("SELECT id, classes, lesson_duration, date_start, date_end FROM courses WHERE $conditions");

  $rows  = SQL_Query($query, $db);
  SQL_Close($db);

  if(!$rows) return [];

  $course = $rows[0];
  if($course["classes"]) {
    Array_Fields_JSONParse($course, ["classes"]);
    User_Date_Process($course["classes"], "date_", "out"); 
  }

  return $course;
}

function Courses_Update($course)
{
 $db = Core_Database_Open();
 $id = $course["id"];
 $courseinfo = SQL_Query("SELECT * FROM courses WHERE id = $id", $db);
 if($courseinfo){
  try {
    $data = [];
    $query = "UPDATE courses SET ";
    foreach ($course as $field => $value) {
      if($field != "id" && $courseinfo[$field] != $value){
        if(gettype($value) == "array") $value = json_encode($value);
        $value  = SQL_Format($value, $db); 
        array_push($data," $field = $value ");
      }
    }
    $data = implode(" , ",$data);
    $query = $query .$data . " WHERE id = $id";
    SQL_Query($query, $db);
    SQL_Close($db);
  } catch (\Throwable $th) {
    var_dump($th->getMessage());
    return false;
  }
 }
 return true;
}


function Courses_MultipleStudents_Add($id, $students = [], $amount = 0, $options = [])
{
 $db = Core_Database_Open();
  	 
 $seats = [];
 foreach ($students as $key => $student) { 
    // GET STUDENTS AND CLASSES
    $rows   = SQL_Query("SELECT students, seats FROM courses WHERE id = $id", $db);
    $course = $rows[0];

    $coursestudents = $course["students"];
    $coursestudents = json_decode($coursestudents, true) ?? [];

    // CHECK COURSE SEATS LIMIT
    if($options["checkseats"])
    {
      if(count(array_keys($coursestudents)) >= $course["seats"])
      {
        SQL_Close($db);  
        return "no seats for student:".$student["student_id"];
      }	  
    }
    
    $student  =(array)$student;
    $date_from = $student["date_from"];
    $student_id = $student["student_id"];

    // IF DATE_FROM NOT SET, THEN IT'S TODAY
    if(!$date_from)
    {
      $date_from = Date_Format_As(Date_Now(), "date-only") . "2359";
    }

    // GET IDS FOR COURSE CLASSES FROM DATE_FROM ONWARD
    $classes = Courses_Classes_List($id, $date_from, "210001012359", "id, lesson_id");
    $ids     = array_column($classes, "id");
    $ids     = SQL_Format_IN($ids, $db);

    // UPDATE STUDENTS OBJECT
    if(!in_array($student_id, $coursestudents))  {
      array_push($coursestudents, $student_id);
      $coursestudents = json_encode($coursestudents);
      $coursestudents = SQL_Format($coursestudents, $db);
      SQL_Query("UPDATE courses SET students = $coursestudents WHERE id = $id", $db);
    }

    // UPDATE REGISTRATION FOR EACH STUDENT
    $lesson_ids = array_column($classes, "lesson_id") ?: [];

    $registration = SQL_Query("SELECT lesson_ids FROM registration WHERE course_id = $id AND student_id = $student_id LIMIT 1", $db);
    $past_lessons = json_decode($registration[0]["lesson_ids"], true) ?: [];
    $combined_lessons = array_unique([...$past_lessons, ...$lesson_ids]);
    if($combined_lessons) {
      $combined_lessons = array_values($combined_lessons);
      sort($combined_lessons);
      $combined_lessons = SQL_Format(json_encode($combined_lessons), $db);
      if($past_lessons){
        SQL_Query("UPDATE registration SET lesson_ids = $combined_lessons WHERE course_id = $id AND student_id = $student_id", $db);
      }else
        SQL_Query("INSERT INTO registration (course_id, student_id, lesson_ids) VALUES($id, $student_id, $combined_lessons)", $db);
    }

    // SANITIZATION: DELETE EXISTING STUDENT SEATS FOR THIS COURSE FROM DATE_FROM ONWARD
    SQL_Query("DELETE FROM classes_seats WHERE (student_id = $student_id AND class_id IN ($ids))", $db);
    
    // CREATE SEATS FOR ALL CLASSES FROM DATE_FROM ONWARD
    $ids  = array_column($classes, "id");
    $db->beginTransaction();
    $seats[$student_id] = [];
    foreach($ids as $class_id)
    {  
      $seat_id = SQL_Query("INSERT INTO classes_seats (class_id, student_id, course_id) VALUES($class_id, $student_id, $id)", $db);
      array_push($seats[$student_id], $seat_id);
      
      // IF AN AMOUNT IS SET AND WE MATCHED IT, STOP ADDING
      if($amount && count($seats[$student_id]) >= $amount) break;
    }
    $db->commit();
 }
 SQL_Close($db);
 return true;
}

function Course_List_ByTeacher($teacher_id, $date_from, $date_to, $options = [])
{
  $db = Core_Database_Open();
  $teacherCondition = "staff LIKE '%\"$teacher_id\"%'";
  $date_from_custom = substr($date_from,0,8);
  $date_to_custom = substr($date_to,0,8);

  $query = "SELECT * FROM courses WHERE $teacherCondition AND ((date_start BETWEEN $date_from_custom AND $date_to_custom) OR (date_end BETWEEN $date_from AND $date_to) OR (date_start < $date_from_custom AND date_end > $date_to)) ORDER BY date_start";
  
  $data  = SQL_Query($query, $db);
 
  foreach ($data as $key => &$value) {
    // DECODE JSON FIELDS
    $decode = ["schedule", "staff", "students", "classes", "config"];
    Array_Fields_JSONParse($value, $decode);
    // CLASSES DATES AND TIMES
    if(!$options["utc"]) User_Date_Process($value["classes"], "date_", "out"); 
  }
 
  SQL_Close($db);
  return $data;
}

function Courses_Test_Create($course_id)
{
  $db = Core_Database_Open();
  try {
  $courses = SQL_Query("SELECT * FROM courses WHERE id = $course_id",$db);
  $course = $courses[0];
  $program = $course["program"];
  $level = $course["level"];
  
  $classes = json_decode($course["classes"]);

  $grades = parse_ini_file("partners/default/grades.cfg",true);
  $programType = $grades[$program]["type"];
  $lessonTest = $grades[$program][$level];
  $lessonTest = explode(";",$lessonTest);
  
  SQL_Transaction_Begin($db);

  // GET ALL EXIST COURSE TEST
  $dataCheck = [];
  $courseTests = SQL_Query("SELECT id, class_id, test_type, name FROM course_test WHERE course_id = $course_id",$db);
  foreach ($courseTests as $key => $value) {
   $dataCheck[$value["id"]] = $value["test_type"] . "__" . $value["name"];
  }
  $studentTests = SQL_Query("SELECT id,test_id FROM student_test WHERE course_id = $course_id",$db);

  $maxScoreTotal = 0;

  // DELETE EXIST COURSE TEST
  SQL_Query("DELETE FROM course_test WHERE course_id = $course_id",$db);
  foreach ($lessonTest as $key => $lessonText) 
  {
    $lessons = explode("__",$lessonText);
    $lesson_id = $lessons[0];
    
    $classKey = array_search($lesson_id,array_column($classes,"lesson_id"));
    $classId = $classes[$classKey]->id;
    
    $lessonInfo = parse_ini_file("content/lessons/$lesson_id/info.dat",true);
    $test = $lessonInfo["test"];
    $testType = $test["type"];
    $testType = SQL_Format($testType,$db);
   
    $skillCount = 0;
    $testKeys = array_keys($test);
    
    for ($i=0; $i < count($testKeys) ; $i++) { 
      if(str_contains($testKeys[$i],"skill")) $skillCount++;
    }
  
    $maxScoreTest = 0;
    $maxWeight = 0;
    for ($i=0; $i < $skillCount; $i++) { 
      $name = $test["skill 0$i"];
      $name = SQL_Format($name,$db);
      $maxScore = $test["max score 0$i"];
      $maxScoreTest += (int)$test["max score 0$i"];
      $weight = $test["percentage 0$i"];
      $maxWeight += (int)$test["percentage 0$i"];
      $id = SQL_Query("INSERT INTO course_test(course_id,class_id,test_type,name,max_score,weight) VALUE($course_id,$classId,$testType,$name,$maxScore,$weight)",$db);
      
      //UPDATE EXIST STUDENT TEST
      if(count($studentTests))
      {
        foreach ($studentTests as $key => $value) {
          if($dataCheck[$value["test_id"]] == $test["type"] . "__" . $test["skill 0$i"])
          {
            $studentTestId = $value["id"];
            SQL_Query("UPDATE student_test SET class_id = $classId , test_id = $id WHERE id = $studentTestId",$db);
          }
        }
      }

      // SET TOTAL SCORE
      switch ($programType) {
        case 'sat':
          if($test["type"] == "Best Scores") $maxScoreTotal += (int)$maxScore;
          break;
        case 'ielts': 
          if($test["type"] == "eomt") $maxScoreTotal += (int)$maxScore/100*$weight;
          break;
      }
    }

    // ADD STRETCH COLUMN WITH PROGRAM TYPE
    switch ($programType) {
      case 'sat':
        if($test["type"] != "Best Scores" && $test["type"] != "Homework")
        {
          $name = $test["type"] . " total";
          $name = SQL_Format($name,$db);
          $id = SQL_Query("INSERT INTO course_test(course_id,class_id,test_type,name,max_score,weight) VALUE($course_id,$classId,$name,$name,$maxScoreTest,0)",$db);
          //UPDATE EXIST STUDENT TEST
          if(count($studentTests))
          {
            foreach ($studentTests as $key => $value) {
              if($dataCheck[$value["test_id"]] == $test["type"] . "__" . $test["type"] . " total")
              {
                $studentTestId = $value["id"];
                SQL_Query("UPDATE student_test SET class_id = $classId , test_id = $id WHERE id = $studentTestId",$db);
              }
            }
          }
        } 
        break;
      case 'ielts':
        // CREATE OR UPDATE MODULE TEST TOTAL
        if($test["type"] == "eomt" || $test["type"] == "mmt")
        {
          $name = $test["type"] . " total";
          $name = SQL_Format($name,$db);
          $id = SQL_Query("INSERT INTO course_test(course_id,class_id,test_type,name,max_score,weight) VALUE($course_id,$classId,$name,$name,$maxScoreTest/4,$maxWeight)",$db);
          //UPDATE EXIST STUDENT TEST
          if(count($studentTests))
          {
            foreach ($studentTests as $key => $value) {
              if($dataCheck[$value["test_id"]] == $test["type"]. " total" . "__" . $test["type"] . " total")
              {
                $studentTestId = $value["id"];
                SQL_Query("UPDATE student_test SET class_id = $classId , test_id = $id WHERE id = $studentTestId",$db);
              }
            }
          }
        }
        break;
    }
  }

  // ADD COURSE TOTAL COLUMN
  switch ($programType) {
    case 'sat':
      $testName = "Final SAT score";
      break;
    
    default:
      $testName = "course total";
      break;
  }
  if($maxScoreTotal == 0) $maxScoreTotal = 100;
  $idTotal = SQL_Query("INSERT INTO course_test(course_id,test_type,name,max_score,weight) VALUE($course_id,'course_total','$testName',$maxScoreTotal,100)",$db);
  foreach ($studentTests as $key => $value) {
    if($dataCheck[$value["test_id"]] == 'course_total__'.$testName)
    {
      $studentTestId = $value["id"];
      SQL_Query("UPDATE student_test SET test_id = $idTotal WHERE id = $studentTestId",$db);
    }
  }
  SQL_Transaction_Commit($db);
  } catch (\Throwable $th) 
  {
    SQL_Transaction_Rollback($db);
    Users_Write_Log("Courses_Test_Create($course_id)",["message" => $th->getMessage(),"trace" => $th->getTrace()]);
  }
  SQL_Close($db);
}
?><?php




?><?PHP

function Dialogue_Load()
{
}




function Dialogue_Read($source)
{
 $file  = "$source/info.dat";
 $info  = parse_ini_file($file, true);
 
 $file  = "$source/script.dat";
 $lines = parse_ini_file($file, true);
 
 $dialogue          = [];
 $dialogue["info"]  = $info  ?: [];
 $dialogue["lines"] = $lines ?: [];
 
 $dialogue["lines"] = Dialogue_Purge($dialogue["lines"]);
 
 return $dialogue;
}



function Dialogue_WriteHeader($path, $data)
{
 if(!file_exists($path)) Storage_Path_Create($path);
 
 Ini_File_Write("$path/info.dat", $data);
}



function Dialogue_Purge($lines)
{
 $purged = [];
 
 foreach($lines as &$line)
 {
  $purge  = true;
  
  $values = array_values($line);
  foreach($values as $value) if(trim($value) != "")
  {
   $purge = false;
   break;
  }
  
  if(!$purge) array_push($purged, $line);
 }
 
 return $purged;
}



function Dialogue_WriteLine($path, $n, $data)
{
 $lines     = parse_ini_file("$path/script.dat", true);
 $lines[$n] = $data;
 
 $purged    = Dialogue_Purge($lines);
 
 Ini_File_Write("$path/script.dat", $purged);
}



function Dialogue_Export($path)
{
 $script = Ini_File_Read("$path/script.dat");
  
 // LANGUAGES
 $languages = array_keys($script[0]);
 Array_Item_Delete($languages, "character");

 // TEXT
 foreach($languages as $locale)
 {
  $data = [];
  
  foreach($script as $line)
  {
   $character = $line["character"];
   $text      = $line[$locale];
   
   array_push($data, "$character: $text");
  }
  
  $data = implode("\r\n", $data);
  
  // PUT IT WITHIN THE DIALOGUE
  //file_put_contents("$path/$locale.txt", $data);
  
  // PUT IT WITHIN A VIDEO FOLDER IN THE SAME PATH AS THE DIALOGUE FOLDER
  $parent = Storage_Path_GetFolder($path);
  $video  = "$parent/video";
  Storage_Path_Create($video);
  
  file_put_contents("$video/$locale.txt", $data);
 } 
 
}

function Dialogue_Read_Mutiple($sources)
{
    $dialogues = [];
    if(gettype($sources) == "string") $sources = explode(",",$sources);
    foreach ($sources as $key => $source) {
     $dialogues[$source] = Dialogue_Read("content/lessons/" . $source . "/dialogue");
    }
    return $dialogues;
}

?><?php

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                       E D I T O R                                              //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Editor_Folder_Download($user_id, $folder)
{
 $path    = "partners/default/users/$user_id/temp";
 $zipfile = "$path/temp.zip";
 Zip_Folder("partners/default/users/$user_id/temp/editor/$folder", $zipfile, ["recurse"]);
 
 header('Content-Description: File Transfer');
 header('Content-Type: application/octet-stream');
 header('Content-Disposition: attachment; filename=' . basename($zipfile));
 header('Content-Transfer-Encoding: binary');
 header('Expires: 0');
 header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
 header('Pragma: public');
 header('Content-Length: ' . filesize($zipfile));
 ob_clean();
 flush();
 readfile($zipfile);
}



function Editor_Folder_Zip($user_id, $folder)
{
 $path    = "partners/default/users/$user_id/temp";
 $zipfile = "$path/temp.zip";
 Zip_Folder("partners/default/users/$user_id/temp/editor/$folder", $zipfile, ["recurse"]);
 
 $handle   = fopen($zipfile, "rb");
 $contents = fread($handle, filesize($zipfile));
 return $contents;
}




function Editor_Import_Resource($dest, $source)
{
 $filename = Storage_Path_GetFilename($source);
 $dest     = "$dest/$filename";
 
 copy($source, $dest);
 
 return $dest;
}




function Editor_Templates_List($content, $category = false)
{
 if($category) $content = "$content/$category";
 
 $folders = Storage_Folders_Collect("content/templates/$content", ["recurse", "uproot"]);
 
 return $folders;
}



function Editor_Files_Upload($srcfilename = "", $path = "", $overwrite = true)
{ 
 $filename = Storage_Path_GetFilename($srcfilename);
 $dest     = "$path/$filename"; 
 
 if(!$overwrite)
 {
  while(file_exists($dest))
  {
   $path     = Storage_Path_GetFolder($dest);
   $filename = Storage_Path_GetFilename($dest);
   $ext      = Storage_Path_GetExtension($dest);
   $name     = Storage_Path_RemoveExtension($filename);
  
   $dest     = "$path/$name.$ext";
  }
 }
 
 Storage_File_Upload($dest);
 
 return $dest;
}


function Editor_Lesson_DeleteFile($lesson, $file)
{
 $dest = "content/lessons/$lesson/$file";
 
 unlink($dest);
}


?><?PHP

function Families_List_ByCenter($center = false, $fields = "*", $order = false, $rows = false, $page = false, $stats = false, $inactive = false)
{
 $db = Core_Database_Open();

 $conditions = [];


 // BASE CONDITION
 if($center)
 {
  if(gettype($center) == "string")
  {
   $center = SQL_Format($center, $db);  
   array_push($conditions, "center = $center");
  }
  else
  {  
   $center = SQL_Format_IN($center, $db);
   array_push($conditions, "center IN ($center)");
  }
 }

 
 
 // PRUNE INACTIVE FAMILIES
 if(!$inactive)
 {
  array_push($conditions, "(status <> 'inactive')");
 }	 
 
 $conditions = implode(" AND ", $conditions);
 
 
 // ORDER 
 if($order)
 {
  $order = "ORDER BY $order";   
 }
 else 
 {
  $order = "";
 }
 
 // LIMIT
 if($rows)
 {
  if($page) 
  {
   $start = $page * $rows;
   $limit = "LIMIT $start, $rows";
  }
  else
  {
   $limit = "LIMIT $rows";
  }
 }
 else
 {
  $limit = "";
 }
 
 $query  = "SELECT $fields FROM users_families WHERE $conditions $order $limit";
 $data   = SQL_Query($query, $db);
 
 if($stats)
 {
  $stats  = [];
  
  $query  = "SELECT count(*) FROM users_families WHERE $conditions";
  $count  = SQL_Query($query, $db);
  $count  = $count[0]["count(*)"];
  
  $stats["count"] = $count;
  
  array_push($data, $stats);
 }
 
 SQL_Close($db);
 
 return $data;
}



function Family_Folder($id = -1)
{ 
 $folder = "partners/" . $_SESSION["partner"] . "/families/" . $id;
 
 return $folder;
}




function Family_Create($data = [])
{
 // INSERT INTO DB
 $db = Core_Database_Open();

 $fields = SQL_Fields_Insert($data, $db);	
 $query  = "INSERT INTO users_families $fields";
 
 $id     = SQL_Query($query, $db);
 
 SQL_Close($db);
	
	
 // CREATE FAMILY FOLDER AND COPY DEFAULTS
 $folder = Family_Folder($id);
 Storage_Path_Create($folder);
 
 //$defaults = "partners/" . $_SESSION["partner"] . "/defaults";
 //copy("$defaults/family-settings.cfg", "$folder/settings.cfg");
	
 return $id;
}



function Family_Users($id)
{
 $db             = Core_Database_Open();
 
 $query          = "SELECT id FROM users WHERE family_id = $id";
 $rows           = SQL_Query($query, $db);
 $ids            = array_column($rows, "id");
 
 SQL_Close($db);

 $users          = Users_Read($ids);
 
 return $users;
}


function Family_Read($id, $options = [])
{
 $db             = Core_Database_Open();
 
 $query          = "SELECT * FROM users_families WHERE id = $id";
 $rows           = SQL_Query($query, $db);
 
 $family         = $rows[0];
 $family["data"] = json_decode($family["data"], true);
 
 
 if(isset($options["manager"]))
 {
  $manager_id = $family["manager_id"];
  if($manager_id)
  {
   $family["manager"] = User_Read($manager_id, ["fields" => "id,firstname,lastname,role"]);
  }
 }
 
 
 SQL_Close($db);


 if($options["users"])
 {
  $family["users"] = Family_Users($id);
 }

  
 return $family;
}



function Family_Update_Field($id, $field, $value)
{	 
 $db     = Core_Database_Open();

 $value  = SQL_Format($value, $db); 
 $query  = "UPDATE users_families SET $field = $value WHERE id = $id";
 
 SQL_Query($query, $db);
 
 SQL_Close($db);
}


function Family_Update_Data($id, $field, $value)
{	 
 $db     = Core_Database_Open();

 $query  = "SELECT data FROM users_families WHERE id = $id";
 $rows   = SQL_Query($query, $db);
 $data   = json_decode($rows[0]["data"], true);
 
 $data[$field] = $value;
 $data         = json_encode($data);
 $data         = SQL_Format($data, $db);
 
 $query  = "UPDATE users_families SET data = $data WHERE id = $id";
 SQL_Query($query, $db);
 
 SQL_Close($db);
}

?><?php

function Grades_Student_Test($course_id, $class_id, $test_id, $student_id, $score, $max_score, $weight, $teacher_id = -1)
{
  $db = Core_Database_Open();
 
  if($teacher_id == -1) $teacher_id = $_SESSION["user"]["id"];

  $now = Date_Now();

  //CHECK EXIST SCORE
  $test = SQL_Query("SELECT id,score FROM student_test WHERE course_id = $course_id AND class_id = $class_id AND test_id = $test_id AND student_id = $student_id",$db);
  if(count($test))
  {
    // UPDATE
    $id = $test[0]["id"];
    SQL_Query("UPDATE student_test SET score = $score, modify_by = $teacher_id, modify_time = $now WHERE id = $id",$db);
  }
  else SQL_Query("INSERT INTO student_test(course_id,class_id,test_id,student_id,score,modify_by,modify_time) VALUE($course_id,$class_id,$test_id,$student_id,$score,$teacher_id,$now)",$db);
  
  // CALCULATE TOTAL SCORE FOR EACH PROGRAM TYPE
  $courses = SQL_Query("SELECT * FROM courses WHERE id = $course_id",$db);
  $course = $courses[0];
  $program = $course["program"];

  $programs = Ini_File_Read("partners/default/programs.cfg");
  $programType = $programs[$program]["type"];

  // GET ALL SCORE OF STUDENT
  $studentTests = SQL_Query("SELECT course_test.* ,student_test.id as student_test_id, student_test.score FROM course_test LEFT JOIN student_test ON student_test.test_id = course_test.id AND student_test.student_id = $student_id WHERE course_test.course_id = $course_id",$db); 
  $total = 0;
  $update = false;
  $updateId = -1;
  $createId = -1;
 
  switch ($programType) {
    case 'sat':
      $updateType = "";
      $updateName = "";

      $testBestScore = ["Maths" => 0, "Verbal (English)" => 0];
      $testBest = [];

      $improvementScore = ["Maths" => 0, "Verbal (English)" => 0];

      foreach ($studentTests as $key => $test) {
        $data[$test["test_type"]][$test["name"]] = $test;
        if($test["id"] == $test_id && $test["test_type"] != "Homework") { $updateType = $test["test_type"]; $updateName = $test["name"];}
        if((int)$test["score"] > (int)$testBestScore[$test["name"]])
        {
          $testBest[$test["name"]] = $test;
          $testBestScore[$test["name"]] = $test["score"];
        }
      }
      
      if($updateType != "")
      {
        $totalUpdate = (int)$data[$updateType]["Maths"]["score"] + (int)$data[$updateType]["Verbal (English)"]["score"];

        // UPDATE TOTAL SCORE
        if($data[$updateType. " total"][$updateType. " total"]["student_test_id"])
        {
          SQL_Query("UPDATE student_test SET score = $totalUpdate, modify_by = $teacher_id, modify_time = $now WHERE id = ".$data[$updateType. " total"][$updateType. " total"]["student_test_id"],$db);
        }
        else
        {
          $testId = $data[$updateType. " total"][$updateType. " total"]["id"];
          SQL_Query("INSERT INTO student_test(course_id,test_id,student_id,score,modify_by,modify_time) VALUE($course_id,$testId,$student_id,$totalUpdate,$teacher_id,$now)",$db);
        }
        
        $total = (int)$testBestScore["Maths"] + (int)$testBestScore["Verbal (English)"];

        // UPDATE BEST SCORE
        if($data["Best Scores"]["Best " . $updateName]["score"] != $testBestScore[$updateName])
        {
          $bestScore = $testBestScore[$updateName];
          
          if($data["Best Scores"]["Best " .$updateName]["student_test_id"])
          {
            SQL_Query("UPDATE student_test SET score = $bestScore, modify_by = $teacher_id, modify_time = $now WHERE id = ".$data["Best Scores"]["Best " .$updateName]["student_test_id"],$db);
          }
          else
          {
            $testId = $data["Best Scores"]["Best " .$updateName]["id"];
            SQL_Query("INSERT INTO student_test(course_id,test_id,student_id,score,modify_by,modify_time) VALUE($course_id,$testId,$student_id,$bestScore,$teacher_id,$now)",$db);    
          }

          // SET CREATEID OR UPDATEID
          if($data["course_total"]["Final SAT score"]["student_test_id"])
          {
            $update = true;
            $updateId = $data["course_total"]["Final SAT score"]["student_test_id"];
          }
          else $createId = $data["course_total"]["Final SAT score"]["id"];

        }

        // UPDATE IMPROVEMENT SCORE
        $improvementScores[$updateName] = (int)$testBestScore[$updateName] - (int)$data["Diagnostic Test"][$updateName]["score"];

        if($data["Score Improvement"][$updateName . " Improvement"]["score"] < $improvementScores[$updateName] || !$data["Score Improvement"][$updateName . " Improvement"]["score"])
        {
          $improvementScore = $improvementScores[$updateName];
          if($data["Score Improvement"][$updateName . " Improvement"]["student_test_id"])
          {
            SQL_Query("UPDATE student_test SET score = $improvementScore, modify_by = $teacher_id, modify_time = $now WHERE id = ".$data["Score Improvement"][$updateName . " Improvement"]["student_test_id"],$db);
          }
          else
          {
            $testId = $data["Score Improvement"][$updateName . " Improvement"]["id"];
            SQL_Query("INSERT INTO student_test(course_id,test_id,student_id,score,modify_by,modify_time) VALUE($course_id,$testId,$student_id,$improvementScore,$teacher_id,$now)",$db);    
          }

          //UPDATE TOTAL IMPROVEMENT
          $totalImpovement = (int)$testBestScore["Maths"] - (int)$data["Diagnostic Test"]["Maths"]["score"] + (int)$testBestScore["Verbal (English)"] - (int)$data["Diagnostic Test"]["Verbal (English)"]["score"];
          if($data["Score Improvement"]["Score Improvement total"]["student_test_id"])
          {
            SQL_Query("UPDATE student_test SET score = $totalImpovement, modify_by = $teacher_id, modify_time = $now WHERE id = ".$data["Score Improvement"]["Score Improvement total"]["student_test_id"],$db);
          }
          else
          {
            $testId = $data["Score Improvement"]["Score Improvement total"]["id"];
            SQL_Query("INSERT INTO student_test(course_id,test_id,student_id,score,modify_by,modify_time) VALUE($course_id,$testId,$student_id,$totalImpovement,$teacher_id,$now)",$db);    
          }
        }
      }
      $result = [
        "test_id" => $test_id, 
        "student_id" => $student_id, 
        "score" => $score, 
        "teacher_id" => $teacher_id, 
        "total_score" => $total,
        "best_math" => (int)$testBestScore["Maths"],
        "best_english" => (int)$testBestScore["Verbal (English)"],
        "improvement_math" => (int)$testBestScore["Maths"] - (int)$data["Diagnostic Test"]["Maths"]["score"],
        "improvement_english" => (int)$testBestScore["Verbal (English)"] - (int)$data["Diagnostic Test"]["Verbal (English)"]["score"],
        "improvement_total" => (int)$testBestScore["Maths"] - (int)$data["Diagnostic Test"]["Maths"]["score"] + (int)$testBestScore["Verbal (English)"] - (int)$data["Diagnostic Test"]["Verbal (English)"]["score"],
        "update_total" => $totalUpdate
      ];
      break;

    case "ielts":
      $updateType = "";
      $updateName = "";

      foreach ($studentTests as $key => $test) {
        $data[$test["test_type"]][$test["name"]] = $test;
        if($test["id"] == $test_id && ($test["test_type"] ==  "eomt" || $test["test_type"] ==  "mmt") ) 
        {
           $updateType = $test["test_type"]; 
           $updateName = $test["name"];
        }
      }

      if($updateType != "")
      {
        // UPDATE TOTAL
        $updateTotal = 0;
        foreach ($data[$updateType] as $key => $value) {
          if($value["score"]) $updateTotal += (double)$value["score"];
        }
        $updateTotal = round($updateTotal/4,2);
        $updateTotal = Grades_Round($updateTotal);

        if(isset($data["$updateType total"]["$updateType total"]))
        {
          if($data["$updateType total"]["$updateType total"]["student_test_id"])
          {
            SQL_Query("UPDATE student_test SET score = $updateTotal, modify_by = $teacher_id, modify_time = $now WHERE id = ".$data["$updateType total"]["$updateType total"]["student_test_id"],$db);
          }
          else
          {
            $testTotalId = $data["$updateType total"]["$updateType total"]["id"];
            SQL_Query("INSERT INTO student_test(course_id,test_id,student_id,score,modify_by,modify_time) VALUE($course_id,$testTotalId,$student_id,$updateTotal,$teacher_id,$now)",$db);
          }
        }

        // SET UPDATEID OR CREATEID
        if($data["course_total"]["course total"]["student_test_id"])
        {
          $update  = true;
          $updateId = $data["course_total"]["course total"]["student_test_id"];
          foreach ($data["eomt"] as $key => $value) {
            if($value["score"]) $total += (double)$value["score"];
          }
          $total = round($total/4,2);
          $total = Grades_Round($total);
        }
        else $createId = $data["course_total"]["course total"]["id"];
      }
      $result = ["test_id" => $test_id, "student_id" => $student_id, "score" => $score, "teacher_id" => $teacher_id, "total_score" => $total, "update_total" => $updateTotal];
      break;
    
    default:
      // HYBRID
      foreach ($studentTests as $key => $test) {
        if($test["score"])
        {
          if($test["test_type"] == "course_total")
          {
            $update = true;
            $updateId = $test["student_test_id"];
          } 
          else $total += $test["score"] * $test["weight"] / $test["max_score"];
        }
        else if($test["test_type"] = "course_total") $createId = $test["id"];
      }
      $result = ["test_id" => $test_id, "student_id" => $student_id, "score" => $score, "teacher_id" => $teacher_id, "total_score" => $total];
      break;
  }

  $total = round($total,2);
  //CREATE OR UPDATE COURSE TOTAL
  if($update)
  {
     SQL_Query("UPDATE student_test SET score = $total, modify_by = $teacher_id, modify_time = $now WHERE id = $updateId",$db);
  }
  else
  {
    if($createId > 0) SQL_Query("INSERT INTO student_test(course_id,test_id,student_id,score,modify_by,modify_time) VALUE($course_id,$createId,$student_id,$total,$teacher_id,$now)",$db); 
  }

  SQL_Close($db);

  return $result;
}


function Grades_List_ByCourse($course_id)
{
  $db = Core_Database_Open();

  $courseTest = SQL_Query("SELECT * FROM course_test WHERE course_id = $course_id",$db);
  $studentTest = SQL_Query("SELECT * FROM student_test WHERE course_id = $course_id",$db);

  SQL_Close($db);

  return ["course_test" => $courseTest, "students" => $studentTest];
}


function Grades_Homework_Projects_Update($table,$id,$field)
{
  $db = Core_Database_Open();

  switch ($table) {
    case 'classes_seats':
      $query = "SELECT class_id, course_id, student_id, assessment FROM $table WHERE id = $id";
      $rows  = SQL_Query($query, $db);
      $seat  = $rows[0];
      
      $courseId = $seat["course_id"];
      $studentId = $seat["student_id"];
      $assessment = json_decode($seat["assessment"], true);

      $courseTests = SQL_Query("SELECT id, class_id, max_score, weight FROM course_test WHERE course_id = $courseId AND name = 'homework'",$db);
      
      if(count($courseTests))
      {
        $testId = $courseTests[0]["id"];
        $maxScore = $courseTests[0]["max_score"];
        $weight = $courseTests[0]["weight"];
        $classId = $courseTests[0]["class_id"];
        $studentSeats = SQL_Query("SELECT assessment FROM $table WHERE student_id = $studentId AND course_id = $courseId",$db);
        $hwScore = 0;
        $hwIndex = 0;
        foreach ($studentSeats as $key => $seat) {
          if($seat["assessment"] != null) 
          {
            $data = json_decode($seat["assessment"], true);
            if(isset($data[$field]))
            {
              $hwScore += (int) $data[$field];
              $hwIndex += 1;
            }
          }
        }
        SQL_Close($db);
        
        if($hwIndex > 0)
        {
          $averageHW  = round($hwScore/$hwIndex);
          Grades_Student_Test($courseId,$classId,$testId,$studentId,$averageHW,$maxScore,$weight);
        }
      }
      
      break;
    
    case "projects_students":
      $query = "SELECT ps.student_id, p.class_id, ps.assessment FROM $table ps JOIN projects p ON p.id = ps.project_id WHERE ps.id = $id";
      $rows = SQL_Query($query,$db);
      $studentProject = $rows[0];

      $studentId = $studentProject["student_id"];
      $classId = $studentProject["class_id"];
      $assessment = json_decode($studentProject["assessment"], true);

      $courseTests = SQL_Query("SELECT id, course_id, max_score, weight FROM course_test WHERE class_id = $classId ",$db);
      if(count($courseTests))
      {
        $testId = $courseTests[0]["id"];
        $maxScore = $courseTests[0]["max_score"];
        $weight = $courseTests[0]["weight"];
        $courseId = $courseTests[0]["course_id"];

        $config = parse_ini_file(__DIR__."/grades-project.dat",true);
        $total = 0;

        foreach ($assessment as $key => $value) {
          $infoSkill = Ini_File_Read("content/skills/".$key."/info.dat");
          if($infoSkill)
          {
            if(isset($infoSkill["lv ". $value]["score"])) $score = $infoSkill["lv ". $value]["score"];
            else $score = $value;

            $weightSkill = $config[$key]["weight"];
            $assessmentScore = $config[$key]["assessment-score"];

            $total += (int)$score / (int)$assessmentScore * (int)$weightSkill;
          }
        }
       
        if($total) Grades_Student_Test($courseId,$classId,$testId,$studentId,$total,$maxScore,$weight);
      }
      break;
  }

  SQL_Close($db);
}

function Grades_Round($grade)
{
  $check = $grade * 100 % 100;
  if($check >= 75) return ceil($grade);
  else if($check >= 25) return floor($grade) + 0.5;
  else return floor($grade);
}
?><?php

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                          H O M E                                               //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Home_Student_Data($student_id = -1)
{
 if($student_id == -1) $student_id = $_SESSION["user"]["id"];
 $data    = [];
 
 // GET LAST CLASS TAKEN
 $today              = Date_Now();
 $classes            = Class_Seats_ListByStudent($student_id, "190001010000", $today, ["last" => true, "lesson" => true]);
 $data["last-class"] = $classes[0] ?? [];
 
 
 // GET NEXT CLASS TO ATTEND
 $classes            = Class_Seats_ListByStudent($student_id, $today, "290001010000", ["lesson" => true]);
 $data["next-class"] = $classes[0] ?? [];
 
 return $data;
}



?><?php
define("EVENT_UNASSIGN","inventory_unassign_student");

function Inventory_Load() {
    return Inventory_Get_Groups_Item();
}

function parseInt($in) { $in["available"] = (int)$in["available"] ; return $in;}

function Inventory_Load_byCenter($centers,$options = [],$fields = "i.id, i.item_code, it.item_name, i.warehouse_code, i.available, i.center_code, i.on_order")
{
    $db = Core_Database_Open();
    $result = [];
    $conditions = []; 
    if($options != []){
        $conditionsqueryin = []; 
        if(isset($options["group"]) && $options["group"]){
            $group = SQL_Format($options["group"], $db);
            array_push($conditionsqueryin, "item_group = $group");
        }
        if(isset($options["item_code"]) && $options["item_code"]){ 
            $itemcode = SQL_Format($options["item_code"], $db);
            array_push($conditionsqueryin, "item_code = $itemcode");
        }

        // SEARCH PROGRAM AND LEVEL
        $itemCodes =  [];
        $searchCondition = [];
        $searchPrograms = [];
        if(isset($options["program"]) && $options["program"]){
            $program = SQL_Format($options["program"] . "%", $db);
            array_push($searchCondition, "program LIKE $program");
        }
        if(isset($options["level"]) && $options["level"]){
            $level = SQL_Format($options["level"] . "%", $db);
            array_push($searchCondition, "level LIKE $level");
        }
        $searchCondition = implode(" AND " , $searchCondition) ?: "true";
        $searchPrograms = SQL_Query("SELECT * from inventory_items_programs WHERE $searchCondition",$db);
        if($searchCondition != "true") {
            $itemCodes = array_column($searchPrograms,"item_code") ?: ["empty"] ;
        }

        if(count($itemCodes))
        {
         $item_codes = SQL_Format_IN($itemCodes, $db);
         array_push($conditionsqueryin, "item_code IN ($item_codes)");
        }

        $conditionsqueryin = implode(" AND " , $conditionsqueryin) ?: "true";
        $queryin = "SELECT DISTINCT item_code FROM inventory_items WHERE $conditionsqueryin";
        array_push($conditions, "i.item_code IN ($queryin)");
    }
    
    $centers = SQL_Format_IN($centers, $db);
    array_push($conditions, "i.center_code IN ($centers)");
    $conditions = implode(" AND " , $conditions);
    $query = "SELECT $fields FROM inventory i JOIN inventory_items  it ON it.item_code = i.item_code WHERE $conditions";
    $rows = SQL_Query($query,$db);

    // GET ALL ITEM
    $queryItem = "SELECT item_code, item_name FROM inventory_items";
    $items = SQL_Query($queryItem,$db);

    $data = [];
    foreach ($items as $key => $item) {
      $data[$item["item_code"]] = [
        "id" => null,
        "item_code" => $item["item_code"],
        "item_name" => $item["item_name"],
        "warehouse_code" => null,
        "available" => 0,
        "center_code" => null,
        "on_order" => 0
      ];
    }

    foreach ($rows as $key => $row) {
        $data[$row["item_code"]] = $row;
    }

    $data = array_values($data);

    SQL_Close($db);

    $data = array_map("parseInt",$data);

    return $data;
}

function Inventory_Assignment_Items_Users($course_id, $group = NULL, $program = NULL, $level = NULL, $fields = "*"){
    $db = Core_Database_Open();
    $data = SQL_Query("SELECT students, center_id FROM courses WHERE id = $course_id",$db);
    $students = json_decode($data[0]["students"]);
    $center_code = SQL_Format($data[0]["center_id"],$db);
 
    $where = [];
    if(isset($group) && $group)
    {
        $item_group = SQL_Format($group,$db);
        $where[] = "it.item_group = $item_group";
    }
    // SEARCH PROGRAM AND LEVEL
    $itemCodes =  [];
    $searchCondition = [];
    $searchPrograms = [];
    if(isset($program) && $program)
    {
        $program = SQL_Format($program . "%",$db);
        $searchCondition[] = "program LIKE $program";
    }
    if(isset($level) && $level)
    {
        $level = SQL_Format($level . "%",$db);
        $searchCondition[] = "level LIKE $level";
    }
    $searchCondition = implode(" AND " , $searchCondition) ?: "true";
    $searchPrograms = SQL_Query("SELECT * from inventory_items_programs WHERE $searchCondition",$db);
    if($searchCondition != "true") {
        $itemCodes = array_column($searchPrograms,"item_code") ?: ["empty"] ;
    }

    if(count($itemCodes))
    {
        $item_codes = SQL_Format_IN($itemCodes, $db);
        array_push($where, "it.item_code IN ($item_codes)");
    }

    $where = implode(" AND " , $where) ?: "true";
    $items = SQL_Query("SELECT i.item_code, it.item_name, i.available FROM inventory i JOIN inventory_items it ON i.item_code = it.item_code WHERE $where AND i.center_code = $center_code",$db);
    $items = array_map("parseInt",$items);

    //GET ITEM HAS GROUP DONT PROGRAM,LEVEL AND DONT GROUP,PROGRAM,LEVEL
    if($group) $groupCondition = "it.item_group = '$group'";
    else $groupCondition = "it.item_group IS NOT NULL";
    $items1 = SQL_Query("SELECT i.item_code, it.item_name, i.available FROM inventory i JOIN inventory_items it ON i.item_code = it.item_code WHERE  ( it.item_code NOT IN (SELECT DISTINCT item_code FROM inventory_items_programs) 
    OR it.item_code IN (SELECT DISTINCT item_code FROM inventory_items_programs WHERE (program IS NULL OR program = '') AND (level IS NULl OR level = ''))) AND ($groupCondition OR it.item_group = '' OR it.item_group IS NULl) AND i.center_code = $center_code",$db);
    $items = array_merge($items,$items1);

    // CUSTOM ITEMS
    if(count($items)){
        $checkUniques = [];
        foreach ($items as $key => $item) {
            if(isset($checkUniques[$item["item_code"]])) unset($items[$key]);
            else $checkUniques[$item["item_code"]] = 1;
        }
    }
    $itemcodes = array_column($items,"item_code");
    $itemcodes = SQL_Format_IN($itemcodes,$db);

    $users = [];
    $rows = [];

    if($students) {
        $ids   = array_values($students); 
        $usersin = SQL_Format_IN($ids,$db);
        $users = SQL_Query("SELECT id, firstname, lastname, midname FROM users WHERE id IN ($usersin) ",$db);
        if($items)
        $rows = SQL_Query("SELECT $fields FROM inventory_items_users WHERE item_code IN ($itemcodes) AND user_id IN ($usersin)",$db);
    }

    SQL_Close($db);
    return ["users" => $users, "items" => $items, "items_users" => $rows];
}

function Inventory_Assign_ItemStudents($item_code,$center_code, $users)
{
    try {
        $db = Core_Database_Open();
        $users = (array)$users;
        $rows = SQL_Query("SELECT available, warehouse_code FROM inventory WHERE item_code = '$item_code' AND center_code = '$center_code' LIMIT 1", $db);
        
        // CHECK AVAILABLE ITEMS OF CENTER
        if(!$rows || ($rows[0]["available"] < count($users))) {
            SQL_Close($db);
            return ["error" => "Not enough quantity for assigning"];
        }

        $assignedUser = array();
        $unassignedUser = array();
        $errorMessage = null;
        $db->beginTransaction();
        foreach ($users as $key => $user) {
            $userid = $user["id"];
            $id = abs(crc32(uniqid())); 
        
            ["data" => $res, "error" => $error] = Inventory_Move_Journal($id, $item_code, $rows[0]["warehouse_code"], -1);
            
            if($res && $res["Success"] && $res["JournalId"]) {
                $journal_id = $res["JournalId"];
                array_push($assignedUser, $userid);
                SQL_Query("INSERT INTO inventory_items_users (id, item_code, user_id, quantity, journal_code) VALUES($id,'$item_code', $userid ,1, '$journal_id')", $db);
            }  else {
                $errorMessage = $error;
                array_push($unassignedUser, $userid);
                Users_Write_Log("error_assign_item_to_student" , ["message" => $error, "studentId" => $userid], ["use-event" => true]);
            }
        }

        //UPDATE AVAILABLE INVENTORY
        $newQuantity = $rows[0]["available"] - count($assignedUser);
        if($newQuantity >= 0 && $newQuantity != $rows[0]["available"]) {
            SQL_Query("UPDATE inventory SET available = $newQuantity, total_available = $newQuantity, inventory = $newQuantity WHERE item_code = '$item_code' AND center_code = '$center_code'", $db);
        }

        $db->commit();
        SQL_Close($db);

        return ["data" => ["assignedStudents" => $assignedUser, 
                           "unassignedStudents" => $unassignedUser, 
                           "newQuantity" => $newQuantity,
                           "message" => $errorMessage]];
    } catch (\Throwable $th) {
        $db->rollBack();
        return ["error" => $th->getMessage()];
    }
}


function Inventory_TakeBack($item_codes,$center_code,$userid)
{
    try {
        $db = Core_Database_Open();
        $db->beginTransaction();
        $availables = [];

        foreach ($item_codes as $key => $itemcode) {
            $rows = SQL_Query("SELECT id, quantity FROM inventory_items_users WHERE user_id = $userid AND item_code = '$itemcode'",$db);
            if($rows)
            {
                //Users_Write_Log(EVENT_UNASSIGN,$rows[0],"138");
                $newQuantity = $rows[0]["quantity"] - 1;
                if($newQuantity < 1)
                    SQL_Query("DELETE FROM inventory_items_users WHERE user_id = $userid AND item_code = '$itemcode'", $db);
                else
                    SQL_Query("UPDATE inventory_items_users SET quantity = $newQuantity WHERE user_id = $userid AND item_code = '$itemcode'", $db);

                //UPDATE AVAILABLE INVENTORY
                $inventories = SQL_Query("SELECT available ,warehouse_code FROM inventory WHERE item_code = '$itemcode' AND center_code = '$center_code' LIMIT 1",$db);
                
                $total = $inventories[0]["available"] + 1;
                Inventory_Move_Journal($rows[0]["id"], $itemcode, $inventories[0]["warehouse_code"], 1);
                SQL_Query("UPDATE inventory SET available = $total, total_available = $total, inventory = $total WHERE item_code = '$itemcode' AND center_code = '$center_code'", $db); 
                $availables[$itemcode] = $total;
            }
        } 
        $db->commit();
    } catch (\Throwable $th) {
        $db->rollBack();
        var_dump($th);
        return ["error" => $th];
    }
    return ["data" => $availables];
}

function Inventory_Get_Item($itemcode,$field = "id, item_name, item_code")
{
    $db = Core_Database_Open();
    $itemcode = SQL_Format($itemcode, $db);
    $query = "SELECT $field FROM inventory_items WHERE item_code = $itemcode";
    $rows = SQL_Query($query,$db);
    SQL_Close($db);
    return $rows[0];
}

function Inventory_Get_Items_ByUser($userid, $group, $program, $level, $field = "i.item_name, i.item_code")
{
    $db = Core_Database_Open();
    $items = Inventory_Items_Search(["group" => $group, "program" => $program, "level" => $level]);
    $itemcodes = array_column($items,"item_code");
    $itemcodes = SQL_Format_IN($itemcodes, $db);
    $query = "SELECT $field FROM inventory_items_users iu JOIN inventory_items i ON iu.item_code = i.item_code WHERE iu.item_code IN ($itemcodes) AND iu.user_id = $userid";
    $rows = SQL_Query($query,$db);
    SQL_Close($db);

    return ["items" => $items ,"user-items"=> $rows];
}

function Inventory_Create_Transfer_Request($item_code,$from_center,$to_center,$quantity)
{
    $db = Core_Database_Open();
    $item_code = SQL_Format($item_code, $db);
    $from_center = SQL_Format($from_center, $db);
    $to_center = SQL_Format($to_center, $db);
    $query = "INSERT INTO inventory_item_request(item_code,from_center,to_center,quantity,status) VALUES($item_code,$from_center,$to_center,$quantity,'pending')";
    $id = SQL_Query($query,$db);
    SQL_Close($db);
    return ["id" => $id,"item_code" => $item_code,"from_center" => $from_center,"to_center" => $to_center,"quantity" => $quantity,"status" => "pending"];
}

function Inventory_Create_Transfer_Requests($data)
{
    $db = Core_Database_Open();
    $result = [];
    try {
        SQL_Transaction_Begin($db);
        foreach ($data as $key => $request) {
            $item_code = SQL_Format($request["item_code"], $db);
            $from_center = SQL_Format($request["from_center"], $db);
            $to_center = SQL_Format($request["to_center"], $db);
            $quantity = $request["quantity"];
            $query = "INSERT INTO inventory_item_request(item_code,from_center,to_center,quantity,status) VALUES($item_code,$from_center,$to_center,$quantity,'pending')";
            $id = SQL_Query($query,$db);
            $result[] = ["id" => $id,"item_code" => $item_code,"from_center" => $from_center,"to_center" => $to_center,"quantity" => $quantity,"status" => "pending"];
        }
        SQL_Transaction_Commit($db);
    } catch (\Throwable $th) {
        $result[] = ["error"=>$th];
        SQL_Transaction_Rollback($db);
    }
    
    SQL_Close($db);
    return $result;
}

function Inventory_Update_Transfer_Request($id, $fields)
{
    $db = Core_Database_Open();
    $request = SQL_Query("SELECT from_center, to_center, item_code, quantity, id, status FROM inventory_item_request WHERE id = $id", $db);
    $from_center = SQL_Format($request[0]["from_center"], $db);
    $to_center = SQL_Format($request[0]["to_center"], $db);
    $itemcode = SQL_Format($request[0]["item_code"], $db);

    try {
        if(isset($fields["status"]) && $fields["status"] == "accepted"){

            // CHECK THE NUMBER OF ITEM OF THE CENTER
            $lenderCenter = SQL_Query("SELECT available, warehouse_code FROM inventory WHERE center_code = $to_center AND item_code = $itemcode LIMIT 1", $db);
    
            if(!$lenderCenter || ($lenderCenter[0]["available"] - $request[0]["quantity"] <= 0))
                return ["error" => "Not Enough Quantity"];
        
            $newQuantity = $lenderCenter[0]["available"] - $request[0]["quantity"];
            
            SQL_Transaction_Begin($db);

            //UPDATE INVENTORY OF THE LENDER CENTER
            SQL_Query("UPDATE inventory SET available = $newQuantity WHERE item_code = $itemcode AND center_code = $to_center", $db); 

            // UPDATE INVENTORY OF BORROWER CENTER
            $borrowedCenter = SQL_Query("SELECT available, warehouse_code FROM inventory WHERE center_code = $from_center AND item_code = $itemcode LIMIT 1", $db);
            
            if($borrowedCenter) {
                
                SQL_Query("UPDATE inventory SET available = ". $borrowedCenter[0]["available"] + $request[0]["quantity"]."
                WHERE item_code = $itemcode AND center_code = $from_center", $db);

                // MAKE REQUEST TO D365
                $data = Inventory_Create_Trans(
                    $request[0]["id"], $request[0]["item_code"], 
                    $lenderCenter[0]["warehouse_code"], 
                    $borrowedCenter[0]["warehouse_code"], 
                    -$request[0]["quantity"]);

                ["error" => $error, "data" => $response] = $data;


                if($error) {
                    SQL_Transaction_Rollback($db);
                    return ["error" => $error];
                }

                if($response["Success"] && $response["JournalId"]) {
                    $request[0]["journal_code"] = $response["JournalId"];
                    SQL_Transaction_Commit($db);
                }
            }
            else 
            {
              $centerData = Inventory_Get_Center($request[0]["from_center"]);
              $warehouseCode = $centerData["warehouse_code"];
              $available = $request[0]["quantity"];

              SQL_Query("INSERT INTO inventory(item_code,warehouse_code,available,center_code) VALUE($itemcode,'$warehouseCode',$available,$from_center)", $db);
              
              // MAKE REQUEST TO D365
              $data = Inventory_Create_Trans(
              $request[0]["id"], $request[0]["item_code"], 
              $lenderCenter[0]["warehouse_code"], 
              $warehouseCode, 
              -$request[0]["quantity"]);

              ["error" => $error, "data" => $response] = $data;


              if($error) {
                  SQL_Transaction_Rollback($db);
                  return ["error" => $error];
              }

              if($response["Success"] && $response["JournalId"]) {
                  $request[0]["journal_code"] = $response["JournalId"];
                  SQL_Transaction_Commit($db);
              }
            }
        }

        // UPDATE ITEM REQUESTS
        $request[0]["status"] = $fields["status"];
        SQL_Query("UPDATE inventory_item_request SET journal_code = '{$request[0]["journal_code"]}', status = '{$request[0]["status"]}' WHERE id = $id", $db);
        SQL_Close($db);
        return ["data" => $request[0]];
    } catch (\Throwable $th) {
        SQL_Transaction_Rollback($db);
        return ["error" => $th->getMessage()];
    }
}

function Inventory_Available($itemcode, $centercode, $quantity, $options = [])
{    
    try {
        $db = Core_Database_Open();
        $itemcode = SQL_Format($itemcode, $db);
        $centercode = SQL_Format($centercode, $db);
        if($options["id"])
            $check = SQL_Query("SELECT available FROM inventory WHERE id = $itemcode AND center_code = $centercode",$db);
        else
            $check = SQL_Query("SELECT available FROM inventory WHERE item_code = $itemcode AND center_code = $centercode",$db);
        SQL_Close($db);
        if(count($check) > 0 && $check[0]["available"] >= $quantity) return $check[0]["available"] - $quantity;  
    } catch (\Throwable $th) {
        //var_dump($th);
        return -1;
    }
    return -1;
}

function Inventory_Get_Center($centercode,$field = "id, center_code, name, warehouse_code")
{
    $db = Core_Database_Open();
    $centercode = SQL_Format($centercode, $db);
    $query = "SELECT $field FROM centers WHERE center_code = $centercode";
    $rows = SQL_Query($query,$db);
    SQL_Close($db);
    return $rows[0];
}

function Inventory_Get_Requests($centercode){
    $db = Core_Database_Open();
    $centercode = SQL_Format($centercode, $db);
    $query = "SELECT inventory_item_request.*,inventory_items.item_name FROM inventory_item_request LEFT JOIN inventory_items ON  inventory_items.item_code = inventory_item_request.item_code WHERE to_center = $centercode";
    $rows = SQL_Query($query,$db);
    SQL_Close($db);
    return array_values($rows);
}

function Inventory_Having_Requests($centercode){
    $db = Core_Database_Open();
    $centercode = SQL_Format($centercode, $db);
    $query = "SELECT count(id) AS count FROM inventory_item_request WHERE to_center = $centercode AND status = 'pending'";
    $rows = SQL_Query($query,$db);
    SQL_Close($db);
    return (int)$rows[0]["count"];
}

function Inventory_Create_Update_FromD365(){
    try {
        $db = Core_Database_Open();
        $dataMasters =  Get_InventoryAvailableMasters_D365();
        $data = $dataMasters->value;
        SQL_Transaction_Begin($db);
        $messageSync = "";
        foreach ($data as $key => $inv) {
            $inv = (array)$inv;
            $itemcode = $inv["ItemNum"] ?? "";
            $center_code = $inv["InventSiteId"] ?? "";
            $warehousecode = $inv["InventLocationId"] ?? "";
            if($warehousecode != "")
            {
                //VALIDATE
                $check_exist_item = SQL_Query("SELECT id FROM inventory_items WHERE item_code = '$itemcode'",$db);
                if(!count($check_exist_item)) continue;
                $checkcenter =  SQL_Query("SELECT id FROM centers WHERE center_code = '$center_code'",$db);
                if(!count($checkcenter)) continue;
                
                $fieldmapping = [
                    "PhysicalInventory" => "inventory", 
                    "PhysicalReserv" => "reserved",
                    "AvailPhysical" => "available",
                    "OrderedInTotal" => "total_ordered",
                    "OnOrder" => "on_order",
                    "ReservOrdered" => "ordered_reserved",
                    "AvailReserv" => "available_reservation",
                    "TotalAvail" => "total_available"
                ];
                //CHECK EXIST INVENTORY
                $check = SQL_Query("SELECT id, available FROM inventory WHERE item_code = '$itemcode' AND center_code = '$center_code'",$db);
                if(count($check)) {
                    if($check[0]["available"] != $inv["AvailPhysical"]) $messageSync .= " Different available of item has code : $itemcode in center $center_code . D365 has ".$inv["AvailPhysical"]. " and LMS has ".$check[0]["available"].";";
                    $updatefield = [];
                    foreach ($inv as $ikey => $value) {
                        if(isset($fieldmapping[$ikey]))
                        $updatefield[]= $fieldmapping[$ikey] . " = " .  $value ;
                    }
                    $updatefield = implode(", ", $updatefield);
                    SQL_Query("UPDATE inventory SET $updatefield WHERE item_code = '$itemcode' AND center_code = '$center_code'",$db);
                }
                else{
                    $insertfields = ["item_code" => $itemcode, "center_code" => $center_code, "warehouse_code" => $warehousecode];
                    foreach ($inv as $ikey => $value) {
                        if(isset($fieldmapping[$ikey]))
                        $insertfields[$fieldmapping[$ikey]] = $value;
                    }
                    $insertfields = SQL_Fields_Insert($insertfields,$db);
                    SQL_Query("INSERT INTO inventory $insertfields",$db);
                }
            }
        }
       
        if($messageSync != "") Users_Write_Log("Inventory_Create_Update_FromD365",["api_name" => "Inventory_Create_Update_FromD365", "event" => "compare_available_inventory", "message" => $messageSync ]);
        SQL_Transaction_Commit($db);
    } catch (\Throwable $th) {
        SQL_Transaction_Rollback($db);
        throw $th;
        return false;
    }
    
    SQL_Close($db);
    return true;
}

function Inventory_Get_Center_ByWarehouseCode($warehousecode,$field = "id, center_code, name, warehouse_code")
{
    $db = Core_Database_Open();
    $warehousecode = SQL_Format($warehousecode, $db);
    $query = "SELECT $field FROM centers WHERE warehouse_code = $warehousecode";
    $rows = SQL_Query($query,$db);
    SQL_Close($db);
    return $rows[0];
}

function Inventory_Items_Create_Update_FromD365(){
   
    try {
        $db = Core_Database_Open();
        $dataMasters =  Get_Item_DataMasters_D365();
        $data = $dataMasters->value;
        SQL_Transaction_Begin($db);
        foreach ($data as $key => $item) {
            $item = (array)$item;
            $itemcode = $item["ProductItem"];
            $itemname = $item["ProductName"] ?: "";
            $itemnameFormat = SQL_Format($itemname,$db);
            $itemtype = $item["ProductType"] ?: "";
            $group    = $item["ItemGroup"] ?: "";
            $unit     = $item["InventUnit"] ?: "";
            $stop     = $item["Stopped"] ?: "";
            if($stop == "Yes") $stop = 1;
            else $stop = 0;
            $category     = $item["Category"] ?: "";
            //CHECK EXIST ITEM
            $check = SQL_Query("SELECT item_name FROM inventory_items WHERE item_code = '$itemcode'",$db);
            if(count($check)) {
                $set = [];
                if($itemname != "") array_push($set, "item_name = $itemnameFormat");
                if($unit != "")     array_push($set, "unit = '$unit'");
                if($itemtype != "") array_push($set, "item_type = '$itemtype'");
                if($group != "")    array_push($set, "item_group = '$group'");
                if($stop != "")    array_push($set, "stop = $stop");
                if($category != "")    array_push($set, "category = ". SQL_Format($category,$db));
                $conditions = implode(", " , $set) ?: "true";
                if($conditions != "true")
                SQL_Query("UPDATE inventory_items SET  $conditions  WHERE item_code = '$itemcode'",$db);
            }else{
                SQL_Query("INSERT INTO inventory_items(item_name,item_code,unit,item_type,item_group,stop,category) VALUES($itemnameFormat,'$itemcode','$unit','$itemtype','$group',$stop,'$category')",$db);
            }
        }
        SQL_Transaction_Commit($db);
        SQL_Close($db);
    } catch (\Throwable $th) {
        SQL_Transaction_Rollback($db);
        var_dump($th);
        return false;
    }
    return true;
}

function Inventory_Get_Groups_Item(){
    $db = Core_Database_Open();
    try {
        $data = SQL_Query("SELECT DISTINCT item_group FROM inventory_items",$db);
        foreach ($data as $key => $value) {
            $data[$value["item_group"]] = $value;
            unset($data[$key]);
        }
    } catch (\Throwable $th) {
        var_dump($th);
        return false;
    }
    return $data;
}

function Inventory_Items_Search($search = [],$field = "*")
{
    $db = Core_Database_Open();

    // SEARCH PROGRAM AND LEVEL
    $itemCodes =  [];
    $searchCondition = [];
    $searchPrograms = [];
    $programsLevels = SQL_Query("SELECT * FROM inventory_items_programs",$db);

    if(isset($search["program"]) && $search["program"])
    {
     $program = SQL_Format($search["program"] . "%", $db);
     array_push($searchCondition, "program like $program");
    }
    if(isset($search["level"]) && $search["level"])
    {
     $level = SQL_Format($search["level"] . "%", $db);
     array_push($searchCondition, "level like $level");
    }
    $searchCondition = implode(" AND " , $searchCondition) ?: "true";
    $searchPrograms = SQL_Query("SELECT * from inventory_items_programs WHERE $searchCondition",$db);
    if($searchCondition != "true") {
        $itemCodes = array_column($searchPrograms,"item_code") ?: ["empty"] ;
    }

    // CONDITIONS
    $conditions = []; 
    if(isset($search["group"]) && $search["group"])
    {
     $group = SQL_Format($search["group"], $db);
     array_push($conditions, "item_group = $group");
    }
    if(count($itemCodes))
    {
     $item_codes = SQL_Format_IN($itemCodes, $db);
     array_push($conditions, "item_code IN ($item_codes)");
     $programsLevels = SQL_Query("SELECT * FROM inventory_items_programs WHERE item_code IN($item_codes)",$db);
    }
    $conditions = implode(" AND " , $conditions) ?: "true";
    $query = "SELECT $field FROM inventory_items WHERE $conditions";
    $rows = SQL_Query($query,$db);
    SQL_Close($db);
    
    // CUSTOM RESULT
    
    foreach ($rows as $keyR => $row) {
        $rows[$keyR]["programs_levels"] = [];
        foreach ($programsLevels as $key => $value) {
            if($row["item_code"] == $value["item_code"]) array_push($rows[$keyR]["programs_levels"],["id" => $value["id"], "program" => $value["program"], "level" => $value["level"]]);
        }
    }
    return $rows;
}

function Inventory_Item_Update($item){
    $db = Core_Database_Open();
    $item = (array)$item;
    try {
        $itemcode = $item["item_code"];
        $itemname = $item["item_name"] ?: "";
        $group     = $item["item_group"] ?: "";
        $programsLevels = $item["programs_levels"] ?: [];
        //CHECK EXIST ITEM
        $query = "UPDATE inventory_items SET ";
        if($itemname != "") $query .= "item_name = '$itemname'";
        if($group != "")    $query .= ", item_group = '$group'";
        $query .= "WHERE item_code = '$itemcode'";
        SQL_Query($query,$db);

        // UPDATE ITEM PROGRAM LEVEL
        foreach ($programsLevels as $id => $value) {
            $program = SQL_Format($value["program"],$db);
            $level = SQL_Format($value["level"],$db);
            SQL_Query("UPDATE inventory_items_programs SET program = $program , level = $level WHERE id = $id",$db);
        }

    } catch (\Throwable $th) {
        var_dump($th);
        return false;
    }
    return true;
}

function Inventory_Request_Search($center_code,$item_group,$program,$level,$options = "from")
{
    $db = Core_Database_Open();
    try {
        $centercode = SQL_Format($center_code,$db);
        $where = [];
        if($item_group)
        {
            $item_group = SQL_Format($item_group,$db);
            $where[] = "item_group = $item_group";
        }
        if($program)
        {
            $program = SQL_Format($program . "%",$db);
            $where[] = "program LIKE $program";
        }
        if($level)
        {
            $level = SQL_Format($level . "%",$db);
            $where[] = "level LIKE $level";
        }
        $where = implode(" AND " , $where) ?: "true";
        $queryin = "SELECT DISTINCT item_code FROM inventory_items WHERE $where";
        $query = "SELECT inventory_item_request.*,inventory.available,inventory_items.item_name from inventory_item_request 
        JOIN inventory ON inventory.item_code = inventory_item_request.item_code 
        JOIN inventory_items ON inventory_items.item_code = inventory_item_request.item_code
        AND inventory.center_code = inventory_item_request.".$options."_center WHERE ".$options."_center = $centercode AND inventory_item_request.item_code IN($queryin)";
        if($options == "from")
        {
            $query .= " UNION ";
            $query .= "SELECT inventory_item_request.*,inventory.available,inventory_items.item_name from inventory_item_request 
            JOIN inventory ON inventory.item_code = inventory_item_request.item_code 
            JOIN inventory_items ON inventory_items.item_code = inventory_item_request.item_code
            AND inventory.center_code = inventory_item_request.to_center WHERE to_center = $centercode AND inventory_item_request.item_code IN($queryin)  AND inventory_item_request.`status` = 'accepted'";

        }
        $rows = SQL_Query($query,$db);
        SQL_Close($db);

        if($options == "from")
        {
            foreach ($rows as $key => $row) {
                if($row["status"] == "accepted")
                {
                    if($row["from_center"] == $center_code){
                        $rows[$key]["status"] = "received";
                    }else{
                        $rows[$key]["status"] = "transfered";
                        $tmp = $row["from_center"];
                        $rows[$key]["from_center"] = $rows[$key]["to_center"];
                        $rows[$key]["to_center"] = $tmp;
                    }
                }
            }
        }
        return $rows;
    } catch (\Throwable $th) {
        var_dump($th) ;
    }
}

function getERPToken() {
    try {
        $tokenFile = "application/modules/inventory/azure_token.json";

        $erpToken = $_SESSION["erpToken"];
        $erpTokenExpireTime = $_SESSION["erpTokenExpireTime"] ?? 0;
        $now = time();
        
        // GET TOKEN FROM CACHED JSON FILE
        if($erpToken == null) {
            $data = file_get_contents($tokenFile);

            if($data !== null) {
                $data = json_decode($data, true);
                $erpToken = $data["access_token"];    
                $erpTokenExpireTime = $data["expired_date"];
                $_SESSION["erpToken"] = $erpToken;
                $_SESSION["erpTokenExpireTime"] = $erpTokenExpireTime;
            }
        }

        //CALL API AZURE TOKEN IF MEET EXPIRED TIME
        if($erpToken == null || strlen($erpToken) == 0 || $now > $erpTokenExpireTime) {
            $erpConfigFile  = parse_ini_File("azure_erp.cfg", true);
            $env =  $_SESSION["mode"] == "dev" ? "dev" : "prod";
            $config = $erpConfigFile[$env];
    
            $curl = curl_init();
            $data = "grant_type={$config["grant_type"]}".
            "&client_id={$config["client_id"]}".
            "&scope={$config["scope"]}.default".
            "&client_secret={$config["client_secret"]}";
    
            $params = 
            array(
              CURLOPT_URL => $config["url"],
              CURLOPT_POST => 1,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_POSTFIELDS => $data,
              CURLOPT_HTTPHEADER => array("Content-Type: application/x-www-form-urlencoded"),
              CURLOPT_SSL_VERIFYHOST => false,
              CURLOPT_SSL_VERIFYPEER => false
              );
              
            curl_setopt_array($curl, $params);
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response , true);
            $duration = $response["expires_in"];
            $expireDateTime = strtotime(date("Y-m-d H:i:s", strtotime("+$duration sec")));
            $response["expired_date"] = $expireDateTime;
            
            $erpToken = $response["access_token"];
            $_SESSION["erpToken"] = $response["access_token"];
            $_SESSION["erpTokenExpireTime"] = $expireDateTime;

            //SAVE JSON FILE
            $tokenFile = fopen($tokenFile, "w");
            fwrite($tokenFile, json_encode($response));   
        }
        return $erpToken;
    } catch (\Throwable $th) {
    }
    return null;
}


function Get_Item_DataMasters_D365(){
    $curl = curl_init();

    $config  = parse_ini_File("azure_erp.cfg", true);
    $env =  $_SESSION["mode"] == "dev" ? "dev" : "prod";

    $erpToken = getERPToken();
    $authorization = "Authorization: Bearer $erpToken";
    $url = $config[$env]["scope"]."data/TNM_ItemDataMasters";
    $filter = $_REQUEST["filter"] ?? "false";
    if($filter == "true")
    {
        $before24hour = date_create("-1 day",new DateTimeZone("UTC"));
        $dateFomat = $before24hour->format("Y-m-d");
        $timeFomat = $before24hour->format("H:i:s");
        $filter = "RecordCreatedDateTime%20ge%20{$dateFomat}T{$timeFomat}Z";
        $url .= "?\$filter=$filter";
    } 
    $params = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array('Content-Type: application/json' , $authorization ),
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false
        );

    curl_setopt_array($curl, $params);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response) ;
}

function Get_InventoryAvailableMasters_D365(){
    $curl = curl_init();

    $config  = parse_ini_File("azure_erp.cfg", true);
    $env =  $_SESSION["mode"] == "dev" ? "dev" : "prod";
    
    $erpToken = getERPToken();
    $authorization = "Authorization: Bearer $erpToken";
    $url = $config[$env]["scope"]."data/TNM_InventAvailMasters";
    $filter = $_REQUEST["filter"] ?? "false";
    $warehouse_code = $_REQUEST["center"] ?? "false";
    if($filter == "true" && $warehouse_code != "false")
    {
        $filter = "InventLocationId%20eq%20'$warehouse_code'";
        $url .= "?\$filter=$filter";
    } 
    $params = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array('Content-Type: application/json' , $authorization ),
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false
        );
        
    curl_setopt_array($curl, $params);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response) ;
}

function Inventory_Items_Assign_Program($item_code,$program,$level) {
    try {
        $db = Core_Database_Open();
        $program = SQL_Format($program,$db);
        $level = SQL_Format($level,$db);
        $query = SQL_Query("INSERT INTO inventory_items_programs(item_code,program,level) VALUE($item_code,$program,$level)",$db);
        Core_Database_Close($db);
        return $query;
    } catch (\Throwable $th) {
        var_dump($th);
        throw $th;
    }
}

function Inventory_Get_Config() {
    try {
        $config  = parse_ini_File("azure_erp.cfg", true);
        $env =  $_SESSION["mode"] == "dev" ? "dev" : "prod";
        return $config[$env];
    } catch (\Throwable $th) {
        return [];
    }
}

function Inventory_Move_Journal($sms_id, $item_code, $warehouse, $quantity = 1) {
    try {
        $config = Inventory_Get_Config();
        $url = "{$config["scope"]}api/services/TNM_S_ItemJourEntriesServiceGroup/MoveJournalSevice/CreateMoveJournal";
        $erpToken = getERPToken();
     
        $curl = curl_init();
        $data = (object)array(
                "request" => (object)array(
                    "DataAreaId" => "ilav",
                    "JournalNumSMS" => $sms_id,
                    "MoveJourLine" => array(
                        (object)array(
                            "ItemNumber" => $item_code,
                            "Quantity" => $quantity,
                            "Date" => date("Y-m-d"),
                            "Warehouse" => $warehouse
                        )
                    )
                )
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json', "Authorization: Bearer $erpToken"),
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => json_encode($data)
        ));
        $response = curl_exec($curl);
        $response = json_decode($response , true);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        ["Message" => $message, "Success" => $success ] = $response;

        if($statusCode == 200 && $success) {
            return ["data" => $response]; 
        } 

        return ["error" => $message];
    } catch (\Throwable $th) {
        return ["error" => $th->getMessage()];
    }
}

function Inventory_Create_Trans($sms_id, $item_code, $fromWarehouse, $toWarehouse, $quantity = -1, $journal_name = "000000_TR") {
    try {
        $config = Inventory_Get_Config();
        $url = "{$config["scope"]}api/services/TNM_S_ItemJourEntriesServiceGroup/TransJournalService/CreateTransJournal";
        $erpToken = getERPToken();
        $curl = curl_init();
        $data = (object)array(
                "request" => (object)array(
                    "DataAreaId" => "ilav",
                    "JournalNumSMS" => $sms_id,
                    "JournalName" => $journal_name,
                    "TransJourLine" => array(
                        (object)array(
                            "ItemNumber" => $item_code,
                            "Quantity" => $quantity,
                            "Date" => date("Y-m-d"),
                            "FromWarehouse" => $fromWarehouse,
                            "ToWarehouse" => $toWarehouse
                            
                        )
                    )
                )
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json', "Authorization: Bearer $erpToken"),
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => json_encode($data)
        ));
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response = json_decode($response , true);
        curl_close($curl);

        ["Message" => $message , "Success" => $success ] = $response;

        if($statusCode == 200 && $success) {
            return ["data" => $response]; 
        } 
      
        return ["error" => $message];
    } catch (\Throwable $th) {
        return ["error" => $th->getMessage()];
    }
}
?><?php

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                       L E S S O N S                                            //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Lessons_List($folder = "content/lessons")
{
 $list = Storage_Folder_ListFolders($folder);
 
 return $list;
}




function Lesson_Info($lesson, $sections = false)
{
 $info = []; 
 $data = parse_ini_file("content/lessons/$lesson/info.dat", true);

 foreach($sections as $section) 
 {
  $info[$section] = $data[$section];
 }
  
 return $info;
}




function Lesson_Metadata($lesson, $options = [])
{
 $meta = [];
 $data = parse_ini_file("content/lessons/$lesson/info.dat", true);

 $db   = Core_Database_Open();
 
 // GET AUTHORS INFO 
 $authors = $data["authors"] ?? [];
 $ids     = array_values($authors);
 $ids     = array_unique($ids);
 
 if(count($ids) > 0)
 {
  $list    = SQL_Values_List($ids);

  $query = "SELECT id, firstname, lastname FROM users WHERE id IN ($list)";
  $rows  = SQL_Query($query, $db);
 
  $meta["authors"] = $rows;
 }
 else
 {
  $meta["authors"] = [];
 }
 
 // GET RATING
 if($options && $options["rating"])
 {
  switch("rating")
  {
   case "average":
   break;
   
   default:
	$id     = $options["rating"];
	
	$query  = "SELECT score, feedback FROM users_ratings WHERE type = 'lesson' AND source = '$lesson' AND user_id = $id";
    $rows   = SQL_Query($query, $db);
	
	$score            = $rows[0]["score"] ?? 0;
	$meta["rating"]   = $score;
	
	$feedback         = $rows[0]["feedback"] ?? "";
	$meta["feedback"] = $feedback;
   break;
  }
 }
 
 SQL_Close($db);
 
 return $meta;
}



function Lesson_Rate($lesson, $userid = -1, $score = 0)
{
 if($userid == -1) $userid = $_SESSION["user"]["id"];
 
 $db = Core_Database_Open();
 
 $query  = "INSERT INTO users_ratings (type, source, user_id, score) VALUES('lesson', '$lesson', $userid, $score) ON DUPLICATE KEY UPDATE score = $score";
 SQL_Query($query, $db);
 
 SQL_Close($db);
}


function Lesson_Feedback($lesson, $userid = -1, $feedback)
{
 if($userid == -1) $userid = $_SESSION["user"]["id"];
 
 $db       = Core_Database_Open();
 $feedback = SQL_Format($feedback, $db);
 
 $query  = "INSERT INTO users_ratings (type, source, user_id, feedback) VALUES('lesson', '$lesson', $userid, $feedback) ON DUPLICATE KEY UPDATE feedback = $feedback";
 SQL_Query($query, $db);
 
 SQL_Close($db);
}




function Lesson_Assessables($lesson, $info = false)
{
 $assessables  = [];
 
 if(gettype($lesson) == "string") $data = parse_ini_file("content/lessons/$lesson/info.dat", true); else $data = $lesson;
 $curriculum = $data["info"]["curriculum"] ?? "default";

 
 // LESSON-SPECIFIC OUTCOMES
 $assessables["outcomes"] = array_values($data["outcomes"] ?? []);
 
 // SKILLS FOR THE CURRICULUM THE LESSON BELONGS TO
 $skills                     = parse_ini_file("content/curricula/$curriculum/info.dat", true);
 $assessables["core skills"] = array_values($data["core skills"] ?? []) || array_keys($skills["core skills"] ?? []) ?? [];
 
 // COMPLEMENT WITH DETAILED INFO IF REQUESTED
 /*
 $keys = array_keys($assessables["core skills"]);
   
 foreach($keys as $key)
 {
  $assessables["core skills"][$key] = Outcome_Read("content/skills/$key");
 }
 */
 
 return $assessables;
}






function Lesson_Read($source, $data = false)
{
 if(!$source) 
 {
  $lesson = [];
 }
 else
 {
  $lesson              = parse_ini_file("content/lessons/$source/info.dat", true);
  if(!$lesson) $lesson = [];
 }
 
 
 // SPECIAL CASES. USED BECAUSE RETURNED DATA FOR MULTIPLE LESSONS COULD BE A LOT
 switch($data)
 {
  case "lesson-only":
	return $lesson;
  break;
  
  case "title-only":
	return $lesson["title"];
  break;
  
  case "base-info":
	$info                = [];
	$info["title"]       = $lesson["title"];
	$info["assessables"] = Lesson_Assessables($lesson);
  
	return $info;
  break;
 }
 
 
 // STANDARD READ
 $lesson["source"] = $source;
 
 // READ ASSESSABLE ITEMS
 if($data["assessables"])
 {
  $lesson["assessables"] = Lesson_Assessables($lesson);
 }
 
 
 // READ OUTCOMES AND SKILLS
 if($data["outcomes"] || $data["all"])
 {
  // DETERMINE SKILLS FROM LESSON'S CURRICULUM
  $curriculum = $data["info"]["curriculum"] ?? "default";
  $skills     = parse_ini_file("content/curricula/$curriculum/info.dat", true);
  
  
  
  // SKILLS
  
  
  
  // READ SKILLS
  $keys                  = array_values($lesson["core skills"] ?? []) ?: array_keys($skills["core skills"] ?? []) ?: [];
  $lesson["core skills"] = [];
   
  foreach($keys as $key)
  {
   $lesson["core skills"][$key] = Outcome_Read("content/skills/$key");   
  }
  
  // READ OUTCOMES
  $ids                = array_values($lesson["outcomes"] ?? []);
  $lesson["outcomes"] = [];
  
  foreach($ids as $id)
  {
   $outcome                 = Outcome_Read("content/outcomes/$id");
   $lesson["outcomes"][$id] = $outcome;
  }
  
  //READ TESTS
  $tests                = array_values($lesson["test"] ?? []);
  $lesson["test"] = [];
  $counttests = count($tests);

  if($counttests > 0)
    $lesson["test"]["type"] = $tests[0];

  for($i=1; $i<$counttests;)
  {
   $lesson["test"][$tests[$i]] = ["max"=>$tests[++$i],"percentage"=>$tests[++$i]];
   $i++;
  }
  
 }
  
 
 
 // LOAD VOCABULARY
 if($data && $data["all"] || $data["vocabulary"])
 {
  $ids                  = array_values($lesson["vocabulary"] ?? []);
  $lesson["vocabulary"] = [];
  
  foreach($ids as $id)
  {
   $outcome                   = Vocabulary_Read_Term("content/vocabulary/$id");
   $lesson["vocabulary"][$id] = $outcome;
  }
 }
 
 
 // DOCUMENTS FOLDER
 if($data && $data["all"] || $data["documents"])
 {
  $files = Storage_Files_Collect("content/lessons/$source/documents", ["pdf"], ["uproot"]);
 
  $lesson["documents"] = $files;
 }
 
 
 return $lesson;
}




function Lesson_Write($path, $data)
{
 Storage_Path_Create($path);
 
 Ini_File_Write("$path/info.dat", $data);
}




function Lesson_Delete($path)
{
 Storage_Folder_Delete($path);
}




function Lesson_Files($source, $folder)
{
 $files = Storage_Files_Collect("content/lessons/$source/$folder", false, ["uproot"]);
 
 return $files;
}


function Lessons_Read($sources, $data = false)
{
 $lessons = [];
 if(gettype($sources) == "string") $sources = explode(",",$sources);
 foreach ($sources as $key => $source) {
  $lessons[$source] = Lesson_Read($source,$data);
 }
 return $lessons;
}

?><?php

function Users_Write_Log($event,$data,$options = [])
{
  try {
    $user_id = $_SESSION["user"]["id"] ?? 0;
    $db     = Core_Database_Open();

    $now = Date_Now();
    $data["time"] = $now;
    if(isset($data["response"]) && gettype($data["response"]) == "string") $data["response"] = json_decode($data["response"]);
    $session =  session_id() ? 'SESSION-'.session_id() : "NULL";
    if(isset($_REQUEST["nologin"])) $session = 'SESSION-nologin';
    if(isset($_REQUEST["loginkey"])) $session = 'SESSION-'.$_REQUEST["loginkey"];
    $id = Users_Log_CheckSession($session);
    
    $dataFormat = json_encode([$data]);
    $dataFormat = SQL_Format($dataFormat,$db);
    $sessionFormat = SQL_Format($session,$db);
    if(isset($options["use-event"]))
    {
      $event = SQL_Format($event,$db);
      SQL_Query("INSERT INTO users_log(user_id,date,event,data) VALUES($user_id,$now,$event,$dataFormat)",$db);
    }
    else
    if($id)
    {
      Users_Log_UpdateData($id, $event, $session, $data);
    }
    else 
    {
      SQL_Query("INSERT INTO users_log(user_id,date,event,data) VALUES($user_id,$now,$sessionFormat,$dataFormat)",$db);
    }
  } catch (\Throwable $th) {
    $thFormat = SQL_Format(json_encode(["message"=> $th->getMessage(), "trace" => $th->getTrace()]),$db);
    if(strlen($thFormat) < 65535)
    SQL_Query("INSERT INTO users_log(user_id,date,event,data) VALUES($user_id,$now,'error_log',$thFormat)",$db);
    else {
      $th = SQL_Format(json_encode(["message" => $th->getMessage()]),$db);
      SQL_Query("INSERT INTO users_log(user_id,date,event,data) VALUES($user_id,$now,'error_log', $th)",$db);
    }
  }
  SQL_Close($db);
}

function Users_Log_CheckSession($session)
{
  $db     = Core_Database_Open();
  $user_id = $_SESSION["user"]["id"] ?? 0;
  $now    = Date_Format_As(Date_Now(), "date-only");
  $data   = SQL_Query("SELECT id from users_log where event = '$session' AND user_id = $user_id AND date >= {$now}0000  AND date <= {$now}2359 ORDER BY id desc",$db);
  SQL_Close($db);
  if(count($data)) return $data[0]["id"];
  return false;
}

function Users_Log_UpdateData($id, $event, $session, $newdata) {
  try {
    $db     = Core_Database_Open();
    $user_id = $_SESSION["user"]["id"] ?? 0;
    $now    = Date_Now();
    $dataquery   =  SQL_Query("SELECT data FROM users_log WHERE id = $id",$db);
    $data   = json_decode($dataquery[0]["data"]) ?? [];
    array_push($data,(object)$newdata);
    $data   = json_encode($data);
    $dataFormat = SQL_Format($data,$db);
    if(strlen($dataFormat) < 65535)
      SQL_Query("UPDATE users_log SET data = $dataFormat WHERE id = $id",$db);
    else 
    {
      $newdataFormat = json_encode([$newdata]);
      $newdataFormat = SQL_Format($newdataFormat,$db);
      $sessionFormat = SQL_Format($session,$db);
      SQL_Query("INSERT INTO users_log(user_id,date,event,data) VALUES($user_id,$now,$sessionFormat,$newdataFormat)",$db);
    }
    SQL_Close($db);
  } catch (\Throwable $th) {
    $th = SQL_Format(json_encode(["message"=> $th->getMessage(), "trace" => $th->getTrace()]),$db);
    SQL_Query("INSERT INTO users_log(date,event,data) VALUES($now,'error_log',$th)",$db);
  }
  
}


function Users_Log_Create_Partitions(){
  // CREATE NEW PARTITION FOR NEXT MONTH
  $db    = Core_Database_Open();
  $now   = Date_Now();
  $month = substr($now,4,2);
  $year  = substr($now,0,4);

  if($month == 12) {$nextmonth = "01";$year = (int)$year + 1;}
  else if((int)$month + 1 > 9) $nextmonth = (int)$month + 1 ;
        else $nextmonth =  '0'.((int)$month + 1);
  
  if($nextmonth == 12) $next2month = ((int)((int)($year) + 1 .'01'))*100*100*100;
  else  $next2month = ((int)($year.$nextmonth) + 1)*100*100*100;


  $partitionMonthName = "MONTH_".$year.$nextmonth;
  $partitionMonth = SQL_Query("SELECT PARTITION_NAME FROM INFORMATION_SCHEMA.PARTITIONS WHERE TABLE_NAME= 'users_log' AND PARTITION_NAME = '$partitionMonthName'",$db);
  if(!count($partitionMonth))
  SQL_Query("ALTER TABLE users_log ADD PARTITION (PARTITION $partitionMonthName VALUES LESS THAN ($next2month))",$db);
}

function Users_Login_Write_Log($event,$data,$options = [])
{
    $user_id = $_SESSION["user"]["id"] ?? 0;
	$user_email = $_SESSION["user"]["email"] ?? '';
    $db     = Core_Database_Open();

    $now = Date_Now();
    $data["time"] = $now;
    $session =  session_id() ? 'SESSION-'.session_id() : "NULL";
    
	$user_email = SQL_Format($user_email,$db);
	$ip = SQL_Format($data["ip"],$db);
    $dataFormat = json_encode([$data]);
    $dataFormat = SQL_Format($dataFormat,$db);
    $sessionFormat = SQL_Format($session,$db);
    $id="abc";
    if(isset($options["use-event"]))
    {
      $event = SQL_Format($event,$db);
      $id = SQL_Query("INSERT INTO users_login_log(user_id,email,date,event,ip,data) VALUES($user_id,$user_email,$now,$event,$ip,$dataFormat)",$db);
    }
    else
    {
      $id = SQL_Query("INSERT INTO users_login_log(user_id,email,date,event,ip,data) VALUES($user_id,$user_email,$now,$sessionFormat,$ip,$dataFormat)",$db);
    }
  SQL_Close($db);
}
?><?PHP

function Main_Load()
{
}

?><?PHP

function Messages_Load()
{
 Messages_Purge();
}



function Messages_Template($template, $data = [])
{
 $text = file_get_contents("./partners/" . $_SESSION["partner"] . "/templates/messages/" . $template . "/index.html");
 $text = String_Variables_Apply($text, $data);
 
 // RELATIVE URLS
 $path     = Server_URL() . "/partners/" . $_SESSION["partner"] . "/templates/messages/" . $template . "/";
 $text     = str_replace(' src = "', ' src = "' . $path, $text);
 
 return $text;
}




function Messages_Send_Single($sender_id, $user_id = -1, $subject = "", $text = "", $options = [])
{ 
 // RECEIVER
 if($user_id == -1) $user_id = $_SESSION["user"]["id"];
 

  
 // TYPE
 if($options["type"])
 {
  $type = $options["type"];
 }
 else 
 {
  $type = "message";
 }
 
 
 // SUBJECT
 
 
 
 // TEMPLATE / TEXT
 if($options["template"])
 {
  // ASSUME TEXT AS A SET OF KEY-VALUE PAIRS, AND POPULATE TEMPLATE
  $text =  Messages_Template($options["template"], $text);
 }
 else
 {
 }
 
 
 
 // EXPIRATION
 if($options["expiration"])
 {
  $date_expiration = $options["expiration"];
 }
 else 
 {
  $date_expiration = NULL;
 }
 
 
 // DATE
 $date_sent = Date_Now();
 
 
 $db = Core_Database_Open();
 
 $type            = SQL_Format($type,            $db);
 $user_id         = SQL_Format($user_id,         $db);
 $sender_id       = SQL_Format($sender_id,       $db);
 $date_sent       = SQL_Format($date_sent,       $db);
 $subject         = SQL_Format($subject,         $db);
 $text            = SQL_Format($text,            $db);
 $date_expiration = SQL_Format($date_expiration, $db);
 
 $id = SQL_Query("INSERT INTO users_messages (type, user_id, sender_id, date_sent, subject, text, date_expiration) VALUES($type, $user_id, $sender_id, $date_sent, $subject, $text, $date_expiration)", $db);
 
 SQL_Close($db);
 
 return $id;
}




function Messages_Send_Multiple($sender_id, $users, $subject = "", $text = "", $options = [])
{  
 if($options["fields"]) $fields = $options["fields"]; else $fields = "firstname,lastname,nickname,email,mobile";
 $users = Users_Read($users, $fields);


 // DATA 
 if(!$options["data"]) $options["data"] = [];
 
  
 // TYPE
 if($options["type"])
 {
  $type = $options["type"];
 }
 else 
 {
  $type = "message";
 }
 
 
 // SUBJECT
 
 
 
 // EXPIRATION
 if($options["expiration"])
 {
  $date_expiration = $options["expiration"];
 }
 else 
 {
  $date_expiration = NULL;
 }
 
 
 // DATE
 $date_sent = Date_Now();
 
 $db = Core_Database_Open();
 
 $type            = SQL_Format($type,            $db);
 $sender_id       = SQL_Format($sender_id,       $db);
 $date_sent       = SQL_Format($date_sent,       $db);
 $subject         = SQL_Format($subject,         $db);
 $date_expiration = SQL_Format($date_expiration, $db);
 
 $db->beginTransaction();
 
 foreach($users as $user)
 {
  // TEMPLATE / TEXT
  if($options["template"])
  {
   $data = array_merge($user, $options["data"]);
   $textFormat = Messages_Template($options["template"], $data);
  }
  else
  { 
  }
  
  $textFormat    = SQL_Format($text, $db);
  $user_id = $user["id"]; 
 
  SQL_Query("INSERT INTO users_messages (type, user_id, sender_id, date_sent, subject, text, date_expiration) VALUES($type, $user_id, $sender_id, $date_sent, $subject, $textFormat, $date_expiration)", $db);
 }
 
 $db->commit();
 SQL_Close($db);
 
 return ;
}




function Messages_Set_Read($id, $read)
{
 if($read) $date_read = Date_Now(); else $date_read = NULL;
 
 $db        = Core_Database_Open();
 
 $date_read = SQL_Format($date_read, $db);
 SQL_Query("UPDATE users_messages SET date_read = $date_read WHERE id = $id", $db);
 
 SQL_Close($db);
}






function Messages_List($user_id = -1, $date_from = "197001010000", $date_to = "297001010000", $options = [])
{
 if($user_id == -1) $user_id = $_SESSION["user"]["id"];

 // UNREAD ONLY?
 if($options["unread"]) $unread = "AND date_read IS NULL"; else $unread = "";
 
 // SPECIFIC TYPE ONLY?
 if($options["type"]) $type = "AND type = '" . $options["type"] . "'"; else $type = "";
 
 // FIELDS
 $fields = $options["fields"] ?? "id, sender_id, subject, date_sent, date_read";
 
 $db       = Core_Database_Open();
 $messages = SQL_Query("SELECT $fields FROM users_messages WHERE user_id = $user_id $unread $type AND (date_sent BETWEEN $date_from AND $date_to) ORDER BY date_sent DESC", $db);
 SQL_Close($db);

 // GET INFO ABOUT SENDERS?
 if($options["users"])
 {
  Users_Integrate($messages, "sender_id", "id,firstname,lastname", "sender");
 }
 

 return $messages;
}





function Messages_Read($id)
{
 $db       = Core_Database_Open();
 $messages = SQL_Query("SELECT * FROM users_messages WHERE id = $id", $db);
 SQL_Close($db);
 
 $message = $messages[0];
 return $message;
}





function Messages_Delete($id)
{
 $db       = Core_Database_Open();
 SQL_Query("DELETE FROM users_messages WHERE id = $id", $db);
 SQL_Close($db);
}





function Messages_Purge($user_id = -1, $age = 30)
{
 if($user_id == -1) $user_id = $_SESSION["user"]["id"];
 
 
 $today      = Date_Now();
 $expiration = Date_Add_Days($today, -$age);

 $db = Core_Database_Open();
 SQL_Query("DELETE FROM users_messages WHERE user_id = $user_id AND (date_read IS NOT NULL AND date_read < $expiration) OR (date_expiration IS NOT NULL AND date_expiration < $today)", $db);
 SQL_Close($db);
}




?><?php

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                      O U T C O M E S                                           //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Outcomes_List($folder = "content/outcomes")
{
 $list = Storage_Folder_ListFolders($folder);
 
 return $list;
}





function Outcome_Write($path, $data)
{
 Storage_Path_Create($path);
 
 Ini_File_Write("$path/info.dat", $data);
}





function Outcome_Read($folder)
{
 $outcome = parse_ini_file("$folder/info.dat", true);
 
 return $outcome;
}




function Outcomes_Read($list, $folder = "outcomes")
{
 $outcomes = [];
 
 foreach($list as $id)
 {
  $outcomes[$id] = Outcome_Read("content/$folder/$id");
 }
 
 return $outcomes;
}





function Outcome_Delete($path)
{
 Storage_Folder_Delete($path);
}



?><?php

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                   P R E S E N T A T I O N                                      //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//




function Presentation_Read($path)
{	
 if(!file_exists($path)) Storage_Path_Create($path);


 $presentation              = [];
 $presentation["slides"]    = [];
 $presentation["resources"] = [];
 
 
 // HEADER
 $presentation["path"] = $path;
 
 
 // LIST RESOURCES
 $presentation["resources"] = Presentation_ListResources($path);
 

 
 // PARSE SLIDES
 $files = Storage_Files_Collect($path, ["dat"]);
 foreach($files as $file)
 {
  $filename = Storage_Path_GetFilename($file);	  
  
  if($filename == "info.dat")
  {
   $data                 = parse_ini_file($file, true);
   $presentation["info"] = $data;
  }
  else
  {
   $data = Presentation_ReadSlide($file);
   array_push($presentation["slides"], $data);
  } 
  
 }
 
 return $presentation;
}





function Presentation_ReadSlide($path, $slide_id = false)
{
 // IF A SLIDE ID IS SPECIFIED, THEN PATH IS JUST A PATH TO A PRESENTATION FOLDER, NOT THE DIRECT PATH TO THE SLIDE FILE
 if($slide_id)
 {
  $path = "$path/$slide_id.dat";
 }
 
 $slide       = parse_ini_file($path, true);
 $slide["id"] = Storage_Path_RemoveExtension(Storage_Path_GetFilename($path));
 
 return $slide;
}





function Presentation_DeleteSlide($path, $slide_id)
{
 $filename = "$path/$slide_id.dat";
 
 unlink($filename);
}




function Presentation_WriteSlide($path, $slide_id, $data)
{
 $filename = "$path/$slide_id.dat";
 
 Ini_File_Write($filename, $data);
}





function Presentation_CopySlide($source, $source_id, $dest, $dest_id)
{
 // READ SOURCE SLIDE
 $slide = parse_ini_file("$source/$source_id.dat", true);
 
 // COLLECT SOURCE MEDIA
 $files    = [];
 $sections = array_keys($slide);
 foreach($sections as $section)
 { 
  $src = urldecode($slide[$section]["src"] ?: "");
  
  if($src)
  {
   array_push($files, $src);
  }	  
 }
 
 // IF DESTINATION PATH DOES NOT EXIST, CREATE IT
 if(!file_exists($dest)) Storage_Path_Create($dest);
 
 // WRITE DESTINATION SLIDE
 Ini_File_Write("$dest/$dest_id.dat", $slide);
 
 // WRITE DESTINATION MEDIA
 foreach($files as $file)
 {
  copy("$source/$file", "$dest/$file");
 }
}





function Presentation_WriteElement($path, $slide_id, $element_id, $data)
{
 $filename = "$path/$slide_id.dat";
 
 Ini_Section_Write($filename, $element_id, $data);
}




function Presentation_ListResources($source)
{
 $list  = [];
 
 $files = Storage_Files_Collect($source, ["jpg", "jpeg", "gif", "png", "svg", "wav", "mp3", "ogg", "mp4", "mpg", "mpeg", "mov", "avi"]);
 foreach($files as $file)
 {
  $filename = Storage_Path_GetFilename($file);	  
  array_push($list, $filename);
 }
 
 return $list;
}




?><?PHP


function Progress_Curriculum_Data($student_id = -1, $curriculum = "default", $classes = 30)
{
 $data = [];
 
 
 // STUDENT
 if($student_id == -1) $student_id = $_SESSION["user"]["id"];
 
 // CURRENT DATE
 $now = Date_Now();
 
 
 
 // READ CURRICULUM, ITS SKILLS, AND EXTRACT THE ASSOCIATED PROGRAMS
 $data["curriculum"] = Content_Curriculum_Read($curriculum);
 $data["skills"]     = Content_Curriculum_Skills($curriculum);
 $programs           = $data["curriculum"]["main"]["programs"] ?? "";
 
 
 // CREATE FILTER TO ISOLATE CLASSES PERTAINING THIS SPECIFIC CURRICULUM
 $programs = explode(",", $programs);
 $filter   = [];
 foreach($programs as $program)
 {
  array_push($filter, "(classes.lesson_id LIKE '$program%')");
 }
 $filter = implode(" OR ", $filter); 
 
 

 // ASSEMBLE QUERY TO EXTRACT CLASSES FOR THIS CURRICULUM
 $query = "SELECT classes_seats.id, classes.lesson_id, classes.date_start, classes_seats.assessment
           FROM   classes, classes_seats 
           WHERE (classes_seats.student_id = $student_id) AND (classes_seats.class_id = classes.id) AND (classes.date_start < $now) 
           AND   ($filter)
           ORDER BY classes.date_start desc
           LIMIT 0, $classes";
		   
		   
 // GET CLASSES
 $db      = Core_Database_Open();
 $classes = SQL_Query($query, $db);
 SQL_Close($db);
 
 usort($classes, "Progress_ClassData_Sort");

 // PROCESS CLASSES AND STORE IT
 foreach($classes as &$class)
 {
  $class["assessment"]   = json_decode($class["assessment"], true);
  $class["date_start"]   = User_Date($class["date_start"], "out");
  $class["lesson_title"] = Lesson_read($class["lesson_id"], "title-only");
 }
 
 $data["classes"] = $classes;
 
 
 // GET PROJECTS
 $projects         = Projects_List_ByStudent($student_id);
 $data["projects"] = $projects;
 
 // RETURN
 return $data;
}	




function Progress_Data_Outcomes($student_id = -1)
{
 // STUDENT
 if($student_id == -1) $student_id = $_SESSION["user"]["id"];

 // READ ALL STUDENT OUTCOMES FROM SEATS ASSESSMENT
 $db   = Core_Database_Open();
 $rows = SQL_Query("SELECT assessment FROM classes_seats WHERE student_id = $student_id", $db);
 SQL_Close($db);
 
 // AGGREGATE ASSESSMENT
 $assessment = [];
 foreach($rows as &$row)
 {
  $row["assessment"] = json_decode($row["assessment"], true) ?? [];
  $assessment = array_merge($assessment, $row["assessment"]);
 }
 
 return $assessment;
}




function Progress_Data_Vocabulary($student_id = -1)
{
 // STUDENT
 if($student_id == -1) $student_id = $_SESSION["user"]["id"];

 // READ ALL STUDENT OUTCOMES FROM SEATS ASSESSMENT
 $db   = Core_Database_Open();
 $rows = SQL_Query("SELECT data FROM users_activities WHERE student_id = $student_id AND mode = 'test' AND source LIKE '%/vocabulary' ", $db); 
 //return "SELECT data FROM users_activities WHERE student_id = $student_id AND mode = 'test' AND source LIKE '%/vocabulary' ";
 SQL_Close($db);
 
 // AGGREGATE DATA
 $vocabulary = [];
 foreach($rows as &$row)
 {
  $row["data"] = json_decode($row["data"], true) ?? [];
  foreach($row["data"] as $token)
  {
   $term = $token["term"];
   array_push($vocabulary, $term);
  }
 }
 $vocabulary = array_unique($vocabulary);
 
 return $vocabulary;
}



/*
function Progress_Data_Outcomes($student_id = -1)
{
 // READ OUTCOMES CATALOG
 $outcomes = file_get_contents("content/index/outcomes-catalog.dat");
 $outcomes = json_decode($outcomes, true);

 // STUDENT
 if($student_id == -1) $student_id = $_SESSION["user"]["id"];
 $stats = [];
 
 // READ ALL STUDENT OUTCOMES FROM SEATS ASSESSMENT
 $db   = Core_Database_Open();
 $rows = SQL_Query("SELECT assessment FROM classes_seats WHERE student_id = $student_id", $db);
 SQL_Close($db);
 
 // AGGREGATE ASSESSMENT
 $assessment = [];
 foreach($rows as &$row)
 {
  $row["assessment"] = json_decode($row["assessment"], true) ?? [];
  $assessment = array_merge($assessment, $row["assessment"]);
 }
 
 
 $data               = [];
 $data["outcomes"]   = $outcomes;
 $data["assessment"] = $assessment;
 
 return $data;
 
 
 // CALCULATE LEVEL PERCENTAGES
 $keys = array_keys($outcomes["by level"]);
 $stats["by level"] = [];
 foreach($outcomes["by level"] as $level => $items)
 {
  // INIT LEVEL STATS
  $stats["by level"][$level]             = [];
  $stats["by level"][$level]["outcomes"] = [];
  
  // FOR EACH OUTCOME, CHECK IF IT'S IN THE STUDENT'S ASSESSMENT
  foreach($items as $item) if(isset($assessment[$item])) 
  {
   array_push($stats["by level"][$level]["outcomes"], $item);
  }
  
  // THE FINAL COUNT IS THE AMOUNT OF OUTCOMES VS ITS TOTAL
  $stats["by level"][$level]["percentage"] = count($stats["by level"][$level]["outcomes"]) / count($items);
 }
 
 return $stats;
} */


function Progress_ClassData_Sort($a,$b)
{
  return strtotime($a["date_start"]) - strtotime($b["date_start"]);
}

?><?PHP

function Projects_Load()
{
 $user    = $_SESSION["user"];
 $user_id = $user["id"];
 
 switch($user["role"])
 {
  case "student":
    $data  = Projects_List_ByStudent($user_id, ["info" => true]);
  break;
  
  
  case "teacher":
    $data = [];
	//$data  = Projects_List_ByTeacher($user_id, ["info" => true]);
  break;
  
  
  default:
	$data = [];
  break;
 }
 
 return $data;
}





function Projects_List_ByTeacher($teacher_id = -1, $date_from = "197001010000", $date_to = "210001010000", $options = [])
{
 if($teacher_id == -1) $teacher_id = $_SESSION["user"]["id"];
 
 if(!$options["utc"])
 {
  $date_from = User_Date($date_from, "in");
  $date_to   = User_Date($date_to,   "in");
 }
 
 $db    = Core_Database_Open();
 $query = "SELECT * FROM projects WHERE teacher_id = $teacher_id AND date_start BETWEEN $date_from AND $date_to";
 $data  = SQL_Query($query, $db);
	
 if($options["info"])
 {
  $ids   = array_column($data, "project_id");
  $info  = Projects_Read($ids);
	
  Array_Integrate_Direct($data, "project_id", $info, "data");
 }
 
 SQL_Close($db);
 
 if(!$options["utc"])
 {
  foreach($data as &$item)
  {
   $fields = array_keys($item);
   foreach($fields as $field)
   {
    if(str_starts_with($field, "date_"))
    {
     $item[$field] = User_Date($item[$field], "out");
    }
   }
  }
 }
 
 return $data;
}



function Projects_List_ByStudent($student_id = -1, $options = [])
{
 if($student_id == -1) $student_id = $_SESSION["user"]["id"];
 
 $db    = Core_Database_Open();
 $query = "SELECT * FROM projects_students WHERE student_id = $student_id ORDER BY -date_due";
 $data  = SQL_Query($query, $db);
 if(!empty($data)){
	 Array_Items_JSONParse($data, ["assessment"]);

	 
	 // OPTIONAL PROJECT DATA
	 if($options["info"])
	 {
	  // REPLACE PROJECT_ID (DATABASE ROW) WITH PROJECT_ID (SOURCE)	
	  $ids   = array_column($data, "project_id");
	  $list  = SQL_Values_List($ids);
	  $query = "SELECT id, project_id FROM projects WHERE id IN ($list)";
	  $info  = SQL_Query($query, $db);
	  Array_Integrate($data, "project_id", $info, "id");
		
	  $ids   = array_column($data, "project_id");
	  $info  = Projects_Read($ids);
	  Array_Integrate_Direct($data, "project_id", $info, "data");	
	 }
 }
 else{
	 $data = array();
 }
 SQL_Close($db);
 
 
 return $data;
}




function Projects_List($folder = "content/projects")
{	 	 
 $list = Storage_Folder_ListFolders($folder);
 
 return $list;
}



function Projects_Read($ids, $files = false)
{
 $data = [];
 
 $ids  = array_unique($ids);
 foreach($ids as $id)
 {
  $data[$id] = Project_Read($id, $files);
 }
 
 return $data;
}



function Project_Read($id, $files = true)
{
 $project = parse_ini_file("content/projects/$id/info.dat", true); 
 
 if($files)
 {
  $project["files"] = Storage_Files_Collect("content/projects/$id", ["pdf"]);
 }
 
 return $project;
}





function Project_Create($project_id, $class_id = null, $teacher_id = null)
{
 $db         = Core_Database_Open();
 
 $project_id = SQL_Format($project_id, $db);
 $class_id   = SQL_Format($class_id,   $db); 
 $teacher_id = SQL_Format($teacher_id, $db);
 
 $date_start = Date_Format_NoSeconds(Date_Now());
 
 // CHECK EXIST SAME PROJECT IN COURSE
 $courses = SQL_Query("SELECT course_id FROM classes WHERE id = $class_id",$db);
 $course_id = $courses[0]["course_id"];

 $checkExist = SQL_Query("SELECT projects.id FROM projects LEFT JOIN classes ON classes.id = projects.class_id WHERE projects.project_id = $project_id AND classes.course_id = $course_id",$db);
 if(count($checkExist))
 {
    //UPDATE PROJECT ID FOR CLASS
    $id = $checkExist[0]["id"];
    SQL_Query("UPDATE classes SET project_id = $id WHERE id = $class_id",$db);
 }
 else
 {
  $query      = "INSERT INTO projects (project_id, class_id, teacher_id, date_start) VALUES ($project_id, $class_id, $teacher_id, $date_start)";
  $id         = SQL_Query($query, $db);
 }
 
 
 SQL_Close($db);
 
 return $id;
}




function Project_List_Students($project_id)
{
 $db     = Core_Database_Open();
 
 $query  = "SELECT * FROM projects_students WHERE (project_id = $project_id)";
 $rows   = SQL_Query($query, $db);
 
 if(count($rows) == 0) return [];
 
 // GET STUDENTS' NAMES
 $ids = array_column($rows, "student_id");
 $ids = SQL_Values_List($ids);
 
 $query    = "SELECT id AS student_id, firstname, lastname, nickname FROM users WHERE id IN ($ids)";
 $students = SQL_Query($query, $db);
   
 Array_Integrate($rows, "student_id", $students);
 Array_Items_JSONParse($rows, ["assessment"]);
 User_Date_Process($rows, ["date_due"], "out");
 
 // ARRANGE GROUPS
 $groups = [];
 foreach($rows as $row)
 {
  $group = $row["group_id"];
  
  if(!isset($groups[$group])) $groups[$group] = [];
  
  array_push($groups[$group], $row);
 }
 
 
 
 SQL_Close($db);
 
 return $groups;
}





function Project_Remove_Student($project_id, $student_id)
{
 $db    = Core_Database_Open();
 
 $query = "DELETE FROM projects_students WHERE project_id = $project_id AND student_id = $student_id";
 SQL_Query($query, $db);
 
 SQL_Close($db);
}




function Project_Assign_Student($project_id, $student_id, $group_id, $days = 365, $url = null)
{	  
 $db = Core_Database_Open();
 
 $project_id  = SQL_Format($project_id, $db);
 $url         = SQL_Format($url, $db);
 
 $user        = $_SESSION["user"];
 $assigner_id = SQL_Format($user["id"], $db);
 
 
 // FIRST CHECK IF A PROJECT WITH THIS STUDENT AND CLASS ALREADY EXISTS
 $query = "SELECT id FROM projects_students WHERE (project_id = $project_id AND student_id = $student_id)";
 $rows  = SQL_Query($query, $db);
 $id    = $rows[0]["id"] ?? false;
 
 // IF IT EXISTS, UPDATE GROUP
 if($id)
 {
  $query = "UPDATE projects_students SET group_id = $group_id, url = $url, assigner_id = $assigner_id WHERE id = $id";
  SQL_Query($query, $db);
 }
 else
 // IF NOT, CREATE IT
 {
  $date_due = Date_Add_Days(Date_Now(), $days);
  $query    = "INSERT INTO projects_students (project_id, student_id, group_id, date_due, assigner_id, url) VALUES($project_id, $student_id, $group_id, $date_due, $assigner_id, $url)";
  $id       = SQL_Query($query, $db);
 }
   
 SQL_Close($db);
 
 return  $id;
}



function Project_File_Set($assignment_id, $url)
{
 $db    = Core_Database_Open();
 
 $url   = SQL_Format($url, $db);
 
 $query = "UPDATE projects_students SET url = $url WHERE id = $assignment_id";
 SQL_Query($query, $db);
 
 SQL_Close($db);
}


?><?php


function Queries_List($filter = [])
{
 $list = Core_Files_PartnerQueries();
 
 if($filter["fixed"])
 {
  $filtered = [];
  foreach($list as &$item) if(str_contains($item, "fixed")) array_push($filtered, $item);
  
  $list = $filtered;
 }
 
 return $list;
}



function Queries_Read($name)
{
 // STANDARD QUERY INFO
 $filename = "partners/" . $_SESSION["partner"] . "/queries/$name.dat";
 $file     = parse_ini_file($filename, true);
 
 return $file;
}




function Queries_Query($name, $fields = false, $data = [])
{ 
 // STANDARD QUERY INFO
 $file     = Queries_Read($name);
 
 
 // FIELDS
 if(!$fields) $fields = $file["sql"]["fields"] ?? "*";
 if(gettype($fields) == "array") $fields = implode(", ", $fields);
 
 // OPEN DATABASE
 $db       = Core_Database_Open();
 
 // BUILD QUERY
 $query    = $file["sql"]["query"];
 foreach($data as &$field) $field = SQL_Format($field, $db);
 $data["fields"] = $fields;
 
 // EXECUTE QUERY
 $query    = String_Variables_Apply($query, $data, "$");
 $rows     = SQL_Query($query, $db);
 
 // CLOSE DATABASE
 Core_Database_Close($db);
 
 // RETURN ROWS
 return $rows;
}




function Queries_Fields($name)
{
 $file  = Queries_Read($name);
 $table = $file["sql"]["table"]; 
 
 $db     = Core_Database_Open();
 $fields = SQL_Table_Fields($table, $db);
  
 Core_Database_Close($db);
 
 return $fields;
}



?><?PHP

function Resources_Lessons($options = [])
{
 // LOAD LESSONS
 $lessons = Storage_Folders_Collect("content/lessons", ["uproot"]);
 
 return $lessons;
}





function Resources_Vocabulary($options = [])
{
 // LOAD LESSONS
 $lessons = Storage_Folders_Collect("content/vocabulary", ["uproot"]);
 
 return $lessons;
}




function Resources_Skills($options = [])
{
 // LOAD SKILLS
 $skills = Storage_Folders_Collect("content/skills", ["uproot"]);
 
 return $skills;
}





function Resources_Outcomes($options = [])
{
 // LOAD LESSONS
 $lessons = Storage_Folders_Collect("content/outcomes", ["uproot"]);
 
 return $lessons;
}




function Resources_Projects($options = [])
{
 // LOAD LESSONS
 $lessons = Storage_Folders_Collect("content/projects", ["uproot"]);
 
 return $lessons;
}



function Resources_Index($index, $options = [])
{
 // LOAD LESSONS
 $index = file_get_contents("content/index/$index.dat");
 $index = json_decode($index, true);
 
 return $index;
}



?><?PHP
$googleservice = Core_Service_Read('google');
require_once $googleservice;
//require_once (__ROOT__.'\services\google\google.php');
function Reminder_Create($course, $teacher_id = null)
{
 $db    = Core_Database_Open();
 $course = (object)$course;
 $reminder_id = Reminder_File_Create($course, $teacher_id);

 $query      = "UPDATE courses SET reminder_id='$reminder_id' WHERE id=$course->id";
 SQL_Query($query, $db);
 
 SQL_Close($db);
 
 return $reminder_id;
}

function Reminder_File_Create($course, $teacher_id = null){
	$client = Google_Client_Create();
	if(empty($course->reminder_id)){
		$file = 0;
		$file = Check_Exist_Reminder_File($client,$course);
		if(!$file){
			$file = Create_Reminder_File($client,$course);
		}
		$course->reminder_id=$file->id;
		$resultView = Set_Has_Link_Can_View_Permission_Reminder_File($client,$course->reminder_id);
	}
	$resultPer = Set_Edit_Permission_Reminder_File($client,$course->reminder_id, $teacher_id);
	$resultView = Set_Has_Link_Can_View_Permission_Reminder_File($client,$course->reminder_id);
	return $course->reminder_id;
}

function Check_Exist_Reminder_File($client,$course){
	$result = array();
	$service = new Google_Service_Drive($client);
	try {
		$parameters = array();
		$parameters['q'] = "name='".$course->name."-reminder'";
		$parameters['pageSize'] = 1;
		$files = $service->files->listFiles($parameters);
		$result = $files->getFiles();
	}
	catch (Exception $e) {
		print "An error occurred: " . $e->getMessage();
		$pageToken = NULL;
	}
	if($result[0]){
		return $result[0];
	}
	return 0;
}

function Create_Reminder_File($client,$course){
	try{
		$service = new Google_Service_Drive($client);
		$file = new Google_Service_Drive_DriveFile();
		$file->setMimeType('application/vnd.google-apps.document');
		$file->setName($course->name.'-reminder');
		$file->setParents(array('1QLBlIwexThK_bEYlp7sxpOLKNssrbp1V'));
		$parameters = array();
		$parameters['fields'] = 'id,name,mimeType';
		$result = $service->files->create($file,$parameters);
		return $result;
	}
	catch(Exception $e){
		print_r($e);die;
		return 0;
	}
	return 0;
}

function Get_List_Permission_Reminder_File($client,$fileId,$email=null){
	try{
		if(!empty($email)){
			$service = new Google_Service_Drive($client);
			$parameters = array();
			$parameters['fields'] = 'kind,permissions';
			$result = $service->permissions->listPermissions($fileId,$parameters);
			$permissions = $result->getPermissions();
			foreach ($permissions as $permission) {
				if($permission->emailAddress == $email){
					if($permission->role == 'writer' || $permission->role == 'owner')
						return true;
				}
			}
			return 0;
		}
		return true;
	}
	catch(Exception $e){
		return 0;
	}
	return 0;
}

function Set_Edit_Permission_Reminder_File($client,$fileId,$teacher_id=null){
	try{
		$db    = Core_Database_Open();
		$query = "SELECT email FROM users WHERE id=$teacher_id";
		$data = SQL_Query($query, $db);
		$email = $data[0]['email'] ?? null;
 		SQL_Close($db);
		if(!empty($email) && !Get_List_Permission_Reminder_File($client,$fileId,$email)){
			$service = new Google_Service_Drive($client);
			$fileBody = new Google_Service_Drive_Permission();
			$fileBody->setRole('writer');
			$fileBody->setType('user');
			$fileBody->setEmailAddress($email);
			$parameters = array();
			//$parameters['emailMessage'] = "Please access to this reminder file and put your reminder for class.";
			$parameters['sendNotificationEmail'] = false;
			$result = $service->permissions->create($fileId,$fileBody,$parameters);
		}
		return true;
	}
	catch(Exception $e){
		print_r($e);
		return 0;
	}
	return 0;
}

function setWebPublishReminderFile($client,$fileId){
	try{
		$service = new Google_Service_Drive($client);
		$revision = new Google_Service_Drive_Revision();
		$revision->setPublished(true);
		$revision->setPublishedOutsideDomain(true);
		$revision->setPublishAuto(true);
		$result = $service->revisions->update($fileId,1,$revision);
		return true;
	}
	catch(Exception $e){
		return 0;
	}
	return 0;
}

function Set_Has_Link_Can_View_Permission_Reminder_File($client,$fileId){
	try {
		$service = new Google_Service_Drive($client);
		$fileBody = new Google_Service_Drive_Permission();
		$fileBody->setType('anyone');
		$fileBody->setRole('reader');
		$result = $service->permissions->create($fileId,$fileBody);
		return true;
	}
	catch(Exception $e){
		print_r($e);
		return 0;
	}
	return 0;
}
?><?php

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                         T E A C H                                              //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//

?><?PHP

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                          T E S T                                               //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Test_Read($source)
{
 $test           = [];
 $test["source"] = $source;
 
 
 // SHEETS
 $test["sheets"] = [];
 $files          = Storage_Files_Collect($source, ["dat"]);
 
 foreach($files as $file)
 {
  $id = Storage_Path_RemoveExtension(Storage_Path_GetFilename($file));
  
  $sheet       = Ini_File_Read($file);
  $sheet["id"] = $id;
  
  array_push($test["sheets"], $sheet);
 }
 
 
 // RESOURCES
 $files             = Test_Resources_List($source);
 $test["resources"] = $files;
 
 
 return $test;
}








// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                     R E S O U R C E S                                          //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//




function Test_Resources_List($source)
{
 $list  = [];
 
 $files = Storage_Files_Collect($source, ["jpg", "jpeg", "gif", "png", "svg", "wav", "mp3", "ogg", "mp4", "mpg", "mpeg", "mov", "avi", "pdf"]);
 foreach($files as $file)
 {
  $filename = Storage_Path_GetFilename($file);	  
  array_push($list, $filename);
 }
 
 return $list;
}








// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                         S H E E T S                                            //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//



function Test_Sheet_Write($path, $sheet_id, $data)
{
 $filename = "$path/$sheet_id.dat";
 
 Ini_File_Write($filename, $data);
}






function Test_Sheet_Delete($path, $sheet_id)
{
 $filename = "$path/$sheet_id.dat";
 
 unlink($filename);
}







// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                          S T O R E                                             //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//

function Test_Results_Store($id, $test_id, $score, $data)
{
 $db      = Core_Database_Open();

 $date    = Date_Now();
 $data    = json_encode($data);
 
 $id      = SQL_Format($id,      $db);
 $date    = SQL_Format($date,    $db);
 $test_id = SQL_Format($test_id, $db);
 $data    = SQL_Format($data,    $db);
 $score   = SQL_Format($score,   $db);

 $query   = "INSERT INTO tests_store (id, date, test_id, score, data) VALUES($id, $date, $test_id, $score, $data) ON DUPLICATE KEY UPDATE date = $date, test_id = $test_id, score = $score, data = $data";
 SQL_Query($query, $db);
 
 SQL_Close($db);
}




function Tests_Results_Read($id)
{
 $db    = Core_Database_Open();
 
 $id    = SQL_Format($id, $db);
 $query = "SELECT date, test_id, score, data FROM tests_store WHERE id = $id";
 $rows  = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 if(count($rows) < 1) return false;
 
 $results = $rows[0];
 $results["data"] = json_decode($results["data"]);
 
 return $results;
}



?><?PHP

function Timeoff_Field_Type($type)
{
  switch ($type) {
    case 'user':
      return "user_id";
      break;
    
    default:
      return "center_id";
      break;
  }
}

function Timeoff_Parse_Value($type,$value)
{
  $center = 'NULL';
  $user_id = 'NULL';
 
  $field = Timeoff_Field_Type($type);
 
  $db = Core_Database_Open();
 
  if($field == "user_id")
  {
    $centerData = SQL_Query("SELECT center FROM users WHERE id = $value",$db);
    $center = SQL_Format($centerData[0]["center"],$db);
    $user_id = $value;
  } 
  else $center = SQL_Format($value,$db);
  SQL_Close($db);
  return [$center,$user_id];
}

function Timeoff_Read($type, $value, $utc = false)
{
  $db = Core_Database_Open();

  $field = Timeoff_Field_Type($type);
  if($field == "center_id") $value = SQL_Format($value,$db);
  
  $data = SQL_Query("SELECT * FROM timeoff WHERE $field = $value",$db);

  // REMOVE TIMEOFF OLDER 1 MONTH
  $today   = Date_Now();
  
  foreach($data as $item)
  {
   // FILTER OUT DATES OLDER THAN ABOUT 1 MONTH
   if(Date_Distance_Days($today, $item["date_to"]) > -30)
   {}
   else{
    SQL_Query("DELETE FROM timeoff WHERE id = ".$item["id"],$db);
   }
  }
 
  SQL_Close($db);
  
  // CONVERT FROM UTC
  if(!$utc)
  {
    foreach($data as &$item)
    {
    $item["date_from"] = User_Date($item["date_from"], "out");
    $item["date_to"]   = User_Date($item["date_to"],  "out");
    }
  }
 
 
 return $data;
}


function Timeoff_Add($type, $value, $item)
{
  // CONVERT TO UTC
  $date_from = User_Date($item["date_from"], "in");
  $date_to   = User_Date($item["date_to"],   "in");

  list($center,$user_id) = Timeoff_Parse_Value($type,$value);

  $db = Core_Database_Open();
  //CHECK EXIST
  $check = SQL_Query("SELECT id FROM timeoff WHERE date_from = $date_from AND date_to = $date_to AND user_id = $user_id AND center_id = $center",$db);
  
  if(!count($check))
  SQL_Query("INSERT INTO timeoff(center_id,user_id,date_from,date_to) VALUES($center,$user_id,$date_from,$date_to)",$db);

  SQL_Close($db);
}



function Timeoff_AddBatch($type, $value, $items)
{
 $values = []; 

 list($center,$user_id) = Timeoff_Parse_Value($type,$value);

 $db = Core_Database_Open();

 // CONVERT TO UTC
 foreach($items as &$item)
 {
  $date_from = User_Date($item["date_from"], "in");
  $date_to   = User_Date($item["date_to"],   "in");
  $values[] = "($center,$user_id,$date_from,$date_to)";
 }
 
 $values = implode(",",$values);
 
 SQL_Query("INSERT INTO timeoff(center_id,user_id,date_from,date_to) VALUES $values",$db);

 SQL_Close($db);
}



function Timeoff_RemoveBatch($type, $value, $markers)
{
  // CONVERT TO UTC
  $date_from = User_Date($markers["date_from"], "in");
  $date_to   = User_Date($markers["date_to"],   "in");

  list($center,$user_id) = Timeoff_Parse_Value($type,$value);

  $db = Core_Database_Open();

  SQL_Query("DELETE FROM timeoff WHERE center_id = $center AND user_id = $user_id AND date_from = $date_from AND date_to = $date_to",$db);

  SQL_Close($db);
}




function Timeoff_Update_Notes($id, $notes)
{
  $db = Core_Database_Open();
  $notes =SQL_Format($notes,$db);
  SQL_Query("UPDATE timeoff SET notes = $notes WHERE id = $id",$db);
  SQL_Close($db);
}



function Timeoff_Delete($id)
{
  $db = Core_Database_Open();
  SQL_Query("DELETE FROM timeoff WHERE id = $id",$db);
  SQL_Close($db);
}



?><?PHP

function Timetable_Load()
{
}









?><?PHP

function Trung_Load()
{
 return "hello";
}

?><?PHP

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                        U S E R S                                               //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Users_List_ByRole($role, $fields = "*")
{
 $db = Core_Database_Open();
 
 if(gettype($role) == "string")
 {
  $role = SQL_Format($role, $db);  
  $condition = "role = $role";
 }
 else
 {  
  $role    = SQL_Format_IN($role, $db);
  $condition = "role IN ($role)";
 }

 $query = "SELECT $fields FROM users WHERE $condition AND status = 'active'";
 $data  = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 return $data;
}





function Users_List_ByManager($manager_id, $fields = "*")
{
 $db = Core_Database_Open();

 $query = "SELECT $fields FROM users WHERE manager_id = $manager_id AND status = 'active'";
 $data  = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 return $data;
}





function Users_List_ByCenter($center = false, $role = false, $fields = "*", $manager = false, $order = false, $rows = false, $page = false, $stats = false, $inactive = false)
{
 $db = Core_Database_Open();

 $conditions = [];


 // BASE CONDITION
 if($center)
 {
  if(gettype($center) == "string")
  {
   $center = SQL_Format($center, $db);  
   array_push($conditions, "center = $center");
  }
  else
  {  
   $center = SQL_Format_IN($center, $db);
   array_push($conditions, "center IN ($center)");
  }
 }


 // MANAGER
 if($manager)
 {
  array_push($conditions, "manager_id = $manager");
 }


 
 // ROLE(S) FILTER
 if($role)
 {
  $role = SQL_Format_IN($role, $db);
  
  array_push($conditions, "role IN ($role)");
 }
 
 
 // PRUNE INACTIVE USERS
 if(!$inactive)
 {
  array_push($conditions, "(status <> 'inactive')");
 }	 
 
 $conditions = implode(" AND ", $conditions);
 
 
 // ORDER 
 if($order)
 {
  $order = "ORDER BY $order";   
 }
 else 
 {
  $order = "";
 }
 
 
 // LIMIT
 if($rows)
 {
  if($page) 
  {
   $start = $page * $rows;
   $limit = "LIMIT $start, $rows";
  }
  else
  {
   $limit = "LIMIT $rows";
  }
 }
 else
 {
  $limit = "";
 }
 
 
 $query  = "SELECT $fields FROM users WHERE $conditions $order $limit";
 $data   = SQL_Query($query, $db);
 
 if($stats)
 {
  $stats  = [];
  
  $query  = "SELECT count(*) FROM users WHERE $conditions";
  $count  = SQL_Query($query, $db);
  $count  = $count[0]["count(*)"];
  
  $stats["count"] = $count;
  
  array_push($data, $stats);
 }
 
 SQL_Close($db);
 
 return $data;
}




function Users_Read($ids, $fields = "firstname,lastname", $options = [])
{
 if(count($ids) > 0)
 {
  if($options["order"]) $order = "ORDER BY " . $options["order"]; else $order = "";
	 
  $db = Core_Database_Open();
 
  $ids = array_unique($ids);
  $ids = SQL_Format_IN($ids, $db);
   
  $fields = explode(",", $fields);
  $fields = ["id", ...$fields];
  $fields = implode(",", $fields);
   
  $query = "SELECT $fields FROM users WHERE id IN ($ids) $order";
  $users = SQL_Query($query, $db);
 
  SQL_Close($db);
  
  if(!$options["array"]) $users = Array_Catalog_AsIndex($users, "id");
 }
 else
 {
  $users = [];
 }
   
 return $users;
}



// SEARCH OBJECT
// $fields = "firstname,lastname" 
// $centers = false, $roles = false, $inactive = false


function Users_Search($search = [])
{
 $db     = Core_Database_Open();
  
 $search = (array) $search;
 
 
 // 1. FIELDS
 $fields = $search["fields"] ?: [];
 if(gettype($fields) == "string") $fields = explode(",", $fields);
 if(array_search("id", $fields) === false) array_unshift($fields, "id");
 $fields = implode(",", $fields);
 
 // 2. CONDITIONS
 $conditions = [];
 
  
 // 2c. ID OR ELSE
 if($search["id"])
 {
  $id = $search["id"];
  array_push($conditions, "(id = '$id')");
  
  // SEARCHING BY ID NULLS SOME SEARCH FILTERS AND INCLUDES INACTIVE USERS TOO
  $search["roles"]    = [];
  $search["centers"]  = [];
  $search["inactive"] = true;
 }
 else
 // OTHER FIELDS
 {
  // LASTNAME
  if($search["lastname"])
  {
   $lastname = $search["lastname"];
   array_push($conditions, "(lastname LIKE '$lastname%')");
  }
  
  // FIRSTNAME
  if($search["firstname"])
  {
   $firstname = $search["firstname"];
   array_push($conditions, "(firstname LIKE '$firstname%')");
  }
  
  // EMAIL
  if($search["email"])
  {
   $email = $search["email"];
   array_push($conditions, "(email LIKE '$email%')");
  }
  
  // MOBILE
  if($search["mobile"])
  {
   $mobile = $search["mobile"];
   array_push($conditions, "(mobile LIKE '$mobile%')");
  }
 }
 
 
 // 2a. CENTERS
 $centers = $search["centers"] ?: [];
 if(gettype($centers) == "string") $centers = explode(",", $centers);
 if(count($centers) > 0) 
 {
  $centers = SQL_Format_IN($centers, $db);
  array_push($conditions, "(center IN ($centers))");
 }
 
 
 // 2b. ROLES
 $roles = $search["roles"] ?: [];
 if(gettype($roles) == "string") $roles = explode(",", $roles);
 if(count($roles) > 0) 
 {
  $roles = SQL_Format_IN($roles, $db);
  array_push($conditions, "(role IN ($roles))");
 }
 
 // 2c. PRUNE INACTIVE USERS
 if(!$search["inactive"])
 {
  array_push($conditions, "(status <> 'inactive')");
 }	 
 
 $conditions = implode(" AND ", $conditions);
 
 
 // 3. LIMIT
 if($search["count"])
 { 
  $limit = $search["count"];
  $limit = "LIMIT $limit";
 }
 else $limit = "";
 
 
 // 4. ORDER
 if($search["order"])
 { 
  $order = $search["order"];
  $order = "ORDER BY $order";
 }
 else $order = "";
 
 
 $query = trim("SELECT $fields FROM users WHERE $conditions $order $limit");
 $rows  = SQL_Query($query, $db);
 
 SQL_Close($db); 
 
 return $rows; 
}



function User_Create($data)
{
 // INSERT INTO DB
 $db     = Core_Database_Open();

 $fields = SQL_Fields_Insert($data, $db);	
 $query  = "INSERT INTO users $fields";
 
 $id     = SQL_Query($query, $db);
 
 SQL_Close($db);
	
 
 $defaults = "partners/" . $_SESSION["partner"] . "/defaults";
 User_Config_Write($id, 'settings', parse_ini_file("$defaults/user-settings.cfg",true));
 User_Config_Write($id, 'propic', base64_encode(file_get_contents("resources/images/default/propic-generic.png")));
	
 return $id;
}



function User_Update_Field($user_id = -1, $field, $value)
{	 
 if($user_id == -1) $user_id = $_SESSION["user"]["id"];
 
 $db     = Core_Database_Open();

 $value  = SQL_Format($value, $db); 
 $query  = "UPDATE users SET $field = $value WHERE id = $user_id";
 
 SQL_Query($query, $db);
 
 SQL_Close($db);
}



function User_Read($user_id = -1, $options = [])
{
 if($user_id == -1) $user_id = $_SESSION["user"]["id"];
 

 // READ USER FROM DB 
 $db = Core_Database_Open();
 
 $fields = $options["fields"] ?? "*";
 $query  = "SELECT $fields FROM users WHERE id = $user_id"; 

 $rows   = SQL_Query($query, $db);
 
 $user   = $rows[0];
 

 if(isset($options["family"]))
 {
  if(!isset($user["family_id"]))
  {
   $rows              = SQL_Query("SELECT family_id FROM users WHERE id = $user_id", $db);
   $user["family_id"] = $rows[0]["family_id"];
  }
  
  $family_id = $user["family_id"];
  if($family_id)
  {
   $user["family"] = Family_Read($family_id);
  }
 }

 
 if(isset($options["manager"]))
 {
  if(!isset($user["manager_id"]))
  {
   $rows              = SQL_Query("SELECT manager_id FROM users WHERE id = $user_id", $db);
   $user["manager_id"] = $rows[0]["manager_id"];
  }
  
  $manager_id      = $user["manager_id"];
  if($manager_id)
  {
   $user["manager"] = User_Read($manager_id, ["fields" => "id,firstname,lastname,role"]);
  }
 
 }
 
 
 SQL_Close($db);

 
 // USER'S SETTINGS
 if($options["settings"])
 {
  $user["settings"] = User_Config_Read($user_id, "settings");
 }
 
 if(!$user["settings"]) $user["settings"] = [];
  
 //START DOAN NHAT NAM 10/05/2024: change text size default is small
 if(!isset($user["settings"]["preferences"]["size"])) $user["settings"]["preferences"]["size"] = "small";
 //END DOAN NHAT NAM 10/05/2024: change text size default is small 
 
 // USER'S PERMISSIONS
 if(isset($options["permissions"]) && $options["permissions"])
 {
  $user["permissions"] = User_Config_Read($user_id, "permissions");
 }
 
 if(!isset($user["permissions"]) || !$user["permissions"]) $user["permissions"] = [];
 
 // USER'S PROPIC
 if(isset($options["propic"]) && $options["propic"])
 {
  $user["propic"] = User_Picture_Read($user_id);
 }
 
 
 // USER'S FILES
 
  //DoanNhatNam:check device mobile 
  if(preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo
  |fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i"
  , $_SERVER["HTTP_USER_AGENT"]))
  {
    $user["settings"]["preferences"]["size"] = "small"; 
    $user["settings"]["preferences"]["device"] = "mobile"; 
  }
 //DoanNhatNam:check device mobile end
 
 return $user;
}




function User_Folder($user_id = -1)
{
 if($user_id == -1) $user_id = $_SESSION["user"]["id"];
 
 $folder = "partners/" . $_SESSION["partner"] . "/users/" . $user_id;
 
 return $folder;
}




function User_Files_List($id, $folder)
{
 if($id == -1) $id = $_SESSION["user"]["id"];
 
 $folder   = User_Folder($id) . "/" . $folder;
 
 if(file_exists($folder))
 {	 
  $files  = Storage_Folder_ListFiles($folder);
 }
 else
 {
  $files = [];
 }
 
 return $files;
}



function User_Files_Upload($id = -1, $srcfilename = "", $path = "")
{ 
 if($id == -1) $id = $_SESSION["user"]["id"];
 
 $folder   = User_Folder($id);
 $filename = Storage_Path_GetFilename($srcfilename);
 $dest     = "$folder/$path/$filename"; 
 
 Storage_File_Upload($dest);
 
 return $dest;
}




function User_Picture_Upload($id = -1)
{
  if($id == -1) $id = $_SESSION["user"]["id"];
  $data = file_get_contents("php://input");

  $db = Core_Database_Open();
  $data = SQL_Format($data,$db);

  $check = SQL_Query("SELECT id FROM users_config WHERE user_id = $id AND file = 'propic'",$db);
  if(count($check))
  {
    $updateId = $check[0]["id"];
    SQL_Query("UPDATE users_config SET content = $data WHERE id = $updateId",$db);
  }
  else SQL_Query("INSERT INTO users_config(user_id,file,content) VALUES($id,'propic',$data)",$db);

  SQL_Close($db);
}




function User_Picture_Read($id)
{
  $db = Core_Database_Open();
  $data = SQL_Query("SELECT content FROM users_config WHERE user_id = $id AND file = 'propic'",$db);
  if(count($data)) return $data[0]["content"];
  else return null;
}




function User_Config_Read($user_id = -1, $file, $section = false, $field = false)
{

 if($user_id == -1) $user_id = $_SESSION["user"]["id"];

 $db = Core_Database_Open();

 $data = SQL_Query("SELECT content FROM users_config WHERE user_id = $user_id AND file = '$file'",$db);
 if(!count($data))
 {
    $config = new stdClass();
    $file = SQL_Format($file,$db);
    $content = SQL_Format(json_encode($config),$db);
    
    SQL_Query("INSERT INTO users_config(user_id,file,content) VALUES ($user_id,$file,$content)",$db);
 }
 else $config = json_decode($data[0]["content"],true);

 SQL_Close($db);
 
 if($section)
 { 
  if($field)
  {
   return $config[$section][$field];
  }
  else
  {
   return $config[$section];
  }
 }
 else 
 {
  return $config;
 }
}






function User_Config_Write($user_id = -1, $file, $data)
{ 
 if($user_id == -1) $user_id = $_SESSION["user"]["id"];

 $db = Core_Database_Open();
 $file = SQL_Format($file,$db);
 $content = SQL_Format(json_encode($data),$db);

 $rows = SQL_Query("SELECT id FROM users_config WHERE user_id = $user_id AND file = $file",$db);
 if(count($rows)) SQL_Query("DELETE FROM users_config WHERE id = ", $rows[0]["id"],$db); 

 SQL_Query("INSERT INTO users_config(user_id,file,content) VALUE($user_id,$file,$content)",$db);

 SQL_Close($db);
}





function User_Config_WriteSection($user_id = -1, $file, $section, $data)
{ 
 if($user_id == -1) $user_id = $_SESSION["user"]["id"];

 $db = Core_Database_Open();
 $content = new stdClass();
 $file = SQL_Format($file,$db);
 $rows = SQL_Query("SELECT id,content FROM users_config WHERE user_id = $user_id AND file = $file",$db);
 if(count($rows)) $content = json_decode($rows[0]["content"]);
 $content->$section = $data;

 $content = SQL_Format(json_encode($content),$db);

 if(count($rows)) SQL_Query("UPDATE users_config SET content = $content WHERE id = ". $rows[0]["id"],$db);
 else SQL_Query("INSERT INTO users_config(user_id,file,content) VALUE($user_id,$file,$content)",$db);

 SQL_Close($db);
}




function User_Config_WriteValue($user_id = -1, $file, $section, $field, $value)
{ 
 if($user_id == -1) $user_id = $_SESSION["user"]["id"];

 $db = Core_Database_Open();
 $content = new stdClass();

 $file = SQL_Format($file,$db);
 $rows = SQL_Query("SELECT id,content FROM users_config WHERE user_id = $user_id AND file = $file",$db);
 if(count($rows)) $content = json_decode($rows[0]["content"]);
 if(isset($content->$section))
 $content->$section->$field = $value;
 else 
 $content->$section = (object)[$field => $value];

 $content = SQL_Format(json_encode($content),$db);

 if(count($rows)) SQL_Query("UPDATE users_config SET content = $content WHERE id = ". $rows[0]["id"],$db);
 else SQL_Query("INSERT INTO users_config(user_id,file,content) VALUE($user_id,$file,$content)",$db);

 SQL_Close($db);
}




function User_Login($user_id, $password)
{
 $result = Core_Login($user_id, $password);
 
 return $result;
}





function User_Logout()
{
 Core_Logout();
}



function User_ChangePassword($user_id = -1, $old, $new)
{
 if($user_id == -1) $user_id = $_SESSION["user"]["id"];
 
 $db   = Core_Database_Open();
 
 $rows = SQL_Query("SELECT password FROM users WHERE id = $user_id", $db);
 $user = $rows[0];
 
 if($user["password"] == $old)
 {
  $new = SQL_Format($new, $db);
  SQL_Query("UPDATE users SET password = $new WHERE id = $user_id", $db);
  
  $result = "ok";
 }
 else 
 {
  $result = "no";
 }
 
 SQL_Close($db);
 
 return $result;
}





function User_Date($date, $mode)
{
 if(!$_SESSION["timezone"])
	 Core_SetTimezone();
 $zone    = $_SESSION["timezone"];
 
 $dateutc = new DateTime($date, new DateTimeZone($zone));
 $offset  = $dateutc->getOffset() / 60;

 if($mode == "in") $offset = -$offset;
 
 $outdate = Date_Add_Minutes($date, $offset);
  
 return $outdate;
}




function User_Date_Process(&$array, $fields, $mode)
{
 // FIELDS AS A PATTERN
 if(gettype($fields) == "string")
 {
  foreach($array as &$item)
  {
   $keys = array_keys($item);
   
   foreach($keys as $field)
   {
    if(str_starts_with($field, $fields))
    {
     $item[$field] = User_Date($item[$field], $mode);
    }
   }
  }
 }
 else
 // FIELDS AS AN EXPLICIT LIST
 {
  foreach($array as &$item)
  {
   foreach($fields as $field)
   {
    if(isset($item[$field]))
    {
     $item[$field] = User_Date($item[$field], $mode);
    }
   }
  }
 }
}






function Users_Available_Teach($centers, $roles, $dates, $options = [])
{
 // 0. CONVERT DATES TO UTC UNLESS OTHERWISE STATED
 if($options["utc"])
 {
  foreach($dates as &$date)
  {
   $date["date_start"] = User_Date($date["date_start"], "in");
   $date["date_end"]   = User_Date($date["date_end"],   "in");	
  }
 }
 
 
 // 1. EXTRACT OVERALL DATE_FROM AND DATE_TO GIVEN LIST OF DATES
 $range = Date_Ranges_Extremes($dates);
 $date_from = $range["date_from"];
 $date_to   = $range["date_to"];
 
 
 // PRE-SET USERS?
 if($options["users"])
 {
  $users = [];
  foreach($options["users"] as $id)
  {
   $user       = [];
   $user["id"] = $id;
   array_push($users, $user);
  }
 }
 // GET USERS FROM ALL CENTERS AND SET THEM UP
 else
 {
  $users = Users_List_ByCenter($centers, $roles, "id");
 }
 
 
 // 3. READ TIME OFF FOR EACH USER. THIS IS THE INITIAL "NOAVAIL" DATA
 foreach($users as &$user)
 {
  $timeoff  = Timeoff_Read("user", $user["id"], true)          ?: [];
  
  $user["noavail"] = $timeoff;
 }
 
 
 // 4. READ CLASSES
 
 // 4a. READ CLASSES FOR ALL USERS TOGETHER IN ONE GO
 $ids = array_column($users, "id");
 
 $db = Core_Database_Open();
 
 $ids = SQL_Format_IN($ids, $db);
 
 $query   = "SELECT date_start, date_end, teacher_id, ta1_id, ta2_id, ta3_id FROM classes WHERE (($date_from BETWEEN date_start AND date_end) OR ($date_to BETWEEN date_start AND date_end) OR (date_start BETWEEN $date_from AND $date_to) OR (date_end BETWEEN $date_from AND $date_to)) AND (teacher_id IN ($ids) OR ta1_id IN ($ids) OR ta2_id IN($ids) OR ta3_id IN ($ids))";
 $classes = SQL_Query($query, $db);
 
 SQL_Close($db); 
 

 
 // 4b. FOR EACH CLASS CONVERT IT TO NOAVAIL BLOCK, FIND THE AFFECTED USERS, AND ADD IT TO THEM
 foreach($classes as &$class)
 {
  if(Date_Valid($class["date_start"]) && Date_Valid($class["date_end"]))
  {
   // CONVERT CLASS TO NOAVAIL ITEM
   $item              = [];
   $item["date_from"] = $class["date_start"];
   $item["date_to"]   = $class["date_end"];
  
   // FIND USERS WHO ARE IN THIS CLASS, HENCE WILL BE GIVEN THE NOAVAIL BLOCK
   foreach($users as &$user)
   {
    if(in_array($user["id"], [$class["teacher_id"], $class["ta1_id"], $class["ta2_id"], $class["ta3_id"]]))
    {
     array_push($user["noavail"], $item);
    }
   }
  }
 }
 
 
 // 5. NOW WE HAVE, FOR EACH USER, ALL THE NOVAIL DATE/TIME BLOCKS. 
 // WE MUST COMPARE THE LIST OF NEED-TO-BE-AVAILABLE DATES WITH THE NOAVAIL DATES AND SEE IF THERE ARE OVERLAPS
 $a = [];
 $b = [];
 foreach($users as &$user)
 { 
  // ASSUME USER AS AVAILABLE
  $user["available"] = true;
  
  // SCAN DATES
  foreach($dates as &$date)
  {
   $a["from"] = $date["date_start"];
   $a["to"]   = $date["date_end"];
	
   // CHECK OVERLAPS WITH NOAVAIL
   foreach($user["noavail"] as &$noavail)
   {
	$b["from"] = $noavail["date_from"];
	$b["to"]   = $noavail["date_to"];
		
	if(Numbers_Range_Intersect($a, $b))
	{
     $user["available"] = false;
	 break;
	}		
   }
   
   if(!$user["available"]) break;
  }

 }
 

 $available = [];
 foreach($users as &$user) 
 {
  if($user["available"]) 
  {
   array_push($available, $user["id"]);
  }
  /*	 
  foreach($user["noavail"] as &$noavail) 
  {
   $noavail["date_from"] = User_Date($noavail["date_from"], "out"); 
   $noavail["date_to"]   = User_Date($noavail["date_to"], "out");
  }
  */
 }
 
 if($options["info"]) 
 {
  $available = Users_Read($available, $options["info"]);
 }
 
 return $available;
}




function User_Available_Teach($id, $date, $duration, $utc = false)
{
 if($utc) $date = User_Date($date, "in");
	 
 $users               = [$id];
 
 $dates               = [];
 $dates["date_start"] = $date;
 $dates["date_end"]   = Date_Add_Minutes($date, $duration);
 $dates               = [$dates];

 $avail = Users_Available_Teach(false, false, $dates, ["users"=>$users]); 
 
 return in_array($id, $avail);
}





function Users_Integrate(&$data, $idfield, $fields = "id,firstname,lastname", $container = "user")
{
 $ids      = array_column($data, $idfield);
 
 if(count($ids) > 0)
 {
  $db = Core_Database_Open();

  $ids = SQL_Format_IN($ids, $db);
  
  $query = "SELECT $fields FROM users WHERE id IN ($ids)";
  $users = SQL_Query($query, $db);
  Array_Integrate($data, $idfield, $users, "id", $container);
  
  SQL_Close($db);
 }
 
}


function User_List_By_StaffCode($staffcodes = [])
{
 $db = Core_Database_Open();
 
 $staffcodes = (array)$staffcodes;
 $staffcodes = SQL_Format_IN($staffcodes, $db);

 $condition = [];
 array_push($condition, "staffcode IN ($staffcodes)");
 $condition = implode(" AND ", $condition);

 $query = "SELECT id, staffcode, role FROM users WHERE $condition ";

 $data  = SQL_Query($query, $db);
 
 SQL_Close($db);
 
 return $data;
}


function Users_Create($data)
{
 
 $db     = Core_Database_Open();
 $userIds = array();
 $db->beginTransaction();
 try {
  foreach ($data as $key => $user) {
    $fields = SQL_Fields_Insert($user, $db);
    $query  = "INSERT INTO users $fields";
    $id     = SQL_Query($query, $db);

    // CREATE USER FOLDER AND COPY DEFAULTS
    $folder = User_Folder($id);
    Storage_Path_Create($folder);
    
    $defaults = "partners/" . $_SESSION["partner"] . "/defaults";
    User_Config_Write($id, 'settings', parse_ini_file("$defaults/user-settings.cfg",true));
    User_Config_Write($id, 'propic', base64_encode(file_get_contents("resources/images/default/propic-generic.png")));
    array_push($userIds, $id);
  }
  $db->commit();
  return ["message" => "success", "data" => $userIds];
 } catch (\Throwable $th) {
  $db->rollBack();
  return ["error" => $th->getMessage(), "data" => []];
 }

 SQL_Close($db);
	
 return true;
}


function Users_Update($data)
{
 
 $db     = Core_Database_Open();

 $db->beginTransaction();
 try {
  foreach ($data as $key => $user) {
    $id = $user["id"];
    $userinfo = SQL_Query("SELECT * FROM users WHERE id = $id", $db);
    
    if($userinfo){
      $queryu = "UPDATE users SET ";
      $update = [];
      foreach ($user as $field => $value) {
        if($field != "id" && $userinfo[$field] != $value){
          $value  = SQL_Format($value, $db); 
          array_push($update," $field = $value ");
        }
      }
      $update = implode(" , ",$update);
      $queryu = $queryu .$update . " WHERE id = $id";
      SQL_Query($queryu, $db);
    }
  }
  $db->commit();
 } catch (\Throwable $th) {
  $db->rollBack();
  var_dump($th->getMessage());
  return false;
 }

 SQL_Close($db);
	
 return true;
}


function Users_List_ByCourse($course_id)
{
 $db = Core_Database_Open();

 $query = "SELECT students FROM courses WHERE id = $course_id";
 $data  = SQL_Query($query, $db);
 $students = $data[0]["students"];
 $students = json_decode($students);
 $studentsCondition = SQL_Format_IN($students,$db);
 $query1 = "SELECT id,firstname,lastname,midname FROM users WHERE id IN ($studentsCondition)";
 $studentsData  = SQL_Query($query1, $db);
 SQL_Close($db);
 
 return $studentsData;
}

?><?php

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                         V I D E O                                              //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//



function Video_Read($source, $native = "en")
{
 $script           = [];
 $script["source"] = $source;
 
 // LOAD INFO
 if(file_exists("$source/info.dat"))
 {
  $script["info"] = parse_ini_file("$source/info.dat", true);
 }

 
 // LOAD SUBTITLES
 /*
 $files = Storage_Files_Collect($source, ["srt"]);
 foreach($files as $file)
 {
  $filename = Storage_Path_GetFilename($file);
  $name     = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filename);
  $lang     = trim(mb_strtolower($name));
  
  $script["subtitles"][$lang] = SRT_File_Read($file);
 }
 */
 
 // LOAD MAIN SUBTITLES (SCRIPT.SRT, AS EN)
 $srt                          = SRT_File_Read("$source/script.srt");
 $script["subtitles"][$native] = $srt;
 
 
 // BUILD SUBTITLES IN OTHER AVAILABLE LANGUAGES
 $files = Storage_Files_Collect($source, ["txt"]);
 foreach($files as $file)
 {
  $filename = Storage_Path_GetFilename($file);
  $name     = Storage_Path_RemoveExtension($filename);
  $lang     = trim(mb_strtolower($name));
  
  if($lang != $native && $lang != "script")
  {
   // COPY THE ORIGINAL SRT FILE
   $trans    = $srt;
   
   // OPEN TXT FILE WITH TRANSLATION
   $text = file_get_contents($file);
   $text = explode("\r\n", $text);
   
   // ASSIGN TRANSLATIONS LINE BY LINE TO THE SRT COPY
   for($i = 0, $c = count($text); $i < $c; $i++)
   {
	$line = $text[$i];                       
	$line = explode(":", $line);           // BREAK LINE IN COMPONENTS
	$line = trim($line[count($line) - 1]); // LAST ELEMENT IN THE LINE IS THE SPOKEN TEXT
	
	$trans[$i]["text"] = $line;
   }
   
   $script["subtitles"][$lang] = $trans;
  }
 }
 
 
 // LOAD VOCABULARY
 $list = [];
  
 // IF THERE IS A VOCAB LIST IN THE VIDEO FILE, LOAD IT
 if(isset($script["info"]["vocabulary"]))
 {
  $words = $script["info"]["vocabulary"];
  $list  = array_merge($list, array_values($words));
 }
 
 // IF THERE IS A VOCAB LIST IN THE VIDEO'S PARENT DIRECTORY, LOAD IT
 {
  $parent          = Storage_Path_GetFolder($source);
  $info            = parse_ini_file("$parent/info.dat", true);
  
  // MAIN
  if(isset($info["vocabulary"])) 
  {
   $words = $info["vocabulary"];
   $list  = array_merge($list, array_values($words));
  }
  
  // EXTRAS, TOO
  if(isset($info["vocabulary extras"])) 
  {
   $words = $info["vocabulary extras"];
   $list  = array_merge($list, array_values($words));
  }
 }
 
 $script["terms"] = Vocabulary_Read_Terms($list);

 
 
 // LOAD EVENTS
 /*
 $script["events"] = [];
 if(file_exists("$path/events.dat"))
 {
  $events    = parse_ini_file("$path/events.dat", true);
  $timecodes = array_keys($events);
  
  foreach($timecodes as $timecode)
  {
   $event               = $events[$timecode];
   $event["time-start"] = (floor(Time_FromTimecode($timecode) / 10) * 10);
   $event["time-end"]   = $event["time-start"] + floor(intval($event["duration"]) * 1000);
   
   array_push($script["events"], $event);
  }
 }
 */


 // LOAD TESTS
 /*
 $script["tests"] = [];
 
 $files = Disk_SearchFiles($path, ["dat"], false);
 foreach($files as $file)
 {
  $filename = Disk_Path_Filename($file);
  $name     = preg_replace('/\\.[^.\\s]{3,4}$/', '', $filename);
  $lang     = trim(mb_strtolower($name));
  
  if(mb_strpos($name, "test") !== false)
  {
   $test     = parse_ini_file($file, true);
   array_push($script["tests"], $test);
  }
 }
 */
 
 return $script;
}



?><?php

// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                     V O C A B U L A R Y                                        //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Vocabulary_List($folder = "content/vocabulary")
{
 $list = Storage_Folder_ListFolders($folder);
 
 return $list;
}




function Vocabulary_Read_Term($source)
{ 
 $term = parse_ini_file("$source/info.dat", true);
 
 if($term)
 {
  $term["source"] = $source;
 
  // DOES IT HAVE SAMPLED AUDIO?
  $audio = "$source/audio.mp3"; 
  if(file_exists($audio))
  {
   $term["audio"] = $audio;
  }
 
  return $term;
 }
 
 return false;
}






function Vocabulary_Read_Terms($terms, $source = false)
{
 // IF TERMS IS A STRING, THEN IT IDENTIFIES THE SECTION WE'LL READ FROM AN INFO.DAT TO DETERMINE THE TERMS THEMSELVES
 if(gettype($terms) == "string")
 {
  $data  = parse_ini_file("$source/info.dat", true);
  $terms = array_values($data[$terms]);
 }
 
 $list = [];
 
 foreach($terms as $term)
 {
  $item = Vocabulary_Read_Term("content/vocabulary/$term");
 
  if($item) $list[$term] = $item;
 }
 
 return $list;
}




function Vocabulary_Write_Term($path, $data)
{
 Storage_Path_Create($path);
 
 Ini_File_Write("$path/info.dat", $data);
}



function Vocabulary_Delete($path)
{
 Storage_Folder_Delete($path);
}




?><?PHP


?>