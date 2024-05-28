<?php

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

?>