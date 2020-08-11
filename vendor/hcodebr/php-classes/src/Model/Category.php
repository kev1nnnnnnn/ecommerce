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
	

}	




?>