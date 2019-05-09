<?php

require_once __DIR__.'/db.class.php';
require_once __DIR__.'/user.class.php';
require_once __DIR__.'/project.class.php';

class ProjectService
{
	//---------------------------------------------------------------
	//users

	function getUserById($id)
	{
		try
		{
			$db = DB::getConnection();
			$st = $db->prepare( 'SELECT id, username, password_hash, email,
				 registration_sequence, has_registered FROM dz2_users where id=:id' );
			$st->execute(array('id' => $id));
		}
		catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }

		$row = $st->fetch();
		if($row === false)
			return null;

		else
			return new User($row['id'], $row['username'], $row['password_hash'],
			$row['email'], $row['registration_sequence'], $row['has_registered'] );


	}


	function getAllUsers( )
	{
		try
		{
			$db = DB::getConnection();
			$st = $db->prepare( 'SELECT id, username, password_hash,
        email, registration_sequence, has_registered FROM dz2_users' );
			$st->execute();
		}
		catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }

		$arr = array();
		while( $row = $st->fetch() )
		{
			$arr[] = new User( $row['id'], $row['username'], $row['password_hash'], $row['email'], $row['registration_sequence'], $row['has_registered'] );
		}

		return $arr;
	}

	function getUserByUsernameAndPassword($username, $password)
	{
		try
		{
			$db = DB::getConnection();
			$st = $db->prepare( 'SELECT id, username, password_hash,
				email, registration_sequence, has_registered FROM dz2_users
				where username=:username' );

			$st->execute(array('username' => $username));
		}
		catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }



		$row = $st->fetch();
		$arr = array();

		if($row === false)
			return $arr;

		else
		{
			$hash = $row['password_hash'];


			if(password_verify($password, $hash))
			{
				$arr[] = new User( $row['id'], $row['username'], $row['password_hash'],
				 $row['email'], $row['registration_sequence'], $row['has_registered'] );

				return $arr;
			}



			else return $arr;

		}

	}


	////////////////////////////////////////////////////////////////
	//projects


	function getProjectById($id)
	{
		try
		{
			$db = DB::getConnection();
			$st = $db->prepare( 'SELECT id,id_user,title,abstract,number_of_members,status
				FROM dz2_projects where id=:id' );

			$st->execute(array('id' => $id));

		}
		catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }

		$row = $st->fetch();
		if($row === false)
			return null;

		else
			return new Project($row['id'], $row['id_user'], $row['title'],
			 $row['abstract'], $row['number_of_members'], $row['status']);

	}



	function getAllProjects()
	{

		try
		{
			$db = DB::getConnection();
			$st = $db->prepare( 'SELECT id,id_user,title,
				abstract,number_of_members,status FROM dz2_projects' );
			$st->execute();
		}
		catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }

		$arr = array();
		while( $row = $st->fetch() )
		{
			$arr[] = new Project( $row['id'], $row['id_user'], $row['title'],
			 $row['abstract'], $row['number_of_members'], $row['status'] );
		}

		return $arr;

	}

	function getMyProjects($id_user)
	{

		try
		{
			$db = DB::getConnection();
			$st = $db->prepare( 'SELECT id,id_user,title, abstract,
				number_of_members,status FROM dz2_projects where id_user=:id_user' );
			$st->execute(array('id_user' => $id_user));

		}
		catch (PDOException $e) { exit( 'PDO error ' . $e->getMessage() ); }

		$arr = array();
		while( $row = $st->fetch() )
		{
			$arr[] = new Project( $row['id'], $row['id_user'], $row['title'],
			 $row['abstract'], $row['number_of_members'], $row['status'] );
		}

		return $arr;



	}

	function createProject($id_user,$projectName,$projectDescription,$projectNumber)
	{
		try
		{
			$db = DB::getConnection();
			$st = $db->prepare( 'INSERT into
				dz2_projects(id_user,title,abstract,number_of_members,status)
				values (:id_user,:title,:abstract,:number_of_members,:status)' );

			$st->execute(array('id_user' => $id_user, 'title' => $projectName,
			 'abstract' => $projectDescription, 'number_of_members' => $projectNumber,
		 		'status' => 'open'));


		}
		catch (PDOException $e) { exit( 'PDO error ' . $e->getMessage() ); }



	}

	function getUsersFromMembersByProjectId($project_id)
	{
		try
		{
			$db = DB::getConnection();
			$st = $db->prepare( 'SELECT id, username, password_hash, email,
				 registration_sequence, has_registered from dz2_users where id in
				(SELECT id_user from dz2_members where id_project=:project_id)' );

			$st->execute(array('project_id' => $project_id));


		}
		catch (PDOException $e) { exit( 'PDO error ' . $e->getMessage() ); }

		$arr = array();
		while( $row = $st->fetch() )
		{
			$arr[] = new User( $row['id'], $row['username'], $row['password_hash'], $row['email'], $row['registration_sequence'], $row['has_registered'] );
		}

		return $arr;

	}



function addNewMember($id_project, $id_user)
{
	try
	{
		$db = DB::getConnection();
		$st = $db->prepare( 'INSERT into dz2_members(id_project,id_user,member_type)
		values (:id_project, :id_user, :member_type)' );

		$st->execute(array('id_project' => $id_project, 'id_user' => $id_user, 'member_type' => 'member' ));


	}
	catch (PDOException $e) { exit( 'PDO error ' . $e->getMessage() ); }


}



function setStatusToClosed($id_project)
{
	try
	{
		$db = DB::getConnection();
		$st = $db->prepare('UPDATE dz2_projects set status=:status where id=:id_project');

		$st->execute(array('status' => 'closed', 'id_project' => $id_project));


	}
	catch (PDOException $e) { exit( 'PDO error ' . $e->getMessage() ); }

}









	//////////////////////////////////////////////////////////////////////////////
	//members






};


 ?>
