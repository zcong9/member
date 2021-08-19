<?php

namespace Mobile\Model;

use Think\Model;

class FoodSpecificationTypeModel extends Model
{
    private $model = NULL;

    const TABLE_NAME = 'food_specification_type';

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);

        $this->model = M(self::TABLE_NAME);
    }

    /**
     * 获取菜品规格
     *
     * @param $food_type_id
     * @param string $field
     * @return array|mixed
     */
    public function getFoodSpecificationTypeInfoByFoodTypeId($food_type_id, $field = '*')
    {
        if (empty($food_type_id) || !is_numeric($food_type_id)) return [];

        return $this->model->field($field)->where(['food_type_id' => $food_type_id])->find();
    }

    /**
     * 根据菜品规格ID列表获取菜品规格总金额
     *
     * @param $food_type_id_list
     * @return float|mixed
     */
    public function getFoodSpecificationTypeTotalPriceByFoodTypeIdList($food_type_id_list)
    {
        if (is_array($food_type_id_list)) {
            $food_type_id_list = rtrim(implode(',', $food_type_id_list), ',');

            if ($food_type_id_list) {
                return $this->model->field('sum(plus_price) as total_price')->where('food_type_id IN (' . $food_type_id_list . ')')->find()['total_price'];
            }

            return 0.00;
        }

        return 0.00;
    }
}
