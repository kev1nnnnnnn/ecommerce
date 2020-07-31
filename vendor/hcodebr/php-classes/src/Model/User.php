<?php

namespace Hcode\Model;
use \Hcode\DB\sql;
use \Hcode\Model;

//Criando a classe Usuário para a validação ************
class User extends Model{

	const SESSION = "User";

	public static function login($login, $password)
	{	
		//chama a classe Sql para consulta
		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
			":LOGIN"=>$login
		));

		if(count($results) === 0) {

			throw new \Exception("Usuário inexistente, ou senha inválida"); // para achar a exception principal precisa do \
		}

		$data = $results[0];

		//verifica a senha do usuario
		if(password_verify($password, $data["despassword"]) === true)
		{
			$user = new User();

			//busca no banco e cria automaticamente
			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;

		} else {
			throw new \Exception("Usuário inexistente, ou senha inválida");
		}
	}

	//Verifica se o usuário está logado
	public static function verifyLogin($inadmin = true)
	{
		if(
			!isset($_SESSION[User::SESSION])
			||
			!$_SESSION[User::SESSION]
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
			//verifica se pode ter acesso a admin
			||
			(bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin
		) {

			header("Location: /admin/login");
			exit();
		}		
	}

	public static function logout()
	{
		$_SESSION[User::SESSION] = NULL;
	}
}




?>