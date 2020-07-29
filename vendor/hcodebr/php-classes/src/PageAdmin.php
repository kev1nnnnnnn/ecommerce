<?php

namespace Hcode;

class PageAdmin extends Page {

	public function __construct($opts = array(), $tpl_dir = "/views/admin/") 
	{
		//chamando o método da classe base Page
		parent::__construct($opts, $tpl_dir);

	}
}



?>