<?php

namespace Mobile\Model;

use Think\Model;

class FoodSpecificationModel extends Model
{
    private $model = NULL;

    const TABLE_NAME = 'food_specification';

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);

        $this->model = M(self::TABLE_NAME);
    }

    /**
     * @param $food_restaurant_id
     * @return bool
     */
    public function isHaveSpecification($food_restaurant_id)
    {
        return $this->model->where(['food_restaurant_id' => $food_restaurant_id])->find() ? true : false ;
    }

    /**
     * @param $food_id
     * @return array
     */
    public function getFoodSpecificationByFoodId($food_id,$correlation_specification_id = '')
    {
        if (empty($food_id) || !is_numeric($food_id)) return [];

        $restaurant_id = (new FoodRestaurantModel())->getFoodRestaurantIdByFoodId($food_id);
        if (empty($restaurant_id)) return [];

//        $where = ' fs.food_restaurant_id IN (' . $restaurant_id . ') ';
        $where = ' fs.food_restaurant_id = '. $restaurant_id;
        if($correlation_specification_id && $correlation_specification_id !== 0){
            $where .= " OR fs.food_specification_id IN ( '$correlation_specification_id' ) ";
        }

//        $sql = " select fs.specification_img,fs.name,fs.food_specification_id,fs.select,fs.specification_desc,fst.type_img,fst.food_type_name,fst.plus_price,fst.food_type_id
//                    from  food_specification as fs
//                    LEFT JOIN food_specification_middle fsm ON fs.food_specification_id=fsm.food_specification_id
//                    LEFT JOIN food_specification_type fst ON fsm.food_type_id=fst.food_type_id
//                    WHERE  fs.food_restaurant_id = $restaurant_id OR fs.food_specification_id IN ( '$correlation_specification_id' )
//										ORDER BY fs.is_custom DESC";

//        return M()->query($sql);

        return $this->model->alias('fs')
            ->field('fs.specification_img,fs.name,fs.food_specification_id,fs.select,fs.specification_desc,fst.type_img,fst.food_type_name,fst.plus_price,fst.food_type_id')
            ->join('food_specification_middle fsm ON fs.food_specification_id=fsm.food_specification_id')
            ->join('food_specification_type fst ON fsm.food_type_id=fst.food_type_id')
            ->where($where)
            ->order('fs.is_custom DESC')
            ->select();
    }
}
