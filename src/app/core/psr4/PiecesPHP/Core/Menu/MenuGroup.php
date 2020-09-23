<?php

/**
 * MenuGroup.php
 */
namespace PiecesPHP\Core\Menu;

use Form\Validator;
use PiecesPHP\Core\HTML\HtmlElement;

/**
 * MenuGroup
 *
 * @category    HTML
 * @package     PiecesPHP\Core\Menu
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class MenuGroup
{
    /**
     * @var string|null
     */
    protected $routeName;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $icon;
    /**
     * @var boolean
     */
    protected $current;
    /**
     * @var boolean
     */
    protected $visible;
    /**
     * @var boolean
     */
    protected $asLink;
    /**
     * @var string
     */
    protected $href;
    /**
     * @var array
     */
    protected $attributes;
    /**
     * @var MenuItem[]
     */
    protected $items;
    /**
     * @var MenuGroup[]
     */
    protected $groups;
    /**
     * @var int
     */
    protected $position;

    /**
     * @var array
     */
    protected $structureOptions = [
        'name' => [
            'rules' => ['is_string'],
            'default' => 'Group...',
        ],
        'icon' => [
            'rules' => ['is_string'],
            'default' => 'bullseye',
        ],
        'current' => [
            'rules' => ['bool'],
            'default' => null,
        ],
        'visible' => [
            'rules' => ['bool'],
            'default' => true,
        ],
        'asLink' => [
            'rules' => ['bool'],
            'default' => false,
        ],
        'href' => [
            'rules' => ['is_string'],
            'default' => '',
        ],
        'attributes' => [
            'rules' => [
                'is_array',
            ],
            'default' => [],
        ],
        'items' => [
            'rules' => ['is_array'],
            'default' => [],
        ],
        'groups' => [
            'rules' => ['is_array'],
            'default' => [],
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
     * @param string $options['name']
     * @param string $options['icon']
     * @param bool $options['current']
     * @param bool $options['visible']
     * @param bool $options['asLink']
     * @param string $options['href']
     * @param array $options['attributes']
     * @param MenuItem[] $options['items']
     * @param MenuGroup[] $options['groups']
     * @param int $options['position']
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

                    if ($name == 'items') {

                        $this->items = [];

                        foreach ($value_on_option as $key => $value) {
                            $valid_item = $this->validateItem($value);
                            if (!$valid_item || !$value->isVisible()) {
                                unset($value_on_option[$key]);
                            }
                        }

                        foreach ($value_on_option as $key => $value) {
                            $this->addItem($value);
                        }

                    }

                    if ($name == 'groups') {
                        foreach ($value_on_option as $key => $value) {
                            $valid_item = $this->validateGroup($value);
                            if (!$valid_item) {
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
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return bool
     */
    public function asLink()
    {
        return $this->asLink;
    }

    /**
     * @return MenuItem[]
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
         * @param MenuItem $a
         * @param MenuItem $b
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
     * @return MenuGroup[]
     */
    public function getGroups()
    {
        return $this->groups;
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
    public function getHtml()
    {
        $html = $this->getHtmlElement();
        return !is_null($html) ? $html->render(false) : '';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return HtmlElement|null
     */
    public function getHtmlElement()
    {
        $group_name = $this->name;
        $group_as_link = $this->asLink;
        $group_href = !is_null($this->href) ? $this->href : '';
        $group_icon = $this->icon;
        $group_visible = $this->visible;
        $group_attributes = $this->attributes;
        $group_current = $this->isCurrent();
        $group_items = $this->getItems();
        $group_groups = $this->groups;

        if ($group_visible) {

            if ($group_current) {
                $group_attributes['current'] = '';
            }

            $group_container = new HtmlElement('ul', '', null, $group_attributes);
            $group_container->setAttribute('class', 'group');

            $group_name = "<i class='icon $group_icon'></i><span>$group_name</span>";

            if (!$group_as_link || $group_current) {

                $group_title_container = new HtmlElement('div', $group_name);

                if ($group_current) {
                    if ($group_as_link) {
                        $group_title_container->setAttribute('class', 'title-group current as-link');
                    } else {
                        $group_title_container->setAttribute('class', 'title-group current');
                    }
                } else {
                    $group_title_container->setAttribute('class', 'title-group');
                }

            } else {

                $group_title_container = new HtmlElement('a', $group_name);
                $group_title_container->setAttribute('class', 'title-group');
                $group_title_container->setAttribute('href', $group_href);

            }

            $group_container->appendChild($group_title_container);

            if (!$group_as_link) {

                $group_items_container = new HtmlElement('div');
                $group_items_container->setAttribute('class', 'items');

                if ($group_items !== false) {
                    foreach ($group_items as $item) {

                        $li = new \PiecesPHP\Core\HTML\HtmlElement('li');

                        $li->appendChild($item->getHtmlElement());
                        $group_items_container->appendChild($li);
                    }
                }

                if ($group_groups !== false) {
                    foreach ($group_groups as $group) {

                        $group_items_container->appendChild($group->getHtmlElement());
                    }
                }

                $group_container->appendChild($group_items_container);
            }

            return $group_container;

        } else {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function isCurrent()
    {
        if (is_null($this->current)) {
            $current_url = get_current_url();
            $href = $this->href;

            while (last_char($current_url) == '/') {
                $current_url = remove_last_char($current_url);
            }
            while (last_char($href) == '/') {
                $href = remove_last_char($href);
            }

            if ($href != $current_url) {
                $items = $this->getItems();
                foreach ($items as $item) {
                    if ($item->isCurrent()) {
                        $this->current = true;
                        break;
                    }
                }
                foreach ($this->groups as $group) {
                    if ($group->isCurrent()) {
                        $this->current = true;
                        break;
                    }
                }
                if ($this->current !== true) {
                    $this->current = false;
                }
            } else {
                $this->current = true;
            }
        }
        return $this->current;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @param MenuItem $item
     * @return static
     */
    public function addItem(MenuItem $item)
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @param MenuGroup $group
     * @return static
     */
    public function addGroup(MenuGroup $group)
    {
        $this->groups[] = $group;
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
     * @param mixed $value
     * @return bool
     */
    protected function validateItem($value)
    {
        return $value instanceof MenuItem;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function validateGroup($value)
    {
        return $value instanceof MenuGroup;
    }

}
