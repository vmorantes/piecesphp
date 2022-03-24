<?php
/**
 * Importer.php
 */
namespace PiecesPHP\Core\Importer;

use PiecesPHP\Core\Importer\Collections\ResponseCollection;

/**
 * Importer.
 *
 * Importador
 *
 * @package     PiecesPHP\Core\Importer
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class Importer
{
    /**
     * @var Schema
     */
    protected $schema;
    /**
     * @var bool
     */
    protected $update = false;
    /**
     * @var array
     */
    protected $data = [];
    /**
     * @var ResponseCollection
     */
    protected $responses = null;
    /**
     * @var int
     */
    protected $totalImported = 0;
    /**
     * @var int
     */
    protected $totalProcessed = 0;
    /**
     * @var string
     */
    protected $title = 'Importador';
    /**
     * @var string
     */
    protected $description = '';

    const LANG_GROUP = 'importerModule';

    /**
     * @param Schema $schema
     * @param array $data
     * @param string $title
     * @return static
     */
    public function __construct(Schema $schema, array $data, string $title = null)
    {
        $this->$title = __(self::LANG_GROUP, $this->title);
        $this->schema = $schema;
        $this->data = $data;
        $this->responses = new ResponseCollection();
        if (!is_null($title)) {
            $this->title = $title;
        }
        set_title($this->title);
    }

    /**
     * @return ResponseCollection
     */
    public function import()
    {

        foreach ($this->data as $index => $data) {

            foreach ($data as $name => $value) {
                //Convertir claves en mayúsculas
                if (is_string($name)) {
                    unset($data[$name]);
                    $name = trim(mb_strtoupper($name));
                    $data[$name] = $value;
                }
            }

            $this->totalProcessed += 1;

            $response = new Response(true, '', $index); //Respuesta
            $expecteds = $this->schema->getFieldNames(); //Campos esperados
            $subFieldExpecteds = $this->schema->getSubFieldsNames(); //Sub campos esperados

            //Aplicar valores y verificar campos obligatorios
            foreach ($expecteds as $expectedName) {

                $field = $this->schema->getFieldByName($expectedName); //Campo
                $value = ''; //Valor que se asignará

                //Verificar que no sea un campo con subcampos
                if ($field->hasMetaProperties()) {
                    continue; //Saltar en caso de ser un campo con subcampos
                }

                //Verificar si el campo tiene entrada y establecer valor
                $hasInput = $this->existsHasEntryAndProcess($data, [
                    $field->getName(),
                    $field->getHumanReadable(),
                ], $value);

                //Aplicar valores de entrada
                $this->processValueAndResponse($field, $value, $hasInput, $response);

            }

            //Verifcar si el schema acepta subcampos
            if ($this->schema->hasSubFields()) {

                //Verificar si tiene subcampos que añadir
                $hasInput = false; //bool[]

                //Aplicar valores y verificar subcampos
                foreach ($subFieldExpecteds as $parentName => $subFields) {

                    //Recorrer subcampos
                    foreach ($subFields as $subField) {
                        $subValue = ''; //Valor que se asignará

                        //Verificar si el subcampo tiene entrada y establecer valor
                        $hasInput = $this->existsHasEntryAndProcess($data, [
                            $subField->getName(),
                            $subField->getHumanReadable(),
                        ], $subValue);

                        //Aplicar valores de entrada
                        $this->processValueAndResponse($subField, $subValue, $hasInput, $response, true);
                    }

                }
            }

            if ($response->getSuccess()) {
                //Si fue exitosa la validación del campo

                //Insertar registro en la base de datos
                try {

                    $this->schema->runBefore();
                    $schemaMode = $this->schema->mode();
                    $modeUpdate = $this->update;

                    if ($schemaMode === Schema::MODE_INSERT) {
                        $modeUpdate = false;
                    } elseif ($schemaMode === Schema::MODE_UPDATE) {
                        $modeUpdate = true;
                    }

                    $success = false;

                    if ($modeUpdate) {
                        $success = $this->schema->update(); //Actualizar
                    } else {
                        $success = $this->schema->insert(); //Insertar
                    }

                    $row = $response->getPosition(); //Posición de la fila en los datos

                    if ($success) {
                        //Si se insertó

                        //Aumentar el conteo de registros insertados y agregar mensaje de operación exitosa
                        $this->totalImported += 1;

                        if ($modeUpdate) {
                            $response->appendMessage(sprintf(__(self::LANG_GROUP, 'Registro de la fila %s actualizado.'), $row));
                        } else {
                            $response->appendMessage(sprintf(__(self::LANG_GROUP, 'Registro de la fila %s insertado.'), $row));
                        }

                    } else {
                        $response->setSuccess(false);
                        //Si no se insertó

                        //Agregar mensaje de la operación errónea
                        if ($modeUpdate) {
                            $response->appendMessage(
                                sprintf(
                                    __(self::LANG_GROUP, 'El registro de la fila %s no ha podido ser actualizado debido a un error desconocido.'),
                                    $row
                                )
                            );
                        } else {
                            $response->appendMessage(
                                sprintf(
                                    __(self::LANG_GROUP, 'El registro de la fila %s no ha podido ser insertado debido a un error desconocido.'),
                                    $row
                                )
                            );
                        }

                    }

                } catch (\Exception $e) {
                    //Si no se insertó

                    //Agregar estado y mensaje de la operación errónea
                    $response->setSuccess(false);
                    $response->appendMessage($e->getMessage());

                    //Agregar respuesta al listado
                    $this->responses->append($response);

                    continue; //Pasar al siguiente registro
                }

                //Agregar respuesta al listado
                $this->responses->append($response);

            } else {
                //Si no fue exitosa la validación del campo

                //Agregar respuesta al listado
                $this->responses->append($response);
                continue; //Pasar al siguiente registro

            }

        }

        return $this->responses; //Devolver el valor
    }

    /**
     * @param array $input
     * @param array $names
     * @param mixed &$value
     * @return bool
     */
    private function existsHasEntryAndProcess(array $input, array $names, &$value)
    {
        $has = false;

        foreach ($names as $name) {
            $name = mb_strtoupper($name);
            if (is_string($name)) {
                if (isset($input[$name])) {
                    if (!is_null($input[$name])) {
                        $value = $input[$name];
                        $has = true;
                        break;
                    }
                }
            }
        }

        return $has;
    }

    /**
     * @param Field $field
     * @param mixed $value
     * @param bool $hasInput
     * @param Response &$response
     * @param bool $isMeta
     * @return void
     */
    private function processValueAndResponse(Field $field, $value, bool $hasInput, Response &$response, bool $isMeta = false)
    {
        $name = $field->getName();
        $humanName = $field->getHumanReadable();

        //Agregar error en caso de no tener valor siendo obligatorio
        if (!$field->isOptional() && !$hasInput) {
            $response->setSuccess(false);
            $response->appendMessage(
                sprintf(
                    __(self::LANG_GROUP, 'Error: el campo %s es obligatorio.'),
                    "$name/$humanName"
                )
            );
        }

        //Aplicar valor por defecto
        if (!$hasInput && $field->isOptional()) {
            $value = $field->getDefaultValue();
            $hasInput = true;
        }

        //Aplicar valores de entrada
        if ($hasInput) {
            try {
                if (!$isMeta) {
                    $this->schema->setFieldValue($name, $value);
                } else {
                    $this->schema->setSubFieldValue($field->getParentName(), $name, $value);
                }
            } catch (\Exception $e) {
                $response->setSuccess(false);
                $response->appendMessage($e->getMessage());
            }
        }
    }

    /**
     * @param bool $yes
     * @return static
     */
    public function setUpdate(bool $yes)
    {
        $this->update = $yes;
        return $this;
    }

    /**
     * @param string $description
     * @return static
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param string $title
     * @return static
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @return Response[]
     */
    public function getResponses()
    {
        $responses = $this->responses->getArrayCopy();
        $this->resetResponses();
        return $responses;
    }

    /**
     * @return int
     */
    public function getTotalImported()
    {
        $totalImported = $this->totalImported;
        $this->resetTotalImported();
        return $totalImported;
    }

    /**
     * @return int
     */
    public function getTotalProcessed()
    {
        $totalProcessed = $this->totalProcessed;
        $this->resetTotalProcessed();
        return $totalProcessed;
    }

    /**
     * @return void
     */
    protected function resetResponses()
    {
        $this->responses = new ResponseCollection();
    }

    /**
     * @return void
     */
    protected function resetTotalImported()
    {
        $this->totalImported = 0;
    }

    /**
     * @return void
     */
    protected function resetTotalProcessed()
    {
        $this->totalProcessed = 0;
    }

}
