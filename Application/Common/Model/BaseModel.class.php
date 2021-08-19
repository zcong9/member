<?php

namespace Common\Model;

use Think\Model;

class BaseModel extends Model
{
    const LIMIT = 10;

    protected $pk = 'id';
    protected $businessId = 0;
    protected $restaurantId = 0;

    public function setBusinessId($businessId)
    {
        $this->businessId = $businessId;
        return $this;
    }

    public function setRestaurantId($restaurantId)
    {
        $this->restaurantId = $restaurantId;
        return $this;
    }

    public function getItemByCondition($condition, $field = '*')
    {
        return $this->field($field)->where($condition)->find();
    }

    public function getListByCondition($condition, $field = "*", $order = '')
    {
        return $this->field($field)->where($condition)->order($order)->select();
    }

    /**
     * 添加或编辑
     * @param $data
     * @param string $error
     * @param bool $is_sql
     * @return int|mixed
     */
    public function edit($data, &$error = '', $is_sql = false)
    {
        $id = (int)$data[$this->getPk()];
        //格式化表数据
        $this->formatData($data, $id);
        //数据表验证
        $data = $this->create($data, $id ? 2 : 1);
        if (!$data) {
            $error = $this->getError();
            return 0;
        }
        //数据入库处理
        if ($id) {
            //修改数据
            $result = $this->where([$this->getPk() => $id])->save($data);
            $rowId = $id;
            if ($is_sql) echo $this->_sql();
        } else {
            //新增数据
            $result = $this->add($data);
            $rowId = $result;
            if ($is_sql) echo $this->_sql();
        }

        if ($result !== false) {
            //重置缓存

        }
        return $rowId;
    }


    /**
     * 格式化编辑的数据
     * @param $data
     * @param int $id
     * @param string $table
     * @return array
     */
    public function formatData(&$data, $id = 0, $table = "")
    {
        $dataList = array();
        $tables = $table ? explode(",", $table) : array($this->getTableName());
        $newData = array();

        foreach ($tables as $table) {
            $tempData = array();
            $fieldInfoList = $this->getFieldInfoList($table);
            foreach ($fieldInfoList as $field => $fieldInfo) {
                if ($field == $this->getPk()) continue;

                //对强制
                if (isset($data[$field])) {
                    if ($fieldInfo['type'] == "int") {
                        $newData[$field] = (int)$data[$field];
                    } else {
                        $newData[$field] = (string)$data[$field];
                    }
                }

                //插入数据-设置默认值
                if (!$id && !isset($data[$field])) {

                    //自动时间戳
                    if (in_array($field, array('create_at'))) {
                        $newData[$field] = time();
                    } elseif (in_array($field, array('business_id'))) {
                        $newData[$field] = $this->businessId ?: $fieldInfo['default'];
                    } elseif (in_array($field, array('restaurant_id'))) {
                        $newData[$field] = $this->restaurantId ?: $fieldInfo['default'];
                    } else {
                        $newData[$field] = $fieldInfo['default'];
                    }

                }

                //更新数据
                if ($id && !isset($data[$field])) {
                    //自动时间戳
                    if (in_array($field, array('update_at'))) {
                        $newData[$field] = time();
                    }
                }

                if (isset($newData[$field])) {
                    $tempData[$field] = $newData[$field];
                }

            }
            $dataList[] = $tempData;
        }
        $data = $newData;
        return $dataList;
    }


    /**
     * 获取字段信息列表
     * @param string $table
     * @return array
     */
    public function getFieldInfoList($table = "")
    {
        $table = $table ? $table : $this->getTableName();
        $fieldList = $this->query("SHOW FIELDS FROM {$table}");
        $infoList = array();
        foreach ($fieldList as $row) {
            if ((strpos($row['Type'], "int") === false) || (strpos($row['Type'], "bigint") !== false)) {
                $type = "string";
                $default = $row['Default'] ? $row['Default'] : "";
            } else {
                $type = "int";
                $default = $row['Default'] ? $row['Default'] : 0;
            }
            $infoList[$row['Field']] = array(
                'type' => $type,
                'default' => $default
            );
        }
        return $infoList;
    }

    /**
     * 读取数据条数
     * @param array $where
     * @return int
     */
    public function getCount(array $where = [])
    {
        return $this->where($where)->count();
    }

    /**
     * 物理删除|真删除
     * @param array|mixed $id
     * @param null $key
     * @return bool|mixed
     */
    public function del($id, $key = null)
    {
        if (is_array($id)) {
            if (count(array_filter(array_keys($id), 'is_string')) > 0) {
                $where = $id;
            } else {
                $where = [is_null($key) ? $this->getPk() : $key => ['in', $id]];
            }
        } else {
            $where = [is_null($key) ? $this->getPk() : $key => $id];
        }

        $objClass = new \ReflectionClass($this);
        if ($objClass->hasConstant('DEL_YES')) {
            return $result = $this->where($where)->setField('is_del', $objClass->getConstant('DEL_YES'));
        } else {
            return $this->where($where)->delete();
        }
    }

    /**
     * 获取一条数据
     * @param int|array $id
     * @param array|null $field
     * @return array|null
     */
    public function get($id, array $field = [])
    {
        if (is_array($id)) {
            $where = $id;
        } else {
            $where = [$this->getPk() => $id];
        }

        if (!isset($where['is_del'])) {
            $objClass = new \ReflectionClass($this);
            if ($objClass->hasConstant('DEL_NO')) {
                $where['is_del'] = $objClass->getConstant('DEL_NO');
            }
        }

        return $this->field($field ?: ['*'])->where($where)->find();
    }

    /**
     * 更新数据
     * @param int|string|array $id
     * @param array $data
     * @param null $key
     * @return bool
     */
    public function doUpdate($id, array $data, $key = null)
    {
        if (is_array($id)) {
            if (count(array_filter(array_keys($id), 'is_string')) > 0) {
                $where = $id;
            } else {
                $where = [is_null($key) ? $this->getPk() : $key => array('in', $id)];
            }
        } else {
            $where = [is_null($key) ? $this->getPk() : $key => $id];
        }
        return $this->where($where)->save($data);
    }


    /**
     * 得到某个列的数组
     * @param array $where
     * @param $field    字段名 多个字段用逗号分隔
     * @param null $spea 字段数据间隔符号 NULL返回数组
     * @return mixed
     */
    public function getColumn($where = array(), $field, $spea = null)
    {
        return $this->where($where)->getField($field, $spea);
    }

    /**
     * 批量更新数据
     * @param array $ids
     * @param array $data
     * @param null $key
     * @return bool
     */
    public function batchUpdate(array $ids, array $data, $key = null)
    {
        return $this->where(array(is_null($key) ? $this->getPk() : $key => array('IN', $ids)))->save($data);
    }
}

