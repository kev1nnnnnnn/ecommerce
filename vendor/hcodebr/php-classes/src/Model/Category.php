<?php

namespace Hcode\Model;

use \Hcode\DB\sql;
use \Hcode\Model;
use \Hcode\Mailer;


//Criando a classe Usuário para a validação ************
class Category extends Model{

	
	//MÉTODO LIST ALL
	public static function listAll()
	{
		$sql = new Sql();

		//lista a tabela organizado pelo nome da categoria
		return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
	}

	public function save()
	{
		$sql = new Sql();

		$results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array(

			":idcategory"=>$this->getidcategory(),
			":descategory"=>$this->getdescategory(),	
		));

		$this->setData($results[0]);

		Category::updateFile();
	}

	public function get($idcategory)
	{
		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", [
			"idcategory"=>$idcategory
		]);

		$this->setData($results[0]);
	}

	public function delete()
	{
		$sql = new Sql();

		$sql->query("DELETE FROM tb_categories WHERE idcategory =:idcategory", [
			'idcategory'=>$this->getidcategory()
		]);

		//chamar o update file quando fizer um delete
		Category::updateFile();
	}

	//atualizar o arquivo
	public static function updateFile()
	{
		//traz as categorias do banco de dados
		$categories = Category::listAll();

		$html = [];

		foreach ($categories as $row) {
			array_push($html, '<li><a href="/categories/'.$row['idcategory'].'">'.$row['descategory'].'</a></li>');
		}
		//salva o arquivo
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR."views". DIRECTORY_SEPARATOR . "categories-menu.html", implode('', $html));
	}
	
	public function getProducts($related = true)
	{
		$sql = new Sql();

		if($related === true) {

			return $sql->select("
				SELECT * FROM tb_products WHERE idproduct IN(
				SELECT a.idproduct
				from tb_products a
				INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
				WHERE B.idcategory = :idcategory
				);
			", [
				':idcategory'=>$this->getidcategory()
			]);

		} else {

			return $sql->select("
				SELECT * FROM tb_products WHERE idproduct NOT IN(
				SELECT a.idproduct
				from tb_products a
				INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
				WHERE B.idcategory = :idcategory
				);
			", [
				':idcategory'=>$this->getidcategory()
			]);
		}
	}
	//paginação *********
	public function getProductsPage($page = 1, $itemsPerPage = 3)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products a
			INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
			INNER JOIN tb_categories c ON c.idcategory = b.idcategory
			WHERE c.idcategory = :idcategory
			LIMIT $start, $itemsPerPage;
		", [
			':idcategory'=>$this->getidcategory()
		]);

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data'=>Product::checkList($results),
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];

	}

	//Adicionando produto das categorias
	public function addProduct(Product $product)
	{
		$sql = new Sql();

		$sql->query("INSERT INTO tb_productscategories(idcategory, idproduct)VALUES(:idcategory, :idproduct)", [
			":idcategory"=>$this->getidcategory(),
			":idproduct"=>$product->getidproduct()
		]);
	}

	//Removendo da categoria
	public function removeProduct(Product $product)
	{
		$sql = new Sql();

		$sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct", [
			":idcategory"=>$this->getidcategory(),
			":idproduct"=>$product->getidproduct()
		]);
	}

	//MÉTODO DE PAGINAÇÃO
	public static function getPage($page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_categories 
			ORDER BY descategory
			LIMIT $start, $itemsPerPage;
		");

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];

	}

	//MÉTODO DE BUSCA
	public function getPageSearch($search, $page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_categories
			WHERE descategory LIKE :search 
			ORDER BY descategory
			LIMIT $start, $itemsPerPage;
		", [
			":search"=>'%'.$search.'%'
		]);

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data'=>($results),
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];

	}

}	




?>