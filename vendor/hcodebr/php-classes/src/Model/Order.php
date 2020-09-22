<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\Cart;

class Order extends Model {

	const SUCCESS = "Order-Success";
	const ERROR = "Order-Error";

	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)", [
			':idorder'=>$this->getidorder(),
			':idcart'=>$this->getidcart(),
			':iduser'=>$this->getiduser(),
			':idstatus'=>$this->getidstatus(),
			':idaddress'=>$this->getidaddress(),
			':vltotal'=>$this->getvltotal()
		]);

		if (count($results) > 0) {
			$this->setData($results[0]);
		}

	}

	public function get($idorder)
	{

		$sql = new Sql();

		$results = $sql->select("
			SELECT * 
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.idorder = :idorder
		", [
			':idorder'=>$idorder
		]);

		if (count($results) > 0) {
			$this->setData($results[0]);
		}

	}

	public static function listAll()
	{
		$sql = new Sql();

		return $sql->select("
			SELECT * 
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			ORDER BY a.dtregister DESC
		");
	}

	//deleta produto comprado no admin
	public function delete()
	{
		$sql = new Sql();

		$sql->query("DELETE FROM tb_orders WHERE idorder = :idorder",[
			':idorder'=>$this->getidorder()
		]);
	}

	public function getCart():Cart
	{
		$cart = new Cart();

		$cart->get((int)$this->getidcart());

		return $cart;
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

}

?>