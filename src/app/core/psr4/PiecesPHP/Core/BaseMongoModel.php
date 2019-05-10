<?php
/**
 * BaseMongoModel.php
 */
namespace PiecesPHP\Core;

use MongoDB\Client as MongoClient;

/**
 * BaseMongoModel - Implementación básica de modelo ActiveRecord con MongoDB.
 *
 * Constituye una abstracción de la interacción con base de datos.
 *
 * Los modelos que heredan de este deben tener el nombre NombreModel.
 * 
 * Nota: Necesita la librería de composer mongodb/mongodb
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class BaseMongoModel
{
    /**
     * Configuración del modelo.
     *
     * Usa MongoDB\Client para la conexión.
     *
     * @param string $server_host El string te la conexión con el servidor, por defecto: "mongodb://localhost:27017"
     * @param string $db Nombre de la base de datos
     * @param string $collection Nombre de la colección
     * @return BaseMongoModel
     */
    public function __construct(string $server_host = "mongodb://localhost:27017", string $db = 'db', string $collection = 'collection')
    {

        $version_compare = '1.4.0';
        $operator_compare = '>';
        if (
            !extension_loaded('mongodb')
            ||
            !version_compare(
                phpversion('mongodb'),
                $version_compare,
                $operator_compare
            )
        ) {
            throw new \Exception("La extensión mongodb $operator_compare $version_compare no está disponible.");
        }

        $this->con = new MongoClient($server_host);

        $this->db_name = $db;
        $this->collection_name = $collection;

        $db_name = $this->db_name;
        $collection_name = $this->collection_name;

        $this->db = $this->con->$db_name;
        $this->collection = $this->db->$collection_name;
    }

    /**
     * Inserta un documento.
     *
     * @param array $data Array asosciativo ['clave'=>'valor']
     * @return BaseMongoModel
     */
    public function insert(array $data)
    {
        $required = $this->fields;
        unset($required[array_search('_id', $required)]);
        if (require_keys($required, $data) === true) {
            return $this->collection->insertOne($data) instanceof \MongoDB\InsertOneResult;
        }
        return false;
    }

    /**
     * Obtiene todos los registros de la colección.
     * @return boolean|array
     * Devuelve un array de objetos.
     */
    public function getAll()
    {
        $result = $this->collection->find([]);
        $result = iterator_to_array($result);
        return $result;
    }

    /**
     * Establece la propiedad con.
     * @param \MongoDB\Client $database Instancia de \MongoDB\Client
     * @return void
     */
    protected function setCon(MongoClient $server_host)
    {
        $this->con = $server_host;
    }

    /**
     * Devuelve el valor de la propiedad con.
     * @return \MongoDB\Client
     */
    public function getCon()
    {
        return $this->con;
    }

    /**
     * Establece la propiedad db.
     * @param string $database Nombre de la base de datos
     * @return void
     */
    protected function setDb(string $database)
    {
        $this->db = $this->con->$database;
    }

    /**
     * Devuelve el valor de la propiedad db.
     * @return string El nombre de la base datos
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Asigna el nombre de la colección del modelo.
     * @param string $collection Nombre de la colección
     * @return void
     */
    protected function setCollection(string $collection)
    {
        $this->collection = $this->db->$collection;
    }

    /**
     * Devuelve la colección del modelo.
     * @return MongoCo
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Asigna los campos del modelo.
     * @param array $fields Array que representa las columnas con la estructura ['campo1','campo2'[,...]]
     * @return void
     */
    protected function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return array Los campos
     */
    public function getFields(array $fields)
    {
        return $this->fields;
    }

    /** @var \MongoDB\Client Instancia de \MongoDB\Client */
    protected $con = null;

    /** @var MongoDB\Database Base de datos */
    protected $db = null;

    /** @var MongoDB\Collection Colección */
    protected $collection = null;

    /** @var string Nombre de la base de datos */
    protected $db_name = null;

    /** @var string Nombre de la colección */
    protected $collection_name = null;

    /** @var array|null Array que representa los campos con la estructura ['campo1','campo2'[,...]] */
    protected $fields = null;
}
