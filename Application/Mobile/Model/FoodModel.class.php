<?php

namespace Mobile\Model;

use Think\Model;
use Think\Redis;

class FoodModel extends Model
{
    private $restaurant_id = 0;

    private $model = NULL;

    private $redis = NULL;

    private $empty_food = [];

    const TABLE_NAME = 'food';

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);

        $this->restaurant_id = session("restaurant_id");
        $this->model = M(self::TABLE_NAME);
        $this->redis = new Redis();
    }

    /**
     * 根据类别ID获取食物
     *
     * @param $category_id
     * @return array
     */
    public function getFoodByCategoryId($category_id)
    {
        if (is_array($category_id)) {

            if (empty($category_id)) return $this->empty_food;

            $category_id_list = implode(',', $category_id);

            return $this->model->alias('f')
                ->field('f.food_id,f.food_name,f.food_price,f.img,f.sale_num,frc.category_id,fr.food_restaurant_id')
                ->join('food_restaurant fr ON fr.food_id=f.food_id')
                ->join('food_restaurant_category frc ON frc.food_restaurant_id=fr.food_restaurant_id')
                ->where(['f.restaurant_id' => $this->restaurant_id])
                ->where(['f.restaurant_id' => $this->restaurant_id])
                ->where('frc.category_id IN (' . $category_id_list . ')')->select();

        } else {

        }

        return $this->empty_food;
    }

    public function getFoodInfoByFoodId($food_id, $field = '*')
    {
        if (empty($food_id) || !is_numeric($food_id)) return $this->empty_food;

        return $this->model->field($field)->where(['food_id' => $food_id])->find();
    }

    public function updateFoodInventory($food_id, $restaurant_id, $update_number)
    {
        if (empty($food_id) || !is_numeric($food_id)) return false;
        if (empty($restaurant_id) || !is_numeric($restaurant_id)) return false;

        //获取今天此店铺中此菜品在redis中的key值，格式: Inventory_年月日_菜品ID_店铺ID。例：Inventory_20191126_1_266
        $food_today_inventory_key = $this->getFoodTodayInventoryKey($food_id, $restaurant_id);

        //如果key不存在,exists函数返回int(0),此时查询数据库数据同步到redis中。
        if ($this->redis->exists($food_today_inventory_key) === 0) {
            //redis中还没有此店铺中此菜品的库存
            $result = $this->syncDatabaseInventoryToRedis($food_today_inventory_key, $food_id);
            if ($result === false) return false;
        }

        //此处redis中已有此店铺中此菜品的库存
        $lua = "
                -- 如果键值存在，获取redis中的库存值，当前库存与变化的值相加，如果大于等于0，执行更新操作，否则返回false
                if redis.call('exists', KEYS[1]) == 1 then
                    local sale_num = redis.call('get', KEYS[1])
                    
                    sale_num = sale_num + ARGV[1];
                    if sale_num >= 0 then
                        return redis.call('set', KEYS[1], sale_num);
                    else
                        return false;
                else
                    return false
            ";

        return $this->redis->executeEval($lua, [$food_today_inventory_key, $update_number], 1);
    }

    public function syncDatabaseInventoryToRedis($food_today_inventory_key, $food_id)
    {
        $sale_num = $this->getFoodSaleNumByFoodId($food_id);

        $lua = "
                -- 如果键值不存在，设置键值为查询出来的库存值，否则返回exists
                if redis.call('exists', KEYS[1]) == 0 then
                    return redis.call('set', KEYS[1], ARGV[1], ARGV[2])
                else
                    return 'exists'
            ";

        return $this->redis->executeEval($lua, [$food_today_inventory_key, 'expire_time', $sale_num, 86400], 2);
    }

    public function getFoodSaleNumByFoodId($food_id)
    {
        return $this->model->where(['food_id' => $food_id])->getField('sale_num');
    }

    private function getFoodTodayInventoryKey($food_id, $restaurant_id)
    {
        return 'Inventory_' . date('Ymd') . '_' . $food_id . '_' . $restaurant_id;
    }
}