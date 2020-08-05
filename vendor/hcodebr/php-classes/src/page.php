<?php


//chama o namespace Hcode
namespace Hcode;

//chama o namespace Rain Tpl
use Rain\Tpl;

//classe PAGE
class Page {

	private $tpl;
	private $options = [];
	private $defaults = [
		"header"=>true,
		"footer"=>true,
		"data"=>[]
	];

	//primeiro a ser executado ****************************
	public function __construct($opts = array(), $tpl_dir = "/views/"){

		$this->options = array_merge($this->defaults, $opts);

		//configuração do RAIN TPL
		$config = array(
						"tpl_dir"       => $_SERVER["DOCUMENT_ROOT"].$tpl_dir,
						"cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views-cache/",
						"debug"         => false
		);

		Tpl::configure( $config );

		$this->tpl = new Tpl;	

		$this->setData($this->options["data"]);

		//criando o arquivo header para repetir em outras pag.
		if($this->options["header"] === true) $this->tpl->draw("header");
	}

	private function setData($data = array())
	{
		foreach ($data as $key => $value) {
			$this->tpl->assign($key, $value);
		}
	}
		

	public function setTpl($name, $data =  array(), $returnHTML = false)
	{
		$this->setData($data);

		return $this->tpl->draw($name, $returnHTML);
	}

	//último a ser executado ****************************
	public function __destruct() {

		if($this->options["footer"] === true) $this->tpl->draw("footer");

	}
}

?>