<?php

namespace Iot\Request\V20180120;

/**
 * Request of BatchGetDeviceState
 *
 * @method array getIotIds()
 * @method string getIotInstanceId()
 * @method array getDeviceNames()
 * @method string getProductKey()
 */
class BatchGetDeviceStateRequest extends \RpcAcsRequest
{

    /**
     * @var string
     */
    protected $method = 'POST';

    /**
     * Class constructor.
     */
    public function __construct()
    {
        parent::__construct(
            'Iot',
            '2018-01-20',
            'BatchGetDeviceState',
            'iot'
        );
    }

    /**
     * @param array $iotIds
     *
     * @return $this
     */
    public function setIotIds(array $iotIds)
    {
        $this->requestParameters['IotIds'] = $iotIds;
        foreach ($iotIds as $i => $iValue) {
            $this->queryParameters['IotId.' . ($i + 1)] = $iValue;
        }

        return $this;
    }

    /**
     * @param string $iotInstanceId
     *
     * @return $this
     */
    public function setIotInstanceId($iotInstanceId)
    {
        $this->requestParameters['IotInstanceId'] = $iotInstanceId;
        $this->queryParameters['IotInstanceId'] = $iotInstanceId;

        return $this;
    }

    /**
     * @param array $deviceNames
     *
     * @return $this
     */
    public function setDeviceNames(array $deviceNames)
    {
        $this->requestParameters['DeviceNames'] = $deviceNames;
        foreach ($deviceNames as $i => $iValue) {
            $this->queryParameters['DeviceName.' . ($i + 1)] = $iValue;
        }

        return $this;
    }

    /**
     * @param string $productKey
     *
     * @return $this
     */
    public function setProductKey($productKey)
    {
        $this->requestParameters['ProductKey'] = $productKey;
        $this->queryParameters['ProductKey'] = $productKey;

        return $this;
    }
}
