<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Address extends Model {

	const SESSION_ERROR = "AddressError";

	public static function getCEP($nrcep) 
	{	
		//pra ter certeza que o cep tem apenas numeros
		$nrcep = str_replace("-", '', $nrcep);

		//informar o php que vai rastrear uma URL
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "http://viacep.com.br/ws/$nrcep/json/");

		//verifica se tem que devolver
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		//variavel pra pegar o retorno
		$data = json_decode(curl_exec($ch), true);

		curl_close($ch);

		return $data;
	}

	public function loadFromCEP($nrcep)
	{

		$data = Address::getCEP($nrcep);

		if (isset($data['logradouro']) && $data['logradouro']) {

			$this->setdesaddress($data['logradouro']);
			$this->setdescomplement($data['complemento']);
			$this->setdesdistrict($data['bairro']);
			$this->setdescity($data['localidade']);
			$this->setdesstate($data['uf']);
			$this->setdescountry('Brasil');
			$this->setdeszipcode($nrcep);

		}

	}


	public function save()
	{
		$sql = new Sql();

		$results = $sql->select("CALL sp_addresses_save(:idaddress, :idperson, :desaddress, :desnumber, :descomplement, :descity, :desstate, :descountry, :deszipcode, :desdistrict", [

			':idaddress'=>$this->getidaddress(),
			':idperson'=>$this->getidperson(),
			':desaddress'=>utf8_decode($this->getdesaddress()),
			':desnumber'=>$this->getdesnumber(),
			':descomplement'=>utf8_decode($this->getdescomplement()),
			':descity'=>utf8_decode($this->getdescity()),
			':desstate'=>utf8_decode($this->getdestate()),
			':descountry'=>utf8_decode($this->getdescontry()),
			':deszipcode'=>$this->getdeszipcode(),
			':desdistrict'=>$this->getdesdistrict()

		]);

		//erro de index nao definido
		if(count($results) > 0 ) {

			$this->setData($results[0]);
		}
	}

	public static function setMsgError($msg)
	{
		$_SESSION[Cart::SESSION_ERROR] = $msg;
	}
	//retorna quando precisar pegar o erro
	public static function getMsgError()
	{	
		//se tiver definido retorna ele mesmo se nao, retorna vazio
		// pega a msg da sessao
		$msg = (isset($_SESSION[Address::SESSION_ERROR])) ? $_SESSION[Address::SESSION_ERROR] : "";
		//limpa a msg da sessao
		Address::clearMsgError();

		return $msg;
	}
	//limpar a sessao
	public static function clearMsgError()
	{
		$_SESSION[Address::SESSION_ERROR] = NULL;
	}
}

?>
