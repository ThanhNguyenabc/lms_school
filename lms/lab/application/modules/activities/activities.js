// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                     A C T I V I T I E S                                        //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Activity_Function(type, action)
{
 var f = window["Activity_" + String_Capitalize_Initial(type) + "_" + String_Capitalize_Initial(action)];
 
 return f || async function(){};	 
}




async function Activity_Run(source, config = {mode:"test", escape:true})
{
 console.log("ACTIVITY RUN");
 console.log(source);
 console.log(config);
 console.log("");

 // IF ESCAPING IS ALLOWED AND MODE = TEST, MUST CONFIRM ESCAPING
 if(config["escape"] && config["mode"] == "test")
 {
  config["escape"] =
  async function(popup)
  {
   // FASTEXIT: JUST EXIT
   if(config["fastexit"])
   {
	return true;
   }
   else
   // CONFIRM EXIT
   {
    // POPUP
    var title    = UI_Language_String("activities", "escape popup title"); 
    var content  = UI_Language_String("activities", "escape popup text save"); 
    var picture  = Resources_URL("images/cover-logout.png");

    var confirm  = await UI_Popup_Confirm(title, content, picture);
    return confirm;
   }
  }
 }
 
 
 // DETERMINE TYPE FROM SOURCE
 var type = String_Filter_AllowAlpha(Path_Filename(source.toLowerCase())); 

 Core_State_Set("activity", ["result"], false);
 Core_State_Set("activity", ["type"],   type);
 Core_State_Set("activity", ["source"], source);
 Core_State_Set("activity", ["config"], config);
 
 // DETERMINE NAMESPACE
 var namespace = "Activity_" + String_Capitalize_Initial(type);
 
 
 // SET UP CONTAINER
 var container = UI_Element_Create("activities/" + type + "-container");
 Core_State_Set("activity", ["container"], container);
 
 for(var control of ["prev", "feedback", "next", "finish"])
 {
  var element = UI_Element_Find(container,  "activity-" + control);
  var func    = window[namespace + "_Control" + String_Capitalize_Initial(control)];
  if(element && func) element.onclick = func;
 }
 


 // CREATE RUN PROMISE
 var promise = new Promise(async(resolve, reject) =>
 {  
  // DISPLAY
  var template = config["popup"] || "standard";
  var popup    = await UI_Popup_Create({content:container}, undefined, "activities/display-popup-" + template, 
  {
   open:   true, 
   escape: config["escape"], 
  
   onescape:
   function()
   {
    // ESCAPING THE ACTIVITY NULLS THE RESULT
    Core_State_Set("activity", ["result"], false);	   
   },
  
   onclose:
   async function()
   {
    var result = Core_State_Get("activity", ["result"]);
   
    // REFRESH CLASS DATA
    await Course_Class_RefreshData();

    resolve(result);
   }		  
  });
	
  Core_State_Set("activity", ["popup"], popup);
  var display = UI_Element_Find(popup, "popup-window");
   
 
   
  // RUN
  var run = Safe_Function(namespace + "_Run"); console.log(namespace + "_Run");
  await run(source, display, config);
 
  
 
  // TERMINATE
 });
 
 Core_State_Set("activity", ["result"], false);
 
 return promise;
}




async function Activity_Result_Store(result)
{
 var config   = Core_State_Get("activity", ["config"]);
 var source   = Core_State_Get("activity", ["source"]);
 var checkResult = Core_State_Get("activity","result",false);
 var mode = config["mode"];
 if(checkResult == false) mode = config["mode"] + "ing";
 var duration = 0;
 
 // STORE ACTIVITY RESULT
 var id = await Core_Api("Activity_Result_Store", 
 {
   student_id: User_Id(), 
   source:     source.replace("content/lessons/", ""), 
   mode:       mode,
   score:      result["score"], 
   data:       result["data"], 
   duration:   duration
 });
 return id;
}


async function Activity_Result_Update()
{
 var id      = Core_State_Get("activity",["existed-id"],false);
 var type   = Core_State_Get("activity", ["type"]);
 if(type == "test")  id = Core_State_Get("activity",["data","id"],false);
 var config   = Core_State_Get("activity", ["config"]);
 var result = Core_State_Get("activity","result",false);
 var mode = config["mode"];
 if(result == false)
 {
   mode = config["mode"] + "ing";
   result  = Activity_Get_Current_Result()
 } 
 if(id){
   // UPDATE ACTIVITY RESULT
   await Core_Api("Activity_Result_Update", 
   {
      id : id,
      mode:       mode,
      score:      result["score"],
      data:  result["data"] 
   });
 }
 else{
   // STORE ACTIVITY RESULT
   let returnId = await Activity_Result_Store(result);
   if(type == "test")
   Core_State_Set("activity",["data","id"],returnId);
   else Core_State_Set("activity",["existed-id"],returnId);
 }
}


async function Activity_Result_Popup(result)
{
 if(!result) return;
 console.log(result);
 
 var config   = Core_State_Get("activity", ["config"]);
 var type     = Core_State_Get("activity", ["type"]);
 var source   = Core_State_Get("activity", ["source"]);
 
 var duration = 0;
 
 // STORE ACTIVITY RESULT
 if(config.mode != "test")
 await Activity_Result_Store(result);
 else await Activity_Result_Update();
 
 // DISPLAY END OF ACTIVITY POPUP BASED ON RESULT 
 var score           = result["score"];
 if(score < 0) score = 0;
 
 for(var level of ["good", "soso", "bad"])
 {
  if(score >= Module_Config("test", "score-" + level))
  {  
   var subtitle = UI_Language_String("activities/results", "popup result title");
   var title    = UI_Language_String("activities/results", level + " title");
   var content  = UI_Language_String("activities/results", level + " text");
   
   var animation = Core_Data_Value("activities/results", "popup result animation", level);
   var picture   = Resources_URL("images/activity-" + level + ".png");
	   
   await UI_Popup_Create({title, subtitle, content, picture}, undefined, undefined, {escape:true, open:true, animation:animation});
   
   break;
  }
 }
 
}

function Activity_Get_Current_Result(){
   var mode   = Core_State_Get("activity", ["config","mode"],"practice");
   var type   = Core_State_Get("activity", ["type"]);
   if(mode == "test"){
      var test   = Core_State_Get("activity", ["data"]);
      var result = {score:0,data:[]};
      var index  = 0;
      var scoreTotal = 0;
      if(type == "test")
      {
         index = parseInt(test["index"]) + 1;
         for (let i = 0; i < index; i++) {
            let score = test["sheets"][i]["result"];
            result.data.push({question: test["sheets"][i]["id"],score: score});
            scoreTotal += score;
         }
      }else if(type == "vocabulary"){
         index = Core_State_Get("activity", ["index"]);
         for (let i = 0; i < index; i++) {
            let score = test[i]["score"];
            result.data.push({term: test[i]["id"],score: score});
            scoreTotal += score;
         }
      }
      result.score = scoreTotal / index;
      return result;
   }
   else return {score:0,data:[]};
}


function Activity_Get_Testing(){
   var source = Core_State_Get("activity", ["source"]);
   source = source.replace("content/lessons/", "");
   var activities   = Core_State_Get("course", ["class-data","activities",source],[]);
   var result = null;
   activities.forEach(element => {
      if(element["mode"] == "testing"){
         result = element;
      } 
   });
   return result;
}

function Activity_Load_ExistTest(test,resultData){
   var data   = Core_State_Get("activity", ["data"]);
   Core_State_Set("activity", ["data","id"],resultData["id"]);
   var sheets = data.sheets;
   for (let index = 0; index < resultData.data.length; index++) {
      sheets[index].result = resultData.data[index].score;
   }
   Core_State_Set("activity", ["data","sheets"],sheets);
   Core_State_Set("activity", ["data","index"],resultData.data.length);
   test["index"] = resultData.data.length;
   return test;
}

function Activity_Load_ExistVocabulary(test,resultData){
   Core_State_Set("activity", ["existed-id"],resultData["id"]);
   for (let index = 0; index < resultData.data.length; index++) {
      test[index].score = resultData.data[index].score;
   }
   Core_State_Set("activity", ["data"],test);
   Core_State_Set("activity", ["index"],resultData.data.length);
   return test;
}

