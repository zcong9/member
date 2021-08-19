<?php

namespace Mobile\Model;

use Think\Model;

class FoodSpecificationMiddleModel extends Model
{
    private $model = NULL;

    const TABLE_NAME = 'food_specification_middle';

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);

        $this->model = M(self::TABLE_NAME);
    }
}
