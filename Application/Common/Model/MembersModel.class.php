<?php

namespace Common\Model;

class MembersModel extends \Common\Model\BaseModel
{
    const DEL_YES = 1;
    const DEL_NO = 0;
    const STATUS_DISABLE = 0;
    const STATUS_ENABLE = 1;

    //自动完成
    protected $_auto = array(
        array('create_at', 'time', 1, 'function'),
    );
}

