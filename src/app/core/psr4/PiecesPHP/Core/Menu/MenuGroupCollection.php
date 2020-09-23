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
     *
     * @var MenuGroup[]
     */
    protected $items;

    /**
     * @var array
     */
    protected $structureOptions = [
        'items' => [
            'rules' => ['is_array'],
            'default' => [],
        ],
    ];

    /**
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

                        $this->items = [];

                        foreach ($value_on_option as $key => $value) {
                            $valid_item = $value instanceof MenuGroup;
                            if (!$valid_item || !$value->isVisible()) {
                                unset($value_on_option[$key]);
                            }
                        }

                        foreach ($value_on_option as $key => $value) {
                            $this->addItem($value);
                        }

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
     * @param MenuGroup $item
     * @return static
     */
    public function addItem(MenuGroup $item)
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @param MenuGroup $item
     * @return MenuGroup[]
     */
    public function getItems()
    {

        $defaultPositions = [];
        $nextPosition = 1;

        $items = $this->items;
        $itemsToOrder = [];

        foreach ($items as $item) {
            if ($item->getPosition() !== -1) {
                $defaultPositions[] = $item->getPosition();
            }
        }

        sort($defaultPositions);

        foreach ($items as $item) {

            $itemToOrder = clone $item;

            if ($itemToOrder->getPosition() === -1) {

                while (in_array($nextPosition, $defaultPositions)) {
                    $nextPosition++;
                }

                $itemToOrder->setPosition($nextPosition);
                $nextPosition++;

            }

            $itemsToOrder[] = $itemToOrder;
        }

        /**
         * @param MenuGroup $a
         * @param MenuGroup $b
         */
        uasort($itemsToOrder, function ($a, $b) {

            $et = 0;
            $gt = 1;
            $lt = -1;
            $result = 0;

            if ($a->getPosition() === $b->getPosition()) {
                $result = $et;
            } elseif ($a->getPosition() === -1) {
                $result = $gt;
            } elseif ($b->getPosition() === -1) {
                $result = $lt;
            } elseif ($a->getPosition() > $b->getPosition()) {
                $result = $gt;
            } else {
                $result = $lt;
            }

            return $result;

        });

        return $itemsToOrder;
    }

    /**
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
     * @return HtmlElement[]
     */
    public function getHtmlElements()
    {
        $groups = [];
        $items = $this->getItems();

        foreach ($items as $group) {
            $groups[] = $group->getHtmlElement();
        }

        return $groups;
    }

}
