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

	//MÉTODO LIST ALL
	public static function listAll()
	{
		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
	}

	//nao pode ser estático para ter acesso as informações no atributo

	public function save() 
	{
		$sql = new Sql();

		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(

			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			"nrphone"=>$this->getnrphone(),
			"inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);
	}


	public function get($iduser)
	{
		 
	 $sql = new Sql();
	 
	 $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser;", array(
	 ":iduser"=>$iduser
	 ));
	 
	 $data = $results[0];
	 
	 $this->setData($data);
		 
	}

	//EDITAR USUARIO
	public function update()
	{
		$sql = new Sql();

		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(

			":iduser"=>$this->getiduser(),
			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			"nrphone"=>$this->getnrphone(),
			"inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);
	}

	//DELETAR USUARIO
	public function delete()
	{
		$sql = new Sql();

		$sql->query("CALL sp_users_delete(:iduser)", array(
			"iduser"=>$this->getiduser()
		));
	}
}	




?>