<?php

/**
 * DependenciesInjectorPiecesPHP.php
 */
namespace PiecesPHP\Core\Routing;

use Psr\Container\ContainerInterface;

/**
 * DependenciesInjectorPiecesPHP
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class DependenciesInjectorPiecesPHP implements ContainerInterface
{

    /**
     * @var array<string,mixed>
     */
    protected $data = [];

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {

        foreach ($options as $key => $value) {
            if (is_scalar($key)) {
                $this->add($key, $value);
            }
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return DependenciesInjectorPiecesPHP
     */
    public function add(string $name, $value)
    {
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get(string $name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        } else {
            return null;
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->data[$name]);
    }

    /**
     * @param string $name
     * @return DependenciesInjectorPiecesPHP
     */
    public function remove(string $name)
    {
        $this->data[$name] = null;
        return $this;
    }

}
