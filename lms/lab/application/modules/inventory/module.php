<?php
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
?>