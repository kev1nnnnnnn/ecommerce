<?php

namespace Hcode\Model;

use \Hcode\DB\sql;
use \Hcode\Model;
use \Hcode\Mailer;


//Criando a classe Usuário para a validação ************
class User extends Model{

	const SESSION = "User";
    const SECRET = "HcodePhp7_Secret";
    const SECRET_IV = "HcodePhp7_Secret_IV";
    const ERROR = "UserError";
    const ERROR_REGISTER = "UserErrorRegister";
    const SUCCESS = "UserSuccess";

    //verifica se a sessao existe, se o id do usuario é maior que 0
    public static function getFromSession()
    {

   		$user = new User();

   		if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {
   			
   			//se nao carregou traz o usuario vazio sem id
    		$user->setData($_SESSION[User::SESSION]);
    	}

    	return $user;
    }
    //metodo check login
    public static function checkLogin($inadmin = true)
    {
    	if (
			!isset($_SESSION[User::SESSION])
			||
			!$_SESSION[User::SESSION]
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
		) {
			//Não está logado
			return false;

    		//to fazendo uma verificação de uma rota da adm? se fizer 
    	} else {

    		//so acontece se tentar acessar uma rota de adm
    		if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {

				return true;

			} else if ($inadmin === false) {

				return true;
    		
    		//se algo for diferente, nao esta logado
    		} else {

    			return false;
    		}
    	}

    }

	public static function login($login, $password)
	{	
		//chama a classe Sql para consulta
		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b ON a.idperson = b.idperson WHERE a.deslogin = :LOGIN", array(
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

			//quando traz do banco faz o encode
			$data['desperson'] = utf8_encode($data["desperson"]);

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
 
    if(!User::checkLogin($inadmin)) {
 
        if ($inadmin){
            header("Location: /admin/login");
        } else {
            header("Location: /login");
        }
        exit;
 
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

			":desperson"=>utf8_decode($this->getdesperson()),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>User::getPasswordHash($this->getdespassword()),
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

	 $data['desperson'] = utf8_encode($data["desperson"]);
	 
	 $this->setData($data);
		 
	}

	//EDITAR USUARIO
	public function update()
	{
		$sql = new Sql();

		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(

			":iduser"=>$this->getiduser(),
			":desperson"=>utf8_decode($this->getdesperson()),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>User::getPasswordHash($this->getdespassword()),
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

	//Esqueceu sua senh
		public static function getForgot($email, $inadmin = true)
	{

		$sql = new Sql();

		$results = $sql->select("
			SELECT *
			FROM tb_persons a
			INNER JOIN tb_users b USING(idperson)
			WHERE a.desemail = :email;
		", array(
			":email"=>$email
		));

		if (count($results) === 0)
		{

			throw new \Exception("Não foi possível recuperar a senha.");

		}
		else
		{

			$data = $results[0];

			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
				":iduser"=>$data['iduser'],
				":desip"=>$_SERVER['REMOTE_ADDR']
			));

			if (count($results2) === 0)
			{

				throw new \Exception("Não foi possível recuperar a senha.");

			}
			else
			{

				$dataRecovery = $results2[0];

				$code = openssl_encrypt($dataRecovery['idrecovery'], 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

				$code = base64_encode($code);

				//se for verdade manda o link da adm
				if ($inadmin === true) {

					$link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";

				} else {

					$link = "http://www.hcodecommerce.com.br/forgot/reset?code=$code";
					
				}				

				$mailer = new Mailer($data['desemail'], $data['desperson'], "Redefinir senha da Hcode Store", "forgot", array(
					"name"=>$data['desperson'],
					"link"=>$link
				));				

				$mailer->send();

				return $link;
			}
		}
	}


	public static function validForgotDecrypt($code)
	{

		$code = base64_decode($code);

		$idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

		$sql = new Sql();

		$results = $sql->select("
			SELECT *
			FROM tb_userspasswordsrecoveries a
			INNER JOIN tb_users b USING(iduser)
			INNER JOIN tb_persons c USING(idperson)
			WHERE
				a.idrecovery = :idrecovery
				AND
				a.dtrecovery IS NULL
				AND
				DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
		", array(
			":idrecovery"=>$idrecovery
		));

		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
		}
		else
		{

			return $results[0];
		}

	}
	
	public static function setFogotUsed($idrecovery)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
			":idrecovery"=>$idrecovery
		));
	}

	public function setPassword($password)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
			":password"=>$password,
			":iduser"=>$this->getiduser()
		));
	}

	public static function setError($msg)
	{
		$_SESSION[User::ERROR] = $msg;
	}

	public static function getError()
	{	
		//verifica se o erro está definido, se estiver definido e nao for vazio, retorna msg de erro, se nao, retorna vazio.
		$msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';

		//assim que pega o erro, limpa para nao ficar erro na session.
		User::ClearError();

		return $msg;
	}

	//metodo pra limpar o erro
	public static function ClearError()
	{
		$_SESSION[User::ERROR] = NULL;
	}

	public static function setSuccess($msg)
	{
		$_SESSION[User::SUCCESS] = $msg;
	}

	public static function getSuccess()
	{	
		//verifica se o erro está definido, se estiver definido e nao for vazio, retorna msg de erro, se nao, retorna vazio.
		$msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : '';

		//assim que pega o erro, limpa para nao ficar erro na session.
		User::ClearSuccess();

		return $msg;
	}

	//metodo pra limpar o erro
	public static function clearSuccess()
	{
		$_SESSION[User::SUCCESS] = NULL;
	}

	public static function setErrorRegister($msg)
	{	
		$_SESSION[User::ERROR_REGISTER] = $msg;
	}

	//método onde os campos não podem ser em branco.
	public static function getErrorRegister()
	{
		$msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

		User::clearSuccessRegister();

		return $msg;
	}

	public static function clearErrorRegister()
	{
		$_SESSION[User::ERROR_REGISTER] = NULL;
	}

	//sem conflitos entre usuarios
	public static function checkLoginExist($login)
	{
		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
			':deslogin'=>$login
		]);
	}

	public static function getPasswordHash($password)
	{
		return password_hash($password, PASSWORD_DEFAULT, [
			'cost'=>12
		]);
	}

}	




?>