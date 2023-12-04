<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {  
	try {
		$git = json_decode(file_get_contents('http://localhost/master.json'));

		$github = [
			'commit' => substr($git->sha,0,7),
			'sha' => $git->sha,
			'message' => $git->commit->message
		];

	} catch(\Exception $ex) 
	{
		$github = ['error'=>'Error al descargar master.json'];
	}

	$service= 'lumen-auth-api';
	$status= 'online';

	$motor= app()->version();
	$server_time = \Carbon\Carbon::now();

	$tag = shell_exec('git describe --always --tags');
	$path = shell_exec('git remote -v');
	$path = explode(' ',preg_replace('/origin|\t/','',$path))[0];


	return compact('service','status','motor','github','server_time');
});

// LOGIN SOCIAL
$router->group(['prefix' => 'social'], function($router)
{
	$router->group(['middleware' => 'auth:social'], function($router) {
		$router->get('/me', 'SocialController@me');
	});

	$router->get('/{driver}', 'SocialController@login');
	$router->get('/{driver}/callback', 'SocialController@callback');
});

// LOGIN CON CREDENCIALES
$router->post('/login', 'AuthController@login');

$router->group(['middleware' => 'auth:api'], function($router)
{
	$router->get('/logout', 'AuthController@logout');
	$router->get('/refresh', 'AuthController@refresh');
	$router->get('/me', 'AuthController@me');
});

// ACL ADMIN , GESTION SOLO POR ROL:SUPERADMIN
// El permiso "gestion_acl" deberia ingresar a esta ruta
$router->get('/pwd', 'AuthController@generateHash');
$router->group(['prefix' => 'acl','middleware' => ['auth:api']], function($router)
{
	$router->get('/hash', 'AuthController@generateHash');

	$router->group(['prefix' => 'users'], function($router)
	{
		$router->get('/{user_id}', 'UsersCrud@show');

		// Gestiona permisos
		$router->get('/', 'UsersCrud@all');
		$router->post('/', 'UsersCrud@add');
		$router->put('/', 'UsersCrud@update');
		$router->delete('/', 'UsersCrud@delete');
	});

	$router->group(['prefix' => 'role'], function($router)
	{
		// Gestiona roles
		$router->get('/', 'RoleCrud@all');
		$router->post('/', 'RoleCrud@add');
		$router->delete('/', 'RoleCrud@delete');

		// Gestiona permisos en roles
		$router->get('/{role}', 'RoleCrud@view');
		$router->post('/{role}', 'RoleCrud@updatePermission');

		// Gestiona relacion de roles y usuarios
		$router->get('/{role}/{userId}', 'RoleCrud@roleToUser');
	});

	$router->group(['prefix' => 'permission'], function($router)
	{
		// Gestiona permisos
		$router->get('/', 'PermissionCrud@all');
		$router->post('/', 'PermissionCrud@add');
		$router->delete('/', 'PermissionCrud@delete');
	});
});