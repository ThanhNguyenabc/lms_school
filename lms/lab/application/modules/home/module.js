// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                           H O M E                                              //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//

async function Home_OnLoad(module, data)
{
 var user = Safe_Get(application, ["user"], {});
 
 var home = Core_Config(["roles", user["role"], "home"]) || user["role"];
 
 //LOAD COLOR 
 var colorTemplate = Safe_Get(user,["settings","preferences","color"],"default");
 if(colorTemplate != "default") document.body.className = colorTemplate;
  
 // MULTIPAGE MODULE
 Module_Page_Set(home);
}




async function Home_OnShow(module, data)
{
}




async function Home_OnUnload()
{
}