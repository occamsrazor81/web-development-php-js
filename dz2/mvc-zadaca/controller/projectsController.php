<?php
session_start();
require_once __DIR__.'/../model/projectservice.class.php';

class ProjectsController
{

	public function index()
	{
		$ps = new ProjectService();

		$title = 'All projects';
		$idProjectList = $ps->getAllProjects();
		//trebamo jos izvuc ime za projectList

		$projectList = array();
		foreach ($idProjectList as $project )
		{
			$user = $ps->getUserById($project->id_user);
			if( $user === null )
					exit( 'There is no user with id = ' . $project->id_user );
			$projectList[] = array('id' => $project->id,'author' => $user->username,
			'title' => $project->title, 'status' => $project->status);


		}


		require_once __DIR__.'/../view/projects_index.php';
	}


///promijeniti da bude i za ostale projekte, a ne samo one di je autor
  public function myProjects()
  {
    $ps = new ProjectService();

    $title = 'My projects';
  	//$idProjectList = $ps->getMyProjects($_SESSION['id_user']);

		$idProjectList = $ps->getAllMyProjects($_SESSION['id_user']);



		//trebamo jos izvuc ime za projectList

		$projectList = array();
		foreach ($idProjectList as $project )
		{
			$user = $ps->getUserById($project->id_user);
			if( $user === null )
					exit( 'There is no user with id = ' . $project->id_user );

			$applicants = $ps->getApplicantsViaProjectId($project->id);


			$projectList[] = array('id' => $project->id,'author' => $user->username,
			'title' => $project->title, 'status' => $project->status,
			'applicants' => $applicants);


		}


    require_once __DIR__.'/../view/projects_myIndex.php';
  }




	public function createNew()
	{
		$title ='Create new project';

		require_once __DIR__.'/../view/projects_newProject.php';
	}

	public function createNewProject()
	{
		$ps = new ProjectService();

		if(!isset($_SESSION['id_user']))
		{
			header('Location: index.php?rt=projects/newProject');
			exit();
		}

		$id_user = $_SESSION['id_user'];



////////////////////////
		if(!isset($_POST['projectName']) || !isset($_POST['projectDescription'])
		|| !isset($_POST['projectNumber'])
		|| !preg_match('/^[a-zA-Z0-9 ,.]+$/',$_POST['projectName'])
		|| !preg_match('/^[1-9][0-9]*$/',(int)$_POST['projectNumber']))
		{
			header('Location: index.php?rt=projects/newProject ');
			exit();
		}

		//////////////////////////////////

		$projectName = $_POST['projectName'];
		$projectDescription = $_POST['projectDescription'];
		$projectNumber = (int)$_POST['projectNumber'];

		$ps->createProject($id_user,$projectName,$projectDescription,$projectNumber);

		// dodaj ga u svoj projekt kao membera

		$targetProject = $ps->getMyLastAddedProject($id_user);

		$ps->initializeProject($targetProject->id,$id_user);

		header('Location: index.php?rt=projects/newProject');
		exit();

	}


	public function findProjectDetails()
	{
		$ps = new ProjectService();


		if(!isset($_POST['project_id']))
		{
			header('Location: index.php?rt=projects/newProject');
			exit();
		}

		$title = 'Project description';

		$project_id = (int)$_POST['project_id'];

		$targetProject = $ps->getProjectById($project_id);

		//sad imamo naslov, description
		//trebamo se dokopati authora i membera

		$author_id = $targetProject->id_user;
		$author = $ps->getUserById($author_id);
		$author_username = $author->username;

		//jos samo ostale members (iz zadnje tablice)

//podesiti td vraca samo membere, ili application_accepted
		$targetUsers = $ps->getUsersFromMembersByProjectId($project_id);

		$userNames = array();
		foreach($targetUsers as $tUser)
		{
			if($tUser->id != $author_id)
			$userNames[] = $tUser->username;

		}

		$projectDescriptionList = array('id_project' => $targetProject->id, 'author' => $author_username,
		'title' => $targetProject->title, 'description' => $targetProject->abstract,
		'targetSize' => $targetProject->number_of_members,'members' => $userNames , 'status' => $targetProject->status);


		require_once __DIR__.'/../view/projects_showDescription.php';


	}

//
// 	public function applyForProject()
// 	{
// 		$ps = new ProjectService();
//
// 		if(!isset($_POST['id_project_apply']))
// 		{
// 			header('Location: index.php?rt=projects/showDescription');
// 			exit();
// 		}
//
// 		$title = 'Project application';
//
// 		$id_user = $_SESSION['id_user'];
// 		$id_project = $_POST['id_project_apply'];
//
// 		$ps->addNewMember($id_project, $id_user);
//
// 		$targetUsers = $ps->getUsersFromMembersByProjectId($id_project);
// 		$targetProject = $ps->getProjectById($id_project);
//
// 		if(count($targetUsers) == (int)$targetProject->number_of_members)
// 			$ps->setStatusToClosed($id_project);
//
//
//
//
// //promijeniti poslije
// 		header('Location: index.php?rt=projects/newProject');
// 		exit();
//
//
// 	}
//
//


	/////////////////////////////////////////////////////////////////


		public function applyForProject()
		{
			$ps = new ProjectService();

			if(!isset($_POST['id_project_apply']))
			{
				header('Location: index.php?rt=projects/showDescription');
				exit();
			}

			$title = 'Project application';

			$id_user = $_SESSION['id_user'];
			$id_project = $_POST['id_project_apply'];

			$ps->sendApplication($id_project, $id_user);

			// $targetUsers = $ps->getUsersFromMembersByProjectId($id_project);
			// $targetProject = $ps->getProjectById($id_project);
			//
			// if(count($targetUsers) == (int)$targetProject->number_of_members)
			// 	$ps->setStatusToClosed($id_project);



			header('Location: index.php?rt=projects/pendingApps');
			exit();


		}


		public function pendingApplications()
		{
			$ps = new ProjectService();

			//zelimo skupiti sve projekte na kojima je pending application za trenutnog usera

			$title = 'Pending applications';

			$id_user = $_SESSION['id_user'];

			$pendingProjects = $ps->getApplicationPendingProjectsByUserId($id_user);
			$acceptedProjects = $ps->getApplicationAcceptedProjectsByUserId($id_user);

			$projectApps = array();


			foreach($pendingProjects as $pending)
			{
				$author = $ps->getUserById($pending->id_user);
				$author_username = $author->username;

				$projectApps[] = array('author' => $author_username, 'status' => $pending->status,
			 	'title' => $pending->title, 'application' => 'pending');

			}

			foreach($acceptedProjects as $accepted)
			{
				$author = $ps->getUserById($accepted->id_user);
				$author_username = $author->username;

				$projectApps[] = array('author' => $author_username, 'status' => $accepted->status,
			 	'title' => $accepted->title, 'application' => 'accepted');

			}




			require_once __DIR__.'/../view/projects_pendingApps.php';

		}


//++++++++++++++++++++++++++++++++++++++++++++++++++
		public function acceptOrRejectApplicants()
		{
			$ps = new ProjectService();

			if(isset($_POST['project_id']))
			{

				$title = 'Project description';

				$project_id = (int)$_POST['project_id'];

				$targetProject = $ps->getProjectById($project_id);

				//sad imamo naslov, description
				//trebamo se dokopati authora i membera

				$author_id = $targetProject->id_user;
				$author = $ps->getUserById($author_id);
				$author_username = $author->username;

				//jos samo ostale members (iz zadnje tablice)

				$targetUsers = $ps->getUsersFromMembersByProjectId($project_id);

				$userNames = array();
				foreach($targetUsers as $tUser)
				{
					if($tUser->id != $author_id)
					$userNames[] = $tUser->username;
				}

				$projectDescriptionList = array('id_project' => $targetProject->id, 'author' => $author_username,
				'title' => $targetProject->title, 'description' => $targetProject->abstract,
				'targetSize' => $targetProject->number_of_members,'members' => $userNames , 'status' => $targetProject->status);




				require_once __DIR__.'/../view/projects_myShowDescription.php';
			}

			elseif (isset($_POST['accept']))
			{

				$title = 'Application accepted';

				// zelimo za dani id_user (koji je value u buttonu)
				// podesiti njegov member_type na application_accepted

				$id_project = substr($_POST['accept'], 0, strpos($_POST['accept'],'_'));
				$id_user = substr($_POST['accept'], strpos($_POST['accept'],'_') + 1);


				// echo "str_pos(_) = ".strpos($_POST['accept'],'_')."<br>";
				// echo "id_user = ".$id_user."<br>id_project = ".$id_project;


				$ps->setApplicationAccepted($id_project,$id_user);

				$targetUsers = $ps->getUsersFromMembersByProjectId($id_project);
				$targetProject = $ps->getProjectById($id_project);

				if(count($targetUsers) == (int)$targetProject->number_of_members)
					$ps->setStatusToClosed($id_project);

					header('Location: index.php?rt=projects/myProjects');
					exit();

				}

			elseif (isset($_POST['reject']))
			{

				$title = 'Application rejected';

				$id_project = substr($_POST['reject'], 0, strpos($_POST['reject'],'_'));
				$id_user = substr($_POST['reject'],  strpos($_POST['reject'],'_') + 1);

				// echo "str_pos(_) = ".strpos($_POST['reject'],'_')."<br>";
				// echo "id_user = ".$id_user."<br>id_project = ".$id_project;




				$ps->setApplicationRejected($id_project, $id_user);

				header('Location: index.php?rt=projects/myProjects');
				exit();

			}


		}


		/////////////////////////////





		public function pendingInvitations()
		{
			$ps = new ProjectService();

			//zelimo skupiti sve pozivnice gdje smo mi pozvani
			$title = 'Pending Invitations';

			$id_user = $_SESSION['id_user'];

			$pendingProjects = $ps->getInvitationPendingProjectsByUserId($id_user);
			$acceptedProjects = $ps->getInvitationAcceptedProjectsByUserId($id_user);

			$projectInvites = array();


			foreach($pendingProjects as $pending)
			{
				$author = $ps->getUserById($pending->id_user);
				$author_username = $author->username;

				$projectInvites[] = array('id_project' => $pending->id,'author' => $author_username, 'status' => $pending->status,
			 	'title' => $pending->title, 'application' => 'pending');

			}

			// foreach($acceptedProjects as $accepted)
			// {
			// 	$author = $ps->getUserById($accepted->id_user);
			// 	$author_username = $author->username;
			//
			// 	$projectInvites[] = array('id_project' => $pending->id, 'author' => $author_username, 'status' => $accepted->status,
			//  	'title' => $accepted->title, 'application' => 'accepted');
			//
			// }




			require_once __DIR__.'/../view/projects_pendingInvites.php';



		}


		public function acceptOrRejectInvitations()
		{

			$ps = new ProjectService();

			//zelimo ovisno o stisnutom gumbu podesiti podatke u bazi


			if(isset($_POST['id_project_accept']))
			{
				$title = 'Invite accepted';

				$id_user = $_SESSION['id_user'];
				$id_project = $_POST['id_project_accept'];

				$ps->setInvitationAccepted($id_project, $id_user);


				$targetUsers = $ps->getUsersFromMembersByProjectId($id_project);
				$targetProject = $ps->getProjectById($id_project);

				if(count($targetUsers) == (int)$targetProject->number_of_members)
					$ps->setStatusToClosed($id_project);

					header('Location: index.php?rt=projects/myProjects');
					exit();

			}


			elseif(isset($_POST['id_project_reject']))
			{
				$title = 'Invite rejected';

				$id_user = $_SESSION['id_user'];
				$id_project = $_POST['id_project_reject'];

				$ps->setInvitationRejected($id_project, $id_user);

				header('Location: index.php?rt=projects/myProjects');
				exit();

			}


		}


		public function inviteSomeone()
		{

			$ps = new ProjectService();


			if(!isset($_POST['id_project_invite']))
			{
				header('Location: index.php?rt=projects/showDescription');
				exit();
			}

			elseif (!isset($_POST['invite_name'])
			|| !preg_match('/^[a-zA-Z]{1,20}$/',$_POST['invite_name']))
			{

				header('Location: index.php?rt=projects/showDescription');
				exit();
			}

			$title = 'Project invitation';
			$id_project = $_POST['id_project_invite'];
			$user_name = $_POST['invite_name'];

			echo "id_project = ".$id_project."username = ".$user_name;



			//moramo provjeriti postoji li taj user

			$user = $ps->getUserByName($user_name);
			if( $user === null )
					exit( 'There is no user with name = ' . $user_name );



			$ps->sendInvitation($id_project, $user->id);

			// $targetUsers = $ps->getUsersFromMembersByProjectId($id_project);
			// $targetProject = $ps->getProjectById($id_project);
			//
			// if(count($targetUsers) == (int)$targetProject->number_of_members)
			// 	$ps->setStatusToClosed($id_project);



			header('Location: index.php?rt=projects/myProjects');
			exit();

		}









};

?>
