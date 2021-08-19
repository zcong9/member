<?php

namespace Common\Model;

class AuthConfigModel extends \Common\Model\BaseModel
{
    const DEL_YES = 1;
    const DEL_NO = 0;

    const PLATFORM_NEWPOS = 1;//视觉
    const PLATFORM_NEWRETAIL= 2;//新零售

    //自动完成
    protected $_auto = array(
        array('create_at', 'time', 1, 'function'),
    );
}

