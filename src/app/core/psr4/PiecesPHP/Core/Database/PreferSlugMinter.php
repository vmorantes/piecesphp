<?php

/**
 * PreferSlugMinter.php
 */

namespace PiecesPHP\Core\Database;

/**
 * PreferSlugMinter — el acuñado del `preferSlug`.
 *
 * Vive en un trait y no en `EntityMapperExtensible` porque `preferSlug` NO lo tienen todos los
 * mappers: solo los que se usan por slug. En la clase base habría que anotar una propiedad que
 * la mayoría no tiene, y eso es escribir una mentira para callar al analizador.
 *
 * @property int|null $preferSlug
 * @property int|null $id
 * @method static string getEncryptIDForSlug(int $id)
 * @method static \PiecesPHP\Core\Database\ActiveRecordModel model()
 *
 * @package     PiecesPHP\Core\Database
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
trait PreferSlugMinter
{

    /**
     * Acuña el slug permanente de una fila que no lo tiene, y lo persiste.
     *
     * El relleno es perezoso a propósito: las filas que entran por IMPORTACIÓN o por ALTA
     * DIRECTA EN BASE no pasan por el alta de la aplicación, así que nadie les pone slug. Y el
     * valor NO es derivable del id —`getEncryptIDForSlug()` mete un `uniqid()`—, de modo que hay
     * que guardarlo. Ver T61 y T64.
     *
     * ES UNA ESCRITURA, Y ATÓMICA: el `UPDATE` se condiciona a que el slug siga nulo, así que
     * dos peticiones simultáneas no acuñan dos slugs distintos. La que pierde la carrera relee
     * el que ganó, en vez de quedarse con uno que no está en la base.
     *
     * NO SE ACUÑA SI LA FILA NO TIENE NOMBRE. El nombre no interviene en el valor, pero acuñar
     * una URL permanente para una fila a medio llenar es comprometerse con algo que todavía no
     * existe. Lo hacían 12 de los 14; ahora los 14.
     *
     * Es PÚBLICA para que la tarea de relleno masivo use exactamente este camino y no una copia
     * suya: dos implementaciones del mismo acuñado serían dos verdades.
     *
     * @param self $mapper
     * @return bool Si esta llamada fue la que lo acuñó.
     */
    public static function mintPreferSlugIfMissing($mapper): bool
    {
        if ($mapper->id === null || $mapper->preferSlug !== null) {
            return false;
        }

        //El campo de nombre lo declara cada mapper en SLUG_NAME_FIELD: una sola verdad.
        $nameField = static::SLUG_NAME_FIELD;
        if ($nameField !== null && $mapper->$nameField === null) {
            return false;
        }

        $id = (int) $mapper->id;
        $slug = static::getEncryptIDForSlug($id);
        $model = static::model();
        $model->resetAll();
        $table = $model->getTable();
        $database = $model->getDatabase();

        if ($database === null) {
            return false;
        }

        //`where()` acepta cadena cruda (ActiveRecord:377-380), que es lo que hace falta para
        //un `IS NULL`: con el array asociativo solo se pueden expresar igualdades.
        $model->update(['preferSlug' => $slug])
            ->where("id = {$id} AND preferSlug IS NULL")
            ->execute();

        //`execute()` devuelve lo que devuelve PDO::execute(), que es TRUE aunque no cambie
        //ninguna fila. Así que quién ganó no lo dice el UPDATE: lo dice releer.
        $statement = $database->prepare("SELECT `preferSlug` FROM `{$table}` WHERE id = ?");
        $statement->execute([$id]);
        $stored = $statement->fetchColumn();
        $stored = $stored === false ? null : (string) $stored;

        $mapper->preferSlug = $stored;

        return $stored === $slug;
    }

}
