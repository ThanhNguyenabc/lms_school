<?PHP
$googleservice = Core_Service_Read('google');
require_once $googleservice;
//require_once (__ROOT__.'\services\google\google.php');
function Reminder_Create($course, $teacher_id = null)
{
 $db    = Core_Database_Open();
 $course = (object)$course;
 $reminder_id = Reminder_File_Create($course, $teacher_id);

 $query      = "UPDATE courses SET reminder_id='$reminder_id' WHERE id=$course->id";
 SQL_Query($query, $db);
 
 SQL_Close($db);
 
 return $reminder_id;
}

function Reminder_File_Create($course, $teacher_id = null){
	$client = Google_Client_Create();
	if(empty($course->reminder_id)){
		$file = 0;
		$file = Check_Exist_Reminder_File($client,$course);
		if(!$file){
			$file = Create_Reminder_File($client,$course);
		}
		$course->reminder_id=$file->id;
		$resultView = Set_Has_Link_Can_View_Permission_Reminder_File($client,$course->reminder_id);
	}
	$resultPer = Set_Edit_Permission_Reminder_File($client,$course->reminder_id, $teacher_id);
	$resultView = Set_Has_Link_Can_View_Permission_Reminder_File($client,$course->reminder_id);
	return $course->reminder_id;
}

function Check_Exist_Reminder_File($client,$course){
	$result = array();
	$service = new Google_Service_Drive($client);
	try {
		$parameters = array();
		$parameters['q'] = "name='".$course->name."-reminder'";
		$parameters['pageSize'] = 1;
		$files = $service->files->listFiles($parameters);
		$result = $files->getFiles();
	}
	catch (Exception $e) {
		print "An error occurred: " . $e->getMessage();
		$pageToken = NULL;
	}
	if($result[0]){
		return $result[0];
	}
	return 0;
}

function Create_Reminder_File($client,$course){
	try{
		$service = new Google_Service_Drive($client);
		$file = new Google_Service_Drive_DriveFile();
		$file->setMimeType('application/vnd.google-apps.document');
		$file->setName($course->name.'-reminder');
		$file->setParents(array('1QLBlIwexThK_bEYlp7sxpOLKNssrbp1V'));
		$parameters = array();
		$parameters['fields'] = 'id,name,mimeType';
		$result = $service->files->create($file,$parameters);
		return $result;
	}
	catch(Exception $e){
		print_r($e);die;
		return 0;
	}
	return 0;
}

function Get_List_Permission_Reminder_File($client,$fileId,$email=null){
	try{
		if(!empty($email)){
			$service = new Google_Service_Drive($client);
			$parameters = array();
			$parameters['fields'] = 'kind,permissions';
			$result = $service->permissions->listPermissions($fileId,$parameters);
			$permissions = $result->getPermissions();
			foreach ($permissions as $permission) {
				if($permission->emailAddress == $email){
					if($permission->role == 'writer' || $permission->role == 'owner')
						return true;
				}
			}
			return 0;
		}
		return true;
	}
	catch(Exception $e){
		return 0;
	}
	return 0;
}

function Set_Edit_Permission_Reminder_File($client,$fileId,$teacher_id=null){
	try{
		$db    = Core_Database_Open();
		$query = "SELECT email FROM users WHERE id=$teacher_id";
		$data = SQL_Query($query, $db);
		$email = $data[0]['email'] ?? null;
 		SQL_Close($db);
		if(!empty($email) && !Get_List_Permission_Reminder_File($client,$fileId,$email)){
			$service = new Google_Service_Drive($client);
			$fileBody = new Google_Service_Drive_Permission();
			$fileBody->setRole('writer');
			$fileBody->setType('user');
			$fileBody->setEmailAddress($email);
			$parameters = array();
			//$parameters['emailMessage'] = "Please access to this reminder file and put your reminder for class.";
			$parameters['sendNotificationEmail'] = false;
			$result = $service->permissions->create($fileId,$fileBody,$parameters);
		}
		return true;
	}
	catch(Exception $e){
		print_r($e);
		return 0;
	}
	return 0;
}

function setWebPublishReminderFile($client,$fileId){
	try{
		$service = new Google_Service_Drive($client);
		$revision = new Google_Service_Drive_Revision();
		$revision->setPublished(true);
		$revision->setPublishedOutsideDomain(true);
		$revision->setPublishAuto(true);
		$result = $service->revisions->update($fileId,1,$revision);
		return true;
	}
	catch(Exception $e){
		return 0;
	}
	return 0;
}

function Set_Has_Link_Can_View_Permission_Reminder_File($client,$fileId){
	try {
		$service = new Google_Service_Drive($client);
		$fileBody = new Google_Service_Drive_Permission();
		$fileBody->setType('anyone');
		$fileBody->setRole('reader');
		$result = $service->permissions->create($fileId,$fileBody);
		return true;
	}
	catch(Exception $e){
		print_r($e);
		return 0;
	}
	return 0;
}
?>