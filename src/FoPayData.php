<?php

namespace Douyasi\Baofoo;

use Douyasi\Baofoo\BaofooException;


/**
 * Class FoPaySdk 宝付代付数据对象类
 * 
 * @author raoyc <raoyc2009@gmaill.com>
 * @link   https://raoyc.com
 * @date   2017-09-22
 */
class FoPayData
{

    /**
     * 请求数据数组
     *
     * @var array 多维数组
     */
    private $_data = [];


    /**
     * 获取最终组装之后的数据
     *
     * @param  bool $usingJson 使用返回 json 数据格式
     * @param  null|array $trans_head 代付交易拆分接口头部信息，其他接口请保持 null
     * @return array|string
     */
    public function get($usingJson = false, $trans_head = null)
    {
        $trans_content = [
            'trans_content' => [
                'trans_reqDatas' => [
                    // 循环域 数组
                    'trans_reqData' => [],
                ],
            ],
        ];

        if (!empty($this->_data) && is_array($this->_data)) {
            if (!empty($trans_head) && is_array($trans_head) && array_key_exists('trans_count', $trans_head) && array_key_exists('trans_totalMoney', $trans_head)) {
                // 重新计算笔数与总金额
                $trans_head['trans_count'] = count($this->_data);
                $trans_head['trans_totalMoney'] = array_sum(array_column($this->_data, 'trans_money'));
                $trans_content['trans_content']['trans_head'] = $trans_head;
            }
            $trans_content['trans_content']['trans_reqDatas']['trans_reqData'] = $this->_data;
            if ($usingJson) {
                $data_content = json_encode($trans_content, JSON_UNESCAPED_UNICODE);
                $data_content = str_replace('\\\"', '"', $data_content);
                return $data_content;
            }
            return $trans_content;
        } else {
            throw new BaofooException(null, BaofooException::BAOFOO_FOPAY_DATA_ILLEGAL);
        }
    }

    /**
     * fillData 填充请求数据
     * 
     * @param array $data 循环域 数组，不含 'trans_reqData' 键名
     * @return array
     */
    public function fillData($data)
    {
        array_push($this->_data, $data);
        return $this;
    }

}