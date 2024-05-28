<?PHP 
  //include_once "application/mysql-sessions.php";
	session_start();
	global $db;
	$_SESSION["partner"] = $_REQUEST["partner"] ?? "default";
	$_SESSION["mode"]    = $_REQUEST["mode"]    ?? "live";

	include_once "application/init-" . $_SESSION["mode"] . ".php";
	
    if(isset($_REQUEST["autologin"]))
	{
		$userid = $_REQUEST["autologin"];
		if($_REQUEST["autologin"] === 'random' && isset($_REQUEST["module"]) && $_REQUEST["module"] == "perftest"){
			// Test with teacher role
			$userids = [438479995,438423870,438423886,438423934];
			// test with student role
			if($_REQUEST["script"] == 'course'){
				$userids = [438428014,438428017,438431691,438431692,438431693,438431900,438431901,438432352,438437893,438437894];
			}
			$userid = $userids[array_rand($userids)];
		}
	 //echo('<pre>');print_r($userid);die;
     include_once "api.php";
	 Core_Login($userid, "137137137");
	}
	
	if(isset($_REQUEST["forcetime"]))
	{
	 if($_REQUEST["forcetime"] == "no") 
	 {
	  unset($_SESSION["forcetime"]);
	 }
	 else
	 {
	  $_SESSION["forcetime"] = $_REQUEST["forcetime"];
	 }
	}
	
	unset($_SESSION["forcetime"]);
?>


<!DOCTYPE html>

<html>
    <head>
		<link rel  = "stylesheet" href = "partners/<?PHP echo $_SESSION["partner"]; ?>/style.css" />
		<link rel  = "manifest"   href = "app-manifest.php?version=<?php echo time(); ?>" />
	</head>
	
	<body style = "border:0px; margin:0px; padding:0px;">
	</body>
</html>


<script>   

   <?PHP 	
		// DETERMINE INITIAL PAGE
		if(isset($_SESSION["user"]))  
		{
		 $value = $_REQUEST["framework"] ?? "main";
		}
		else 
		{
		 $value = "login";
		}		   
		
		Client_PublishVar("initial_page", $value, true);
		
		
		// OPTIONAL MODULE INITIALIZATION
		$flag = isset($_REQUEST["init"]);
	    Client_PublishVar("module_init", $flag, true);
	?>
      
	  
   // REGISTER AS WEB APP
   Core_App_Register("app-worker.js");


   // DISABLE CONTEXT MENUS
   document.body.oncontextmenu = 
   function(event)
   {
	event.preventDefault(); 
	event.stopPropagation; 
	return false;
   }
   
   
   // INITIAL MODULE, INITIAL PAGE    
   var module = Client_Location_Parameter("module") || "home";	
   var page   = Client_Location_Parameter("page");	
   
   
   // INITIALIZATION
   Core_Init("<?PHP echo $_SESSION['partner']; ?>");
   Core_State_Set("core", ["initial-page"], initial_page);
 
 
   // PLATFORM
   var platform = Client_Location_Parameter("platform") || "desktop";	
   Core_State_Set("core", "platform", platform);   
 
 
   // BASE MODULE LOAD
   Module_Load(initial_page, document.body).then(
   function()
   {
	Core_Api("Core_SetTimezone", {timezone:Date_Timezone()});
	
    if(module)
    {
 	 Module_Load(module);
    }
	
	
	// HACK, REMOVE
	/*
	UI_Element_Find("menu-settings").onclick =
    function()
	{
     document.onfullscreenchange =
	 function(event)
	 {
	  //document.getElementById("viewport").setAttribute("content", "width=1920, initial-scale=0, maximum-scale=1.0, minimum-scale=1, user-scalable=yes");
	  document.body.style.zoom = 0.75;
	 } 
	 document.body.requestFullscreen();
	}
	*/
	
   });
   
</script>

