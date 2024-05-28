// -----------------------------------------------------------------------------------------------//
//                                                                                                //
//                                    A S S E S S M E N T                                         //
//                                                                                                //
// -----------------------------------------------------------------------------------------------//


function Course_Class_DisplayAssessment(container)
{
 var data            = Core_State_Get("course", ["class-data"]);
 var assessment      = Safe_Get(data, ["seat", "assessment"], {});
 assessment = Round(assessment);
 container.innerHTML = "";
 
 // TEACHER FEEDBACK
 var row = Course_Class_AssessmentFeedback(data);
 
 container.appendChild(row);
 
 
 
 // OUTCOMES FOR LESSON AIMS AND SKILLS
 for(var category of ["outcomes", "core skills"])
 {
  outcomes = Safe_Get(data, ["lesson", category], {});
  //SETUP CORE SKILL FROM OUTCOME SKILL
  if(category == "core skills"){
    var coreskills = {};
    outcomedata= Safe_Get(data, ["lesson", "outcomes"], {});
    for (let id in outcomedata) {
        skill = outcomedata[id]["cefr"]["skill"];
        if(skill) coreskills[skill] = outcomes[skill];
    }
    outcomes = coreskills;
  }
  var row  = Assessment_Display_Assessment(category, outcomes, assessment);
  
  container.appendChild(row);
 }
 
}






function Course_Class_AssessmentFeedback(data, textonly)
{
 // TEACHER PICTURE
 var teacher_id = Safe_Get(data, ["teacher", "id"]);
 
 
 // GET ALL ASSESSMENT DATA FOR THIS STUDENT FOR THIS LESSON
 var assessment = Safe_Get(data, ["seat", "assessment"], {});
  assessment = Round(assessment);
 // GET ALL OUTCOMES FOR THIS LESSON, CATEGORIZED
 var outcomes = [];
 for(var category of ["outcomes", "core skills", "extra skills"])
 {
  outcomes[category] = Safe_Get(data, ["lesson", category], {});
 }
 
 
 // BEHAVIOR, ATTENDANCE AND FEEDBACK TAKEN DIRECTLY FROM SEAT
 var more = Safe_Get(data, ["seat"]);
 
 
 // TRANSCRIBE 
 var text = Assessment_Report_Transcribe(outcomes, assessment, more) + "<br><br>";
 
 if(textonly) return text; 
 
 var row = Assessment_Display_Feedback(teacher_id, text);
 return row;
}



function Round(assessment){
 for (let key in assessment) {
    assessment[key] = Math.ceil(assessment[key]);
 }
 return assessment;
}