<?php

namespace Hcode;

class Model {

	private $values = [];

	//dinamico com métodos mágicos
	public function __call($name, $args)
	{
		//se for posic 0, traga 0 1 2
		$method = substr($name, 0, 3);
		//apartir da 3 até o final
		$fieldname = substr($name, 3, strlen($name));

		switch ($method) {
			case 'get':
				return (isset($this->values[$fieldname])) ? $this->values[$fieldname] : NULL;
				break;
			case 'set':
				$this->values[$fieldname] = $args[0];
				break;
		}
	}

	//metodo, cada campo retornado, vai criar um atributo com o valor de cada uma das informações
	public function setData($data = array())
	{
		foreach ($data as $key => $value) {
			
			//chama os métodos automaticamente
			$this->{"set".$key}($value);
		}
	}

	public function getValues()
	{
		return $this->values;
	}
}

// Se for GET vai trazer a informação e retornar, 
//se for SET tem que atribuir valor do atributo da informação que foi passada

?>

