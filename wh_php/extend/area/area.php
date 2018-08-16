<?php
/**
 * 地域编码扩展类
 * Author: Ning(nk11@qq.com)
 * Date: 2017/10/27 0027
 * Time: 下午 2:27
 */

namespace area;

use think\Cache;

class area
{
    public static $areamap;
    public static $version;

    /**
     * 加载区域编码文件
     */
    public function __construct()
    {
        if (self::$areamap===null) {
            $mapfile = include_once "map/areamap1001.php";
            self::$areamap = $mapfile['map'];
            self::$version = $mapfile['version'];
        }
    }

    /**
     * 获取省级单位
     * @return mixed
     */
    public function getProv()
    {
        $prov = Cache::get('prov');
        if (!$prov) {
            for ($i = 11;$i<85;$i++) {
                $code = $i.'0000';
                if (self::$areamap[$code]) {
                    $prov_arr[$code] = self::$areamap[$code];
                }
            }
            Cache::set('prov', $prov_arr);
            $prov = $prov_arr;
        }
        return $prov;
    }

    /**
     * 获取市级单位
     * @param $code 省级编码
     * @return mixed 省份下所有地级市单位
     */
    public function getCity($code)
    {
        $code = substr($code, 0, 2);
        $citys = Cache::get($code);
        if (!$citys) {
            for ($i = 1; $i <= 54; $i++) {
                $i<10 && $i = '0'.$i;
                $i==54&&$i==90;//针对新疆优化
                $ncode = $code.$i.'00';

                if (self::$areamap[$ncode]) {
                    $city[$ncode] = self::$areamap[$ncode];
                }
            }

            Cache::set($code, $city);
            $citys = $city;
        }
        return $citys;
    }

    /**
     * 获取区级单位
     * @param $code 市级编码
     * @return mixed 市级下所有区域
     */
    public function getRegion($code)
    {
        $code = substr($code, 0, 4);
        $regions = Cache::get($code);
        if (!$regions) {
//            for ($i = 1; $i < 60; $i++) {
            for ($i = 1; $i < 99; $i++) {
                $i<10 && $i = '0'.$i;
                $ncode = $code.$i;
                if (self::$areamap[$ncode]) {
                    $region[self::$version.$ncode] = self::$areamap[$ncode];
                }
            }
            Cache::set($code, $region);
            $regions = $region;
        }

        return $regions;
    }

    /**
     * 获取完整地址信息
     * @param $code 精准到区县级编码
     * @return mixed 省/市/县
     */
    public function getAddrstr($code)
    {
        $version = substr($code, 0, 4);
        if($version !== '1001') return '';
        $area = substr($code, 4);
        if ($version ==self::$version) {
            $address = self::$areamap[substr_replace($area, '0000', 2)].','.self::$areamap[substr_replace($area, '00', 4)].','.self::$areamap[$area];
        } else {
            $areamap = include_once "map/areamap{$version}.php";
            $address = $areamap[map][substr_replace($area, '0000', 2)].','.$areamap[map][substr_replace($area, '00', 4)].','.$areamap[map][$area];
        }
        return $address;
    }

    /**
     * getAddrcode 获取带有省市标识的地址信息
     * @param $code
     * @return string
     * Author: Ning
     */
    public function getAddrcode($code)
    {
        $version = substr($code, 0, 4);
        $area = substr($code, 4);
        if ($version ==self::$version) {
            $address[province][ocde] = substr_replace($area, '0000', 2);
            $address[province][name] = self::$areamap[substr_replace($area, '0000', 2)];
            $address[city][code] = [substr_replace($area, '00', 4)=>self::$areamap[substr_replace($area, '00', 4)]];
            $address[Region] = [$code=>self::$areamap[$area]];
        } else {
            $address="数据版本已更新,请删除重新设定!";
        }
        return $address;
    }

}
