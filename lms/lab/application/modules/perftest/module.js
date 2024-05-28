// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                     P E R F T E S T                                            //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//

async function Perftest_OnLoad(module, data)
{
	
}



async function Perftest_OnShow(module, data)
{
 // HOW LONG?
 var time = Time_Now() + (Client_Location_Parameter("time") || 10);
 
 // WHAT TEST?
 var script = Safe_Function("Perftest_Script_" + String_Capitalize_Initial(Client_Location_Parameter("script")), function(){});  
 
 // GO 
 while(Time_Now() < time)
 {	 
  await script();
 }
 
 // DONE
 console.log("done");
}



async function Perftest_OnUnload()
{
}





// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                      S C R I P T S                                             //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//



async function Perftest_Script_Random()
{
 var scripts = Functions_List("Perftest_Script");
 delete scripts["Perftest_Script_Random"];
 
 var script = scripts[Array_Elements_Random(Object.keys(scripts))];
 await script();
}




async function Perftest_Script_Teach()
{
 var t = Client_Location_Parameter("t") || 0.5;
 t = t * 1000;
 setTimeout(function(){
	location.reload(true);
 },600000);
 try {
		// LOAD TEACHER PAGE AND WAIT A BIT
	await Module_Load("teach", UI_Element_Find("main-module"));
	await Client_Wait((500 + Numbers_Random(0, 2500)) / 1000);

	// FIND ALL CLASSES
	var table   = UI_Element_Find("classes-calendar");
	var cells   = Document_Element_Children(table, true);
	var classes = [];
	for(var cell of cells)
	{
	var data = Document_Element_GetObject(cell, "classes");
	if(data) classes.push(...data);
	}


	// EXTRACT A CLASS AND LOAD IT
	var cls = Array_Elements_Random(classes);
	Core_State_Set("global", ["view-class"], cls["id"]);
	await Module_Load("classroom", UI_Element_Find("main-module"));
	await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);

	// PLAY AROUND WITH STUDENTS AND SLIDES, RANDOMLY
	var actions = Numbers_Random(5, 15);
	for(var i = 0; i<actions; i++)
	{
		var action = Numbers_Random(0, 2);   

		switch(action)
		{
		// CLICK AND ASSESS A STUDENT
		case 0:
			var seats = Core_State_Get("classroom", ["class", "seats"]);
			var seat  = Array_Elements_Random(seats);
			await Classroom_Manage_SelectStudent(undefined, seat);
		
			// ASSESS RANDOMLY
			var assessments = Numbers_Random(1, 5);
			
			// GET SELECTS IN ASSESSMENT PANEL
			var panel    = UI_Element_Find("presentation-panel-right")
			var elements = Document_Element_Children(panel, true);
			var selects  = [];
			for(var element of elements) if(element.tagName == "SELECT") selects.push(element);

			for(var a = 0; a<assessments; a++)
			{
			var select = Array_Elements_Random(selects);
			select.value = Array_Elements_Random(select.options).value;
			Document_Event_Trigger(select, "change");
			
			await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);
			}
		break;

		// CLICK A SLIDE
		case 1:
			// LIST SLIDES
			var panel    = UI_Element_Find("presentation-thumbnails");
			var elements = Document_Element_Children(panel);
			var slides   = [];
			for(var element of elements) if(element.onclick) slides.push(element);
			
			// PICK A RANDOM ONE, CLICK IT, AND WAIT A BIT
			var slide = Array_Elements_Random(slides);
			Document_Event_Trigger(slide, "click");
			
			await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);
		break;
		}
    }
 } catch (error) {
	console.log(error)
 }
 
 
 await Client_Wait((500 + Numbers_Random(0, 100)) / 1000);
}




async function Perftest_Script_Course()
{
 var t = Client_Location_Parameter("t") || 1;
 t = t * 1000;
 setTimeout(function(){
	location.reload(true);
 },600000);
 // LOAD COURSE PAGE AND WAIT A BIT
 await Module_Load("course", UI_Element_Find("main-module"));
 var moduleBody = Core_State_Get("course","module-body");
 await Client_Wait((t + Numbers_Random(0, 100)) / 1000);
 var actions = Numbers_Random(5,15);
 console.log("Random class : " + actions)
 var countcatch = 0;
 for (let a = 0; a < actions; a++) {
	try {
		//RANDOM CLICK LABELS AND GET CLASSES 
		var labels = ['past', 'last month', 'last week', 'this week', 'next week', 'next month', 'future'];
		//var labels = ['past'];
		var classes = [];
		while (classes.length == 0) {
			var key = Numbers_Random(0, 6);
			var label = UI_Element_Find(moduleBody,labels[key]);

			Document_Event_Trigger(label, "click");
			await Client_Wait((t*2 + Numbers_Random(0, 1000)) / 1000);

			var classList   = UI_Element_Find("classes-list");
			if(typeof classList.children[0] != "undefined"){
			/* for (let index = 0; index < classList.children[0].children.length; index++) {
				let element = classList.children[0].children[index];
				classes.push(element);
			} */
			let element = classList.children[0].children[0];
			classes.push(element);
			}
		}
		await Client_Wait((t/2 + Numbers_Random(0, 1000)) / 1000);

		// LOAD CLASS
		var cls = Array_Elements_Random(classes);
		console.log("class:",cls);
		Document_Event_Trigger(cls, "click");
		await Client_Wait((t*5 + Numbers_Random(0, 1000)) / 1000);


		// CREATE HOMEWORK TEST OR VOCAB TEST
		var check = false;
		var count = 0;
		while (check == false) {
			try {
				var rows = UI_Element_Find("content-rows").children;
				check = true;
			} catch (error) {
				count ++;
				await Client_Wait((t*2 + Numbers_Random(0, 1000)) / 1000);
				if(count == 10) break;

				console.log("error........................."+error);
				console.log(UI_Element_Find("content-rows"));
			}
		}
		if(count == 10) continue;
		
		var testaction = [];
		for (let i = 0; i < rows.length; i++) {
			let element = rows[i];
			let caption = UI_Element_Find(element,"caption");
			if(typeof caption !== "undefined" && caption.textContent == "VIDEO") {
				testaction.push(element);
			}
			if(typeof caption !== "undefined" && caption.textContent == "HOMEWORK") {
				testaction.push(element);
			}
			if(typeof caption !== "undefined" && caption.textContent == "VOCABULARY") {
				testaction.push(element);
			}
			if(typeof caption !== "undefined" && caption.textContent == "MATERIALS") {
				testaction.push(element);
			}
			if(typeof caption !== "undefined" && caption.textContent == "CLASSROOM") {
				testaction.push(element);
			}
		}
		console.log("testaction:",testaction);
		// RAMDOM TEST ACTION
		var testactionramdom = [];
		let actionnumber = Numbers_Random(1, testaction.length);
		//actionnumber = 1;
		for (let r = 0; r < actionnumber; r++) {
			let actionindex = Numbers_Random(0, testaction.length - 1);
			testactionramdom.push(testaction[actionindex]);
		}

		for (let e = 0; e < testactionramdom.length; e++) {
			var element = testactionramdom[e];
			let caption = UI_Element_Find(element,"caption");
			let action = UI_Element_Find(element,"actions");
			if(typeof action !== "undefined")
			{
				var listaction = action.children
				// RANDOM LISTACTION
				var randomlistaction = [];
				let actionnumber = Numbers_Random(1, listaction.length);
				for (let r = 0; r < actionnumber; r++) {
					var actionindex = Numbers_Random(0, listaction.length - 1);
					if(typeof listaction[actionindex] != "undefined")
					randomlistaction.push(listaction[actionindex]);
				}
				//console.log("randomlist",randomlistaction);
				for (let l = 0; l < randomlistaction.length; l++) {
					var act = randomlistaction[l];
					if(act.classList.contains("style-disabled")) {}
					else
					{
						try {
							Document_Event_Trigger(act, "click");

							await Client_Wait((t*2 + Numbers_Random(0, 1000)) / 1000);

							switch (caption.textContent) {
								case "HOMEWORK": case "VOCABULARY":
									
									// RANDOM SCORE TEST 
									var type =  Core_State_Get("activity", ["type"]);
									var checkfinishtest = UI_Element_Find("activity-finish");
									if(typeof checkfinishtest == "undefined") break;
									while (checkfinishtest.style.display == "none"){
										// NOT FINISH
										let score = Numbers_Random(0, 10) / 10;

										await Client_Wait((t/2 + Numbers_Random(0, 1000)) / 1000);

										switch (type) {
											case "test":
												let sheets = Core_State_Get("activity", ["data","sheets"]);
												console.log(sheets);
												let index = Core_State_Get("activity", ["data","index"]);
												if(sheets == []) console.log("check empty...........................")

												sheets[index]["result"] = score;
												Core_State_Set("activity", ["data","sheets"],sheets);
												await Client_Wait((t/2 + Numbers_Random(0, 1000)) / 1000);

												if(index == sheets.length -1 )
												{
													var next = UI_Element_Find("activity-next")
													next.style.display = "none";
													
													checkfinishtest.style.display = "flex";
													Document_Element_Animate(checkfinishtest, "flash 1.5s 1.5");
													await Client_Wait((t/2 + Numbers_Random(0, 1000)) / 1000);
												}
												else {
													// RANDOM CHOOSE SHEETS
													var nextslide = Numbers_Random(index + 1, sheets.length - 1)
													for (let slideindex = index; slideindex < nextslide; slideindex++) {
														sheets[slideindex]["result"] = score;
														Core_State_Set("activity", ["data","sheets"],sheets);	
													}
													Core_State_Set("activity", ["data","index"],nextslide - 1);

													Activity_Test_Next();
												}

												break;
										
											case "vocabulary":
												let data = Core_State_Get("activity", ["data"]);
												let vocabindex = Core_State_Get("activity", ["index"]);

												data[vocabindex]["score"] = score;
												Core_State_Set("activity", ["data"],data);
												await Client_Wait((t/2 + Numbers_Random(0, 1000)) / 1000);

												if(vocabindex == data.length -1 )
												{
													checkfinishtest.style.display = "flex";
													Activity_Vocabulary_UpdateLastTerm();
													await Client_Wait((t/2 + Numbers_Random(0, 1000)) / 1000);
												} 
												else{
													// RANDOM CHOOSE DATA
													var nextdata = Numbers_Random(vocabindex + 1, data.length - 1)
													for (let slideindex = vocabindex; slideindex < nextdata; slideindex++) {
														data[slideindex]["score"] = score;
														Core_State_Set("activity", ["data"],data);	
													}
													Core_State_Set("activity", ["index"],nextdata - 1);

													Activity_Vocabulary_ControlNext();
												} 
												break;
										}
										
										await Client_Wait((t/2 + Numbers_Random(0, 500)) / 1000);
									}
									
									//Document_Event_Trigger(checkfinishtest, "click");
									await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);

									break;
								case "CLASSROOM":
									var slides = Core_State_Get("activity",["presentation","slides"]);
									for (let s = 0; s < slides.length; s++) {
										var next = UI_Element_Find("activity-next")
										if(typeof next == "undefined") break;
										Document_Event_Trigger(next, "click");
										await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);
									}
									break;
								default:
									var playbutton = UI_Element_Find(UI_Element_Find("player"),"control-play");
									if(typeof playbutton !== "undefined"){
										UI_Element_Find("video").muted="muted";
										await Client_Wait((t*2 + Numbers_Random(0, 500)) / 1000);
										Document_Event_Trigger(playbutton, "click");

										await Client_Wait((t*10 + Numbers_Random(0, 1000)) / 1000);
									} 
									else await Client_Wait((t*3 + Numbers_Random(0, 1000)) / 1000);
									break;
							}
						} catch (error) {
							await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);
							console.log("error........................."+error)
						}
					} 
					//REMOVE POPUP 
					let buttonclose = UI_Element_Find("buttons");
					if(typeof buttonclose !== "undefined")	Document_Event_Trigger(buttonclose, "click");
					await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);
			
					var popup = UI_Element_Find("layer-back");
					if(typeof popup !== "undefined" ) 
					{
						popup.parentElement.remove();
						var body = UI_Element_Find(document.body, "main-body");
						Document_CSS_UnsetClass(body, "style-blurred-medium");
					}
					await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);

				}	
			}
		}
		
		await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);
	} catch (th) {
		countcatch++;
		console.log("error:..." + th);
		if(countcatch == 5) location.reload();
		continue;
	}
 }

}



async function Perftest_Script_CourseNoRandom()
{
  var t = Client_Location_Parameter("t") || 1;
  t = t * 1000;
  // LOAD COURSE PAGE AND WAIT A BIT
  await Module_Load("course", UI_Element_Find("main-module"));
  var moduleBody = Core_State_Get("course","module-body");
	try {
		//RANDOM CLICK LABELS AND GET CLASSES 
		var labels = ['last week', 'this week'];
		var classes = [];
    var key = 0;
		while (key < 2) {
			var label = UI_Element_Find(moduleBody,labels[key]);

			Document_Event_Trigger(label, "click");
			await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);

			var classList   = UI_Element_Find("classes-list");
			if(typeof classList.children[0] != "undefined")
			for (let index = 0; index < classList.children[0].children.length; index++) {
				let element = classList.children[0].children[index];
				console.log("class:",element);
        
        // LOAD CLASS
        Document_Event_Trigger(element, "click");
        await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);
        // CREATE HOMEWORK TEST OR VOCAB TEST
        var check = false;
        while (check == false) {
          try {
            var rows = UI_Element_Find("content-rows").children;
            check = true;
          } catch (error) {
            console.log("error........................."+error);
            console.log(UI_Element_Find("content-rows"));
            break;
          }
        }

        if(check)
        for (let i = 0; i < rows.length; i++) {
          let element = rows[i];
          let caption = UI_Element_Find(element,"caption");
          let action = UI_Element_Find(element,"actions");
        
          if(typeof action !== "undefined" && typeof caption !== "undefined")
          {
            var listaction = action.children
            // RANDOM LISTACTION
            var randomlistaction = [];
            let actionnumber = Numbers_Random(1, listaction.length);
            for (let r = 0; r < actionnumber; r++) {
              var actionindex = Numbers_Random(0, listaction.length - 1);
              if(typeof listaction[actionindex] != "undefined")
              randomlistaction.push(listaction[actionindex]);
            }
      
            for (let l = 0; l < randomlistaction.length; l++) {
              var act = randomlistaction[l];
              if(act.classList.contains("style-disabled")) {}
              else
              {
                try {
                  Document_Event_Trigger(act, "click");

                  await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);

                  switch (caption.textContent) {
                    case "HOMEWORK": case "VOCABULARY":
                      
                      // RANDOM SCORE TEST 
                      var type =  Core_State_Get("activity", ["type"]);
                      var checkfinishtest = UI_Element_Find("activity-finish");
                      if(typeof checkfinishtest == "undefined") break;
                      while (checkfinishtest.style.display == "none"){
                        // NOT FINISH
                        let score = Numbers_Random(0, 10) / 10;

                        await Client_Wait((t/4 + Numbers_Random(0, 1000)) / 1000);

                        switch (type) {
                          case "test":
                            let sheets = Core_State_Get("activity", ["data","sheets"]);
                            console.log(sheets);
                            let index = Core_State_Get("activity", ["data","index"]);
                            if(sheets == []) console.log("check empty...........................")

                            sheets[index]["result"] = score;
                            Core_State_Set("activity", ["data","sheets"],sheets);
                            await Client_Wait((t/4 + Numbers_Random(0, 1000)) / 1000);

                            if(index == sheets.length -1 )
                            {
                              var next = UI_Element_Find("activity-next")
                              next.style.display = "none";
                              
                              checkfinishtest.style.display = "flex";
                              Document_Element_Animate(checkfinishtest, "flash 1.5s 1.5");
                              await Client_Wait((t/4 + Numbers_Random(0, 1000)) / 1000);
                            }
                            else {
                              // RANDOM CHOOSE SHEETS
                              var nextslide = Numbers_Random(index + 1, sheets.length - 1)
                              for (let slideindex = index; slideindex < nextslide; slideindex++) {
                                sheets[slideindex]["result"] = score;
                                Core_State_Set("activity", ["data","sheets"],sheets);	
                              }
                              Core_State_Set("activity", ["data","index"],nextslide - 1);

                              Activity_Test_Next();
                            }

                            break;
                        
                          case "vocabulary":
                            let data = Core_State_Get("activity", ["data"]);
                            let vocabindex = Core_State_Get("activity", ["index"]);

                            data[vocabindex]["score"] = score;
                            Core_State_Set("activity", ["data"],data);
                            await Client_Wait((t/4 + Numbers_Random(0, 1000)) / 1000);

                            if(vocabindex == data.length -1 )
                            {
                              checkfinishtest.style.display = "flex";
                              Activity_Vocabulary_UpdateLastTerm();
                              await Client_Wait((t/4 + Numbers_Random(0, 1000)) / 1000);
                            } 
                            else{
                              // RANDOM CHOOSE DATA
                              var nextdata = Numbers_Random(vocabindex + 1, data.length - 1)
                              for (let slideindex = vocabindex; slideindex < nextdata; slideindex++) {
                                data[slideindex]["score"] = score;
                                Core_State_Set("activity", ["data"],data);	
                              }
                              Core_State_Set("activity", ["index"],nextdata - 1);

                              Activity_Vocabulary_ControlNext();
                            } 
                            break;
                        }
                        
                        await Client_Wait((t/4 + Numbers_Random(0, 500)) / 1000);
                      }
                      
                      //Document_Event_Trigger(checkfinishtest, "click");
                      await Client_Wait((t/2 + Numbers_Random(0, 1000)) / 1000);

                      break;
                    case "CLASSROOM":
                      var slides = Core_State_Get("activity",["presentation","slides"]);
                      for (let s = 0; s < slides.length; s++) {
                        var next = UI_Element_Find("activity-next")
                        if(typeof next == "undefined") break;
                        Document_Event_Trigger(next, "click");
                        await Client_Wait((t/2 + Numbers_Random(0, 1000)) / 1000);
                      }
                      break;
                    default:
                      var playbutton = UI_Element_Find(UI_Element_Find("player"),"control-play");
                      if(typeof playbutton !== "undefined"){
                        UI_Element_Find("video").muted="muted";
                        await Client_Wait((t + Numbers_Random(0, 500)) / 1000);
                        Document_Event_Trigger(playbutton, "click");

                        await Client_Wait((t*5 + Numbers_Random(0, 1000)) / 1000);
                      } 
                      else await Client_Wait((t*3 + Numbers_Random(0, 1000)) / 1000);
                      break;
                  }
                } catch (error) {
                  await Client_Wait((t/2 + Numbers_Random(0, 1000)) / 1000);
                  console.log("error........................."+error)
                }
              } 
              //REMOVE POPUP 
              let buttonclose = UI_Element_Find("buttons");
              if(typeof buttonclose !== "undefined")	Document_Event_Trigger(buttonclose, "click");
              await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);
          
              var popup = UI_Element_Find("layer-back");
              if(typeof popup !== "undefined" ) 
              {
                popup.parentElement.remove();
                var body = UI_Element_Find(document.body, "main-body");
                Document_CSS_UnsetClass(body, "style-blurred-medium");
              }
              await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);

            }	
          }
        }
			}
      await Client_Wait((t + Numbers_Random(0, 1000)) / 1000);
      key++;
		}
	} catch (th) {
		throw th;
	}
}




async function Perftest_Script_TeachNoRandom()
{
 try {
  var t = Client_Location_Parameter("t") || 0.5;
  t = t * 1000;
		// LOAD TEACHER PAGE AND WAIT A BIT
	await Module_Load("teach", UI_Element_Find("main-module"));
	await Client_Wait((t/2 + Numbers_Random(0, 2500)) / 1000);

	// FIND ALL CLASSES
	var table   = UI_Element_Find("classes-calendar");
	var cells   = Document_Element_Children(table, true);
	var classes = [];
  var dateStart = Date_Portion(Date_Week_FirstDay(Date_Now()), "date-only") + "0000"; 
  var dateEnd = Date_Portion(Date_Week_LastDay(Date_Now()), "date-only") + "2359"; 
	for(var cell of cells)
	{
	  var data = Document_Element_GetObject(cell, "classes");
    if(data)
    {
      var date_start = data[0]["date_start"];
      if(dateEnd - date_start > 0  && date_start - dateStart > 0) 
      {
        var dataIndex = 0;
        while (dataIndex < data.length) {
          // EXTRACT A CLASS AND LOAD IT
            var cls = data[dataIndex];
            console.log("classroom:",cls)
            Core_State_Set("global", ["view-class"], cls["id"]);
            await Module_Load("classroom", UI_Element_Find("main-module"));
            await Client_Wait((t + Numbers_Random(0, 2500)) / 1000);

            // PLAY AROUND WITH STUDENTS AND SLIDES, RANDOMLY
            var actions = Numbers_Random(5, 15);
            for(var i = 0; i<actions; i++)
            {
              var action = Numbers_Random(0, 2);   

              switch(action)
              {
              // CLICK AND ASSESS A STUDENT
              case 0:
                var seats = Core_State_Get("classroom", ["class", "seats"]);
                var seat  = Array_Elements_Random(seats);
                await Classroom_Manage_SelectStudent(undefined, seat);
              
                // ASSESS RANDOMLY
                var assessments = Numbers_Random(1, 5);
                
                // GET SELECTS IN ASSESSMENT PANEL
                var panel    = UI_Element_Find("presentation-panel-right")
                var elements = Document_Element_Children(panel, true);
                var selects  = [];
                for(var element of elements) if(element.tagName == "SELECT") selects.push(element);

                for(var a = 0; a<assessments; a++)
                {
                var select = Array_Elements_Random(selects);
                select.value = Array_Elements_Random(select.options).value;
                Document_Event_Trigger(select, "change");
                
                await Client_Wait((t/4 + Numbers_Random(0, 1000)) / 1000);
                }
              break;

              // CLICK A SLIDE
              case 1:
                // LIST SLIDES
                var panel    = UI_Element_Find("presentation-thumbnails");
                var elements = Document_Element_Children(panel);
                var slides   = [];
                for(var element of elements) if(element.onclick) slides.push(element);
                
                // PICK A RANDOM ONE, CLICK IT, AND WAIT A BIT
                var slide = Array_Elements_Random(slides);
                if(typeof slide != "undefined")
                Document_Event_Trigger(slide, "click");
                
                await Client_Wait((t/4 + Numbers_Random(0, 1000)) / 1000);
              break;
              }
            }
          dataIndex++;
        }
      }
    }
	}

 } catch (error) {
	console.log(error)
 }
 
}