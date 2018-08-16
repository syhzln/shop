<?php
/**
 * 商城
 * $Author: 月夜青衫 2017-10-16 $
 */

namespace app\home\model;

use think\Model;
use think\Db;

/**
 * @package Home\Model
 */
class Goods extends Model
{

    public function getDiscountAttr($value, $data)
    {
        if ($data['market_price'] == 0) {
            $discount = 10;
        } else {
            $discount = round($data['shop_price'] / $data['market_price'], 2) * 10;
        }
        return $discount;
    }
}