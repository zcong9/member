<?php

namespace Admin\Service;

class EquipmentService
{
    // yell 叫号
    // cancel 核销
    // summary 汇总叫号屏
    protected $equipment_type;

    public function __construct()
    {
        $this->equipment_type = C("equipment_type");
    }

    /**
     * @param $equipment_type
     * @param $restaurant_id
     * @return false|mixed|\PDOStatement|string|\think\Collection
     */
    public function getEquipmentInfo($equipment_type, $restaurant_id)
    {
        // 设备类型是否正确
        if (!in_array($equipment_type, $this->equipment_type)) {
            return [];
        }

        $equipment_model = D("equipment");
        $where['equipment_type'] = $equipment_type;
        $where['restaurant_id'] = $restaurant_id;
        $equipments = $equipment_model->where($where)->select();

        // 叫号屏关联取餐区
        if($equipment_type == 'yell'){
            $district_model = D("restaurant_district");
            $yell_cancel_model = D("yell_cancel");
            foreach($equipments as $key => $val){
                $where['yell_equipment_id'] = $val['equipment_id'];
                $d_rel = $district_model->where($where)->field("district_id")->find();
                $district_id = $d_rel['district_id'];
                $equipments[$key]['district'] = $district_id;

                $yell_cancel_rel = $yell_cancel_model->where($where)->find();
                if(!empty($yell_cancel_rel)){
                    $equipments[$key]['disabled'] = true;
                    continue;
                }
                $equipments[$key]['disabled'] = false;
            }
        }

        // 核销屏关联叫号屏
        if ($equipment_type == 'cancel') {
            $yell_cancel_model = D("yell_cancel");
            foreach ($equipments as $key => $val) {
                $where['cancel_equipment_id'] = $val['equipment_id'];
                $d_rel = $yell_cancel_model->where($where)->field("yell_equipment_id")->find();
                $yell_equipment_id = $d_rel['yell_equipment_id'];
                if ($yell_equipment_id) {
                    $equipments[$key]['yell_equipment_id'] = $yell_equipment_id;
                } else {
                    $equipments[$key]['yell_equipment_id'] = "";
                }

            }
        }
        return $equipments;
    }
}