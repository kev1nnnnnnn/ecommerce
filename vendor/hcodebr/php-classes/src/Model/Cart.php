<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;

class Cart extends Model {

	const SESSION = "Cart";
	const SESSION_ERROR = "CartError";

	//verifica se precisa inserir um carrinho novo, se ja tem esse carrinho vai pegar da sessao, se a sessao foi perdida

	public static function getFromSession()
	{
		$cart = new Cart();

		if(isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {

			$cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
		
		} else {

			$cart->getFromSessionID();

			//verificar se carrega o carrinho
			if(!(int)$cart->getidcart() > 0) {

				$data = [
					'dessessionid'=>session_id()
				];

				if(User::checkLogin(false)) {

					//quer dizer que ta logado
				//se tem usuario logado
					$user = User::getFromSession();

					//o id do usuario
					$data['iduser'] = $user->getiduser();
				} 
				
				$cart->setData($data);

				$cart->save();

				$cart->setToSession();

			}
		}

		return $cart;
	}

	public function setToSession() {

		$_SESSION[Cart::SESSION] = $this->getValues();

	}

	public function getFromSessionID()
	{
		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", [
			':dessessionid'=>session_id()
		]);

		if(count($results) > 0) {

			$this->setData($results[0]);
		}

		
	}

	public function get(int $idcart)
	{
		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", [
			':idcart'=>$idcart
		]);

		if(count($results) > 0) {
			
			$this->setData($results[0]);
		}
	}
	
	//metodos basicos SAVE
	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", [
			':idcart'=>$this->getidcart(),
			':dessessionid'=>$this->getdessessionid(),
			':iduser'=>$this->getiduser(),
			':deszipcode'=>$this->getdeszipcode(),
			':vlfreight'=>$this->getvlfreight(),
			':nrdays'=>$this->getnrdays()
		]);

		$this->setData($results[0]);

	}

	//metodos para adicionar e remover o produto **** 
	public function addProduct(Product $product)
	{
		$sql = new Sql();

		$sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES(:idcart, :idproduct)", [
			':idcart'=>$this->getidcart(),
			':idproduct'=>$product->getidproduct()
		]);

		//forçando o recalculo
		$this->getCalculateTotal();
	}

	//se ta removendo um ou todos os produtos igual a esse.
	public function removeProduct(Product $product, $all = false)
	{
		$sql = new Sql();

		if ($all) {

			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL", [
				':idcart'=>$this->getidcart(),
				':idproduct'=>$product->getidproduct()
			]);

		} else {

			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1", [
				':idcart'=>$this->getidcart(),
				':idproduct'=>$product->getidproduct()
			]);
		}
		//forçando o recalculo
		$this->getCalculateTotal();
	}

	//adicionar produtos e somar
	public function getProducts()
	{

		$sql = new Sql();

		$rows = $sql->select("
			SELECT b.idproduct, b.desproduct , b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal 
			FROM tb_cartsproducts a 
			INNER JOIN tb_products b ON a.idproduct = b.idproduct 
			WHERE a.idcart = :idcart AND a.dtremoved IS NULL 
			GROUP BY b.idproduct, b.desproduct , b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl 
			ORDER BY b.desproduct
		", [
			':idcart'=>$this->getidcart()
		]);

		return Product::checkList($rows);

	}

	//metodo que traz todos os itens e a soma de cada um dos atributos dos produtos do carrinho
	public function getProductsTotals()
	{

		$sql = new Sql();

		$results = $sql->select("
			SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth, SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd
			FROM tb_products a
			INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct
			WHERE b.idcart = :idcart AND dtremoved IS NULL;
		", [
			':idcart'=>$this->getidcart()
		]);

		if (count($results) > 0) {
			return $results[0];
		} else {
			return [];
		}

	}

	//metodo para calcular o FRETE
	public function setFreight($nrzipcode)
	{
		//certificar que se digitar o traço seja removido
		$nrzipcode = str_replace('-', '', $nrzipcode);

		//pegar as informações totais do carrinho
		$totals = $this->getProductsTotals();

		//verifica se tem algum produto no carrinho
		if ($totals['nrqtd'] >= 1) {

			//erro de regra de negocio
			if ($totals['vlheight'] < 2) $totals['vlheight'] = 2;

			if ($totals['vllength'] < 16) $totals['vllength'] = 16;

	        if ($totals['vlwidth'] < 11) $totals['vlwidth'] = 11;

			//espera um array
			$qs = http_build_query([
            'nCdEmpresa'=>'',
            'sDsSenha'=>'',
            'nCdServico'=>'40010',
            'sCepOrigem'=>'09853120',
            'sCepDestino'=>$nrzipcode,
            'nVlPeso'=>$totals['vlweight'],
            'nCdFormato'=>'1',
            'nVlComprimento'=>$totals['vllength'],
            'nVlAltura'=>$totals['vlheight'],
            'nVlLargura'=>$totals['vlwidth'],
            'nVlDiametro'=>'0',
            'sCdMaoPropria'=>'S',
            'nVlValorDeclarado'=>$totals['vlprice'],
            'sCdAvisoRecebimento'=>'S'
        ]);
			//funcao para ler xml
			$xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);
        	
        	$result = $xml->Servicos->cServico;

			//verifica se trouxe resultado de erro
			if ($result->MsgErro != '') {
            Cart::setMsgError($result->MsgErro);
        } else {
            Cart::clearMsgError();
        }
	        $this->setnrdays($result->PrazoEntrega);
	        $this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
	        $this->setdeszipcode($nrzipcode);
	        $this->save();

	        return $result;
	    } else {
	    
	    }
	}

	public static function formatValueToDecimal($value):float
		{
			$value = str_replace('.', '', $value);
			return str_replace(',', '.', $value);
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
		$msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : "";
		//limpa a msg da sessao
		Cart::clearMsgError();

		return $msg;
	}
	//limpar a sessao
	public static function clearMsgError()
	{
		$_SESSION[Cart::SESSION_ERROR] = NULL;
	}

	public function updateFreight()
	{	
		//verifica se tem um cep dentro do carrinho
		if ($this->getdeszipcode() != '') {

			$this->setFreight($this->getdeszipcode());
		}
	}

	public function getValues()
	{	
		//ver o total, somar subtotal e total, e colocar a informação no objeto
		$this->getCalculateTotal();

		return parent::getValues();
	}

	public function getCalculateTotal()
	{	
		$this->updateFreight();

		$totals = $this->getProductsTotals();

		$this->setvlsubtotal($totals['vlprice']);
		$this->setvltotal($totals['vlprice'] + (float)$this->getvlfreight());
	}
}

?>