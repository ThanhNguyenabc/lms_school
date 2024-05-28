<?php

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

?>