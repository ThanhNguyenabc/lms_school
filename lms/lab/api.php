<?PHP

//error_reporting(E_ERROR | E_PARSE);
//if(!isset($_REQUEST["loginkey"]) && !isset($_REQUEST["nologin"]))
	//include_once "application/mysql-sessions.php"; 
session_start();
if(isset($_REQUEST["loginkey"])){
	$_SESSION["timezone"] = $_REQUEST["timezone"] ?? date_default_timezone_get();
	$_SESSION["partner"] = $_REQUEST["partner"] ?? "default";
	$_SESSION["mode"]    = $_REQUEST["mode"]    ?? "api";
}

if(isset($_REQUEST["nologin"])){
	$_SESSION["timezone"] = $_REQUEST["timezone"] ?? date_default_timezone_get();
	$_SESSION["partner"] = $_REQUEST["partner"] ?? "default";
	$_SESSION["mode"]    = $_REQUEST["mode"]    ?? "dev";

	ob_start();
	include_once "application/init-" . $_SESSION["mode"] . ".php";
	ob_end_clean();
}

switch($_SESSION["mode"])
{
 case "dev":
	include_once "application/lib/utils.php";
  
  Client_IncludeScripts("./application/modules", ["php"], ["recurse"]);
  
 break;
 
 default:
	include_once "application/data/backend.php";
 break;
}

Server_Api();

?>