<?php

namespace Mobile\Model;

use Think\Model;

class RestaurantModel extends Model
{
    private $restaurant_id = 0;

    private $model = NULL;

    const TABLE_NAME = 'restaurant';

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);

        $this->restaurant_id = session("restaurant_id");
        $this->model = M(self::TABLE_NAME);
    }

    public function getRestaurantNameByRestaurantId($restaurant_id)
    {
        $restaurant_id = $restaurant_id ? $restaurant_id : $this->restaurant_id;

        if (empty($restaurant_id) || !is_numeric($restaurant_id)) return '';

        return $this->getRestaurantField('restaurant_name', [
            'restaurant_id' => $restaurant_id
        ]);
    }

    private function getRestaurantField($field, $condition)
    {
        return $this->model->where($condition)->getField($field);
    }

}