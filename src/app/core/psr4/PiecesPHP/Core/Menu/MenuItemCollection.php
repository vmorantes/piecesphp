<?php

/**
 * MenuItemCollection.php
 */
namespace PiecesPHP\Core\Menu;

use PiecesPHP\Core\HTML\HtmlElement;

/**
 * MenuItemCollection
 *
 * @category    HTML
 * @package     PiecesPHP\Core\Menu
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class MenuItemCollection
{
    /**
     * @var MenuItem[]
     */
    protected $items;

    /**
     * @var array
     */
    protected $structureOptions = [
        'items' => [
            'rule' => 'is_array',
            'default' => [],
        ],
    ];

    /**
     * @param mixed[] $options
     * @return static
     */
    public function __construct(array $options = [])
    {
        foreach ($this->structureOptions as $name => $config) {

            $defined_in_options = isset($options[$name]);

            if ($defined_in_options) {

                $value_on_option = $options[$name];
                $valid = Validator::validate($config['rule'], $value_on_option);

                if ($valid) {

                    $value_on_option = Validator::parse($config['rule'], $value_on_option);
                    if ($name == 'items') {
                        foreach ($value_on_option as $key => $value) {
                            $valid_item = $value instanceof MenuItem;
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

    public function addItem(MenuItem $item)
    {
        $this->items[] = $item;
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        $elements = $this->getHtmlElements();
        $html = '';
        foreach ($elements as $element) {
            $html .= $element->render(false) . "\n";
        }
        return $html;
    }

    /**
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
