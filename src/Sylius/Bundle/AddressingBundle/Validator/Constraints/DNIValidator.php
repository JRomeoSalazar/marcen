<?php
// src/Sylius/Bundle/AddressingBundle/Validator/Constraints/DNIValidator.php
namespace Sylius\Bundle\AddressingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DNIValidator extends ConstraintValidator
{
    public function validate($cadena, Constraint $constraint)
    {
    	$valido = 1;
        //Comprobamos longitud
	    if (strlen($cadena) != 9) $valido = 0;      
	  
	    //Posibles valores para la letra final 
	    $valoresLetra = array(
	        0 => 'T', 1 => 'R', 2 => 'W', 3 => 'A', 4 => 'G', 5 => 'M',
	        6 => 'Y', 7 => 'F', 8 => 'P', 9 => 'D', 10 => 'X', 11 => 'B',
	        12 => 'N', 13 => 'J', 14 => 'Z', 15 => 'S', 16 => 'Q', 17 => 'V',
	        18 => 'H', 19 => 'L', 20 => 'C', 21 => 'K',22 => 'E'
	    );

	    //Comprobar si es un DNI
	    if (preg_match('/^[0-9]{8}[A-Z]$/i', $cadena))
	    {
	        //Comprobar letra
	        if (strtoupper($cadena[strlen($cadena) - 1]) !=
	            $valoresLetra[((int) substr($cadena, 0, strlen($cadena) - 1)) % 23])
	            $valido = 0;
	    }
	    //Comprobar si es un NIE
	    else if (preg_match('/^[XYZ][0-9]{7}[A-Z]$/i', $cadena))
	    {
	        //Comprobar letra
	        if (strtoupper($cadena[strlen($cadena) - 1]) !=
	            $valoresLetra[((int) substr($cadena, 1, strlen($cadena) - 2)) % 23])
	            $valido = 0;
	    }
	    
	    //Cadena no válida
	    if (!$valido)
	    	$this->context->addViolation($constraint->message, array('%string%' => $cadena)); 
    }
}