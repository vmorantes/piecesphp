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
     * $routeName
     *
     * @var string|null
     */
    protected $routeName;
    /**
     * $text
     *
     * @var string
     */
    protected $text;
    /**
     * $href
     *
     * @var string
     */
    protected $href;
    /**
     * $class
     *
     * @var string
     */
    protected $class;
    /**
     * $attributes
     *
     * @var array
     */
    protected $attributes;
    /**
     * $current
     *
     * @var boolean
     */
    protected $current;
    /**
     * $visible
     *
     * @var boolean
     */
    protected $visible;
    /**
     * $structureOptions
     *
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
    ];

    /**
     * __construct
     *
     * @param array $options
     * @param string $options['text']
     * @param string $options['href']
     * @param string $options['class']
     * @param array $options['attributes']
     * @param bool $options['current']
     * @param bool $options['visible']
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

                    $this->$name = $value_on_option;
                } else {
                    $this->$name = $config['default'];
                }

            } else {
                $this->$name = $config['default'];
            }
        }

    }

    /**
     * setVisible
     *
     * @param bool $visible
     * @return void
     */
    public function setVisible(bool $visible)
    {
        $this->visible = $visible;
    }

    /**
     * getRouteName
     *
     * @return string|null
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * getHtml
     *
     * @return string
     */
    public function getHtml()
    {
        return $this->getHtmlElement()->render(false);
    }

    /**
     * getHtmlElement
     *
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
     * isCurrent
     *
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
