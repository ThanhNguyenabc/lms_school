async function Progress_Outcomes()
{
 var page = UI_Element_Create("progress/outcomes");
 
 var outcomes   = await Resources_Index("outcomes-catalog");
 var assessment = await Core_Api("Progress_Data_Outcomes");
 Core_State_Set("progress", ["outcomes", "assessment"], assessment);

 var container       = UI_Element_Find(page, "outcomes-levels");
 //container.innerHTML = "";
 
 var n = 0;
 for(var level of ["pr", "a1", "a2", "b1", "b2"])
 {
  // CALCULATE PERCENTAGE OF ACHIEVED OUTCOMES
  var all = Safe_Get(outcomes, ["index", level], []);
  var i   = 0;
  var length = 0;
  for(var outcomeParentname of all)
  {
    var outcomeParent = Safe_Get(outcomes, ["by parent", outcomeParentname], []);
    for(var outcome of outcomeParent)
    {
      length++;
      if(typeof assessment[outcome] != "undefined") i++
    }
  }
  i = i / length;
  console.log(i,length)
  
  var chartbox = UI_Element_Create("progress/outcomes-chart");
  
  
  var color = "color-wheel-" + n % 16;
  var full  = Document_CSS_GetVariable(color);
  var faint = Color_Hex_ToRGBA(Document_CSS_GetVariable(color), 0.25);
  
  chartdata = [{color:full, label:"", value:i}, {color:faint, label:"", value:1-i}];
  var chart = UI_Chart_Doughnut(chartdata, 0.67, {zIndex:100, tooltip:false}); 
  
  //var box = UI_Element_Create("progress/outcomes-level-chart");
  //box.appendChild(chart);
  Document_Element_SetData(chart, "level",       level);
  Document_Element_SetData(chart, "colorfull",  full);
  Document_Element_SetData(chart, "colorfaint", faint);
  
  Document_CSS_SetClass(chart, "style-clickable");
  chart.onclick = Progress_Outcomes_DisplayLevel;
  
  UI_Element_Find(chartbox, "text").innerHTML        = level.toUpperCase();
  UI_Element_Find(chartbox, "subtext").style.display = "none";
  UI_Element_Find(chartbox, "chart").appendChild(chart);
  
  container.appendChild(chartbox);
  
  n++;
 }
 
 
 var container       = UI_Element_Find("module-page");
 container.innerHTML = "";
 container.appendChild(page);
}



async function Progress_Outcomes_DisplayLevel(event, chart)
{
 var element = event.currentTarget;
 var level   = Document_Element_GetData(element, "level"); 
 var full    = Document_Element_GetData(element, "colorfull"); 
 var faint   = Document_Element_GetData(element, "colorfaint"); 
 
 // GET OUTCOMES AND THEIR NAMES
 var assessment = Core_State_Get("progress", ["outcomes", "assessment"], []);
 var outcomes   = await Resources_Index("outcomes-catalog"); 
 var names      = await Resources_Index("outcomes-" + UI_Language_Current());
 
 
 // FOR EACH SLICE (MACRO OUTCOME) SET IT AS FAINT OR FULL BASED ON ACTUAL COUNT OF MICRO
 var chartdata = [];
 var basecolor = Document_CSS_GetVariable("color-dark");
 for(var id of outcomes["index"][level] || []) 
 {
  // CALCULATE
  var all   = outcomes["by parent"][id];
  var count = 0;
  for(var token of all) if(typeof assessment[token] != "undefined") count++;
  count = count / all.length;  
  
  // CHART SLICE
  var value = 1;
  var label = names[id];
  
  if(Numbers_Between(count, 0.9, 1)) var color = count;
  else
  if(Numbers_Between(count, 0.75, 0.9)) var color = 0.5;
  else
  if(Numbers_Between(count, 0.5, 0.75)) var color = 0.33;
  else
  if(Numbers_Between(count, 0.25, 0.5)) var color = 0.2;
  else
  var color = 0.1;
  	  
  color = Color_Hex_ToRGBA(full, color);
  
  chartdata.push({value, label, color, id, count});
 }

 // SORT CHART DATA BY COUNT
 chartdata.sort(
 function(a, b)
 {
  if(a["count"] < b["count"]) return 1;
  else
  if(a["count"] > b["count"]) return -1;
  else
  return 0;
 });

 
 var chart  = UI_Chart_Sunburst(chartdata, 0.33, Progress_Outcomes_DisplayMacro, 
 {
  zIndex:    100, 
  maxlength: 20,
  font:
  {
  }
 });
 
 Document_CSS_SetClass(chart, "style-clickable");
 
 
 console.log(Document_Element_GetObject(chart, "chart"));

 
 
 var container       = UI_Element_Find("level-macros");
 container.innerHTML = "";
 container.appendChild(chart);
}



async function Progress_Outcomes_DisplayMacro(item, chart)
{ 
 // GET OUTCOMES AND THEIR NAMES
 var assessment = Core_State_Get("progress", ["outcomes", "assessment"], []);
 var outcomes   = await Resources_Index("outcomes-catalog"); 
 var names      = await Resources_Index("outcomes-" + UI_Language_Current());
 
 var items      = Safe_Get(outcomes, ["by parent", item["id"]], []);
 
 UI_Element_Find("level-micros-title").innerHTML = String_Capitalize_Initial(names[item["id"]]);
 
 var container  = UI_Element_Find("level-micros-list");
 container.innerHTML = "";
 for(var id of items)
 {
  var name  = String_Capitalize_Initial(names[id]);
  var score = assessment[id];
    
  var element                   = UI_Element_Create("progress/outcomes-item-micro", {name});
  element.style.backgroundColor = item["color"];
  
  if(typeof score == "undefined")
  {
   Document_Element_Disable(element, "style-disabled");
   var score = 0;
  }
  
  var meter = UI_Element_Find(element, "score");
  for(var i = 1; i<=5; i++)
  {
   if(i <= score) var template = "full"; else var template = "empty";
   var point = UI_Element_Create("progress/star-" + template);
  
   meter.appendChild(point);
  } 
  
  container.appendChild(element);
 }
}