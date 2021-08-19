<?php

namespace Mobile\Model;

use Think\Model;

class FoodRestaurantModel extends Model
{
    private $model = NULL;

    const TABLE_NAME = 'food_restaurant';

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);

        $this->model = M(self::TABLE_NAME);
    }

    public function getFoodRestaurantIdByFoodId($food_id)
    {
        if (empty($food_id) || !is_numeric($food_id)) return '';

        return $this->model->field('GROUP_CONCAT(food_restaurant_id) as food_restaurant_id')->where(['food_id' => $food_id])->find()['food_restaurant_id'];
    }

}