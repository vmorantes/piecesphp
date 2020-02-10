<?php

/**
 * MenuGroupCollection.php
 */
namespace PiecesPHP\Core\Menu;

use Form\Validator;
use PiecesPHP\Core\HTML\HtmlElement;

/**
 * MenuGroupCollection
 *
 * @category    HTML
 * @package     PiecesPHP\Core\Menu
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class MenuGroupCollection
{
    /**
     * $items
     *
     * @var MenuGroup[]
     */
    protected $items;

    /**
     * $structureOptions
     *
     * @var array
     */
    protected $structureOptions = [
        'items' => [
            'rules' => ['is_array'],
            'default' => [],
        ],
    ];

    /**
     * __construct
     *
     * @param mixed $options
     * @return static
     */
    public function __construct($options = [])
    {
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

                    if ($name == 'items') {
                        foreach ($value_on_option as $key => $value) {
                            $valid_item = $value instanceof MenuGroup;
                            if (!$valid_item) {
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
     * addItem
     *
     * @param MenuGroup $item
     * @return void
     */
    public function addItem(MenuGroup $item)
    {
        $this->items[] = $item;
    }

    /**
     * getItems
     *
     * @param MenuGroup $item
     * @return MenuGroup[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * getHtml
     *
     * @return string
     */
    public function getHtml()
    {
        $elements = $this->getHtmlElements();
        $html = '';
        foreach ($elements as $element) {
            $html .= !is_null($element) ? $element->render(false) . "\n" : '';
        }
        return $html;
    }

    /**
     * getHtmlElements
     *
     * @return HtmlElement[]
     */
    public function getHtmlElements()
    {
        $groups = [];

        foreach ($this->items as $group) {
            $groups[] = $group->getHtmlElement();
        }

        return $groups;
    }

}
