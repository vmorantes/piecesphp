<?php

/**
 * MenuItem.php
 */
namespace PiecesPHP\Core\Menu;

use Form\Validator;
use PiecesPHP\Core\HTML\HtmlElement;

/**
 * MenuItem
 *
 * @category    HTML
 * @package     PiecesPHP\Core\Menu
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class MenuItem
{
    /**
     * @var string|null
     */
    protected $routeName;
    /**
     * @var string
     */
    protected $text;
    /**
     * @var string
     */
    protected $href;
    /**
     * @var string
     */
    protected $class;
    /**
     * @var array
     */
    protected $attributes;
    /**
     * @var boolean
     */
    protected $current;
    /**
     * @var boolean
     */
    protected $visible;
    /**
     * @var int
     */
    protected $position;

    /**
     * @var array
     */
    protected $structureOptions = [
        'text' => [
            'rules' => ['is_string'],
            'default' => 'No text...',
        ],
        'href' => [
            'rules' => ['is_string'],
            'default' => '#',
        ],
        'class' => [
            'rules' => ['is_string'],
            'default' => 'item',
        ],
        'attributes' => [
            'rules' => [
                'is_array',
            ],
            'default' => [],
        ],
        'current' => [
            'rules' => ['bool'],
            'default' => false,
        ],
        'visible' => [
            'rules' => ['bool'],
            'default' => true,
        ],
        'routeName' => [
            'rules' => ['is_string'],
            'default' => null,
        ],
        'position' => [
            'rules' => ['integer'],
            'default' => -1,
        ],
    ];

    /**
     * @param array $options
     * @param string $options['text']
     * @param string $options['href']
     * @param string $options['class']
     * @param array $options['attributes']
     * @param bool $options['current']
     * @param bool $options['visible']
     * @param string $options['routeName']
     * @param int $options['position']
     * @return static
     */
    public function __construct($options = [])
    {
        $this->structureOptions['current']['default'] = function () {
            return $this->href == get_current_url();
        };

        foreach ($this->structureOptions as $name => $config) {
            $defined_in_options = isset($options[$name]);

            if ($defined_in_options) {

                $value_on_option = $options[$name];
                $pattern_validation = [
                    $name => $config['rules'],
                ];
                $validator = new Validator($pattern_validation);
                $valid = $validator->validate([$name => $value_on_option]);

                if ($valid) {

                    if ($name == 'attributes') {
                        foreach ($value_on_option as $key => $value) {
                            $valid_attr = true;

                            if (is_string($key)) {
                                if (!is_scalar($value)) {
                                    if (is_array($value)) {
                                        foreach ($value as $jvalues) {
                                            if (!is_scalar($jvalues)) {
                                                $valid_attr = false;
                                                break;
                                            }
                                        }
                                        if ($valid_attr) {
                                            $value = implode(' ', $value);
                                        }
                                    } else {
                                        $valid_attr = false;
                                    }
                                }
                            } else {
                                $valid_attr = false;
                            }

                            if ($valid_attr) {
                                $value_on_option[$key] = (string) $value;
                            } else {
                                unset($value_on_option[$key]);
                            }
                        }
                    }

                    if ($name == 'position') {
                        $this->setPosition($value_on_option);
                    } else {
                        $this->$name = $value_on_option;
                    }

                } else {
                    $this->$name = $config['default'];
                }

            } else {
                $this->$name = $config['default'];
            }
        }

    }

    /**
     * @param integer $position
     * @return static
     */
    public function setPosition(int $position)
    {
        $this->position = $position !== -1 && $position <= 0 ? 1 : $position;
        return $this;
    }

    /**
     * @param bool $visible
     * @return static
     */
    public function setVisible(bool $visible)
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @return string|null
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        return $this->getHtmlElement()->render(false);
    }

    /**
     * @return HtmlElement
     */
    public function getHtmlElement()
    {
        $attr = $this->attributes;
        $current = $this->isCurrent();
        $class = is_array($this->class) ? $this->class : [$this->class];
        if ($current) {
            $class[] = 'current';
        }
        $class = implode(' ', $class);
        $tag = $current ? 'span' : 'a';

        $a = new HtmlElement($tag, $this->text, [], $attr);
        $a->setAttribute('class', $class);
        $a->setAttribute('href', $this->href);

        return $a;
    }

    /**
     * @return bool
     */
    public function isCurrent()
    {
        $current = false;

        if (is_callable($this->current)) {
            $current = ($this->current)() === true;
        } else {
            $current = $this->current === true;
        }

        return $current;
    }
}
