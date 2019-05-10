<?php 
/**
 * FormValidation.php
 */
namespace PiecesPHP\Core\Forms;

/**
 * FormValidation - Validación de entrada de formularios.
 * 
 * Funciona como módulo independiente
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @version     v.1
 * @copyright   Copyright (c) 2018
 * @extends <a target='blank' href='https://github.com/rlanvin/php-form'>\Form\Validator</a>
 * @wiki <a target='blank' href='https://github.com/rlanvin/php-form/wiki'>Wiki</a>
 * @info Funciona como módulo independiente
 */
class FormValidation extends \Form\Validator
{
    /**
     * Contructor
     * @link https://github.com/rlanvin/php-form/wiki/Rules Rules
     * @param array $rules Array asosciativo de las reglas de validación
     * <pre>
     * //Ejemplo:
     * $validator = new FormValidation([
     *          'input_name' => ['required', 'trim'],
     *          'input_name_2' => ['required', 'trim', 'email']
     *          [,...]
     *      ]);
     * </pre>
     */
    public function __construct(array $rules)
    {
        parent::__construct($rules);
    }

    /**
     * Valida los datos.
     * Devuelve ['valid'=>boolean,'values'=>array] o ['valid'=>boolean,'errors'=>array,'values'=>array]
     * @param array $data Array asosciativo con los datos
     * <pre>
     * Ejemplo:
     * $validator->executeValidation([
     *          'input_name' => value,
     *          'input_name_2' => value
     *          [,...]
     *      ]);
     * </pre>
     * @return array Un array asociativo con la información de la validación. </br>
     * Si la validación fue exitosa ['valid'=>true,'values'=>array] si no ['valid'=>false,'errors'=>array,'values'=>array]
     */
    public function executeValidation($data)
    {
        if ($this->validate($data)) {
            return [
                'valid' => true,
                'values' => $this->getValues(),
            ];
        } else {
            return [
                'valid' => false,
                'errors' => $this->getErrors(),
                'values' => $this->getValues()
            ];
        }
    }
}
