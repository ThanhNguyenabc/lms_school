<?PHP

include_once "application/lib/utils.php";


// 1.1 LOAD PARTNER CONFIGURATION
function Init_Partner()
{
 $path    = "./partners/" . $_SESSION["partner"];

 $files  = Storage_Files_Collect($path, ["cfg"], ["uproot"]);
 $config = Storage_Files_Read($files, "ini", $path, ["noext"]);
 unset($config['system']['database']);

 return $config;
}

// PRESENT STATIC DATA TO CLIENT
$config = Init_Partner();
//$_SESSION["config"] = $config;


// 3.1 INCLUDE BACKEND CODE
$path = "./application/lib";

Client_IncludeScripts($path, ["php"], ["recurse"], ["application/data"]);


// CHECK LOGIN KEY
$keys = $config["api-key"];

if(isset($_REQUEST["loginkey"]) && isset($keys[$_REQUEST["loginkey"]]))
{
    $modules = $keys[$_REQUEST["loginkey"]]["modules"];
    $modules =  explode(",", $modules);
    $apies = $keys[$_REQUEST["loginkey"]]["api-name"];
    $apies = explode(",", $apies);
    if(isset($_REQUEST["f"]) && in_array($_REQUEST["f"],$apies))
    {
        foreach ($modules as $key => $module) {
            Client_IncludeScripts("./application/modules/$module", ["php"], ["recurse"]);
        }
    }
}

?>