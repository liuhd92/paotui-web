<?php
// +----------------------------------------------------------------------
// | http://www.paotui.com/ 跑腿
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://paotui.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: liuhd<liuhd92@163.com>
// +----------------------------------------------------------------------
namespace Api\Controller;
use Think\Controller;
/**
 * 物品业务层
 * @author liuhd
 * @date 2018/11/29
 */
class GoodsController extends Controller {
    /*------------------------------------------------------ */
    //--咨询客户修改
    /*------------------------------------------------------ */
    public function goods_list(){
        /* ----------post/get参数 + 数据校验---------- */
        $type = (int)I('post.type', 0);
        if (empty($type)) json_error(10401); // 物品类型不能为空：1代购|2取送件
        
        /* ----------根据类型查询物品列表---------- */
        $Goods = D('Goods');
        $goods_info = $Goods->getInfoByType($type);
        if ($goods_info == null) {
            json_error(10402); // 暂无商品列表
        } else if ($goods_info === false){
            json_error(10107); // 数据库操作失败
        }
        
        /* ----------格式化数据---------- */
        $goods_info = self::filter_goods($goods_info, $type);
        if ($goods_info == false) json_error(10403); // 物品列表获取失败
        
        json_success($goods_info);
    }
    
    /*------------------------------------------------------ */
    //--私有方法
    /*------------------------------------------------------ */
    private function filter_goods($goods_info = array(), $type = 0){
        if (empty($goods_info) || empty($type)) return false;
        if ($type == 1) {
            $filter = array();
            $detail = array();
            foreach ($goods_info as $k=>$v){
                if ($v['pid'] == 0) {
                    $v['detail'] = array();
                    $filter[$v['id']] = $v;
                } else {
                    $filter[$v['pid']]['detail'][] = $v;
                }
            }
            return $filter;
        } else if ($type == 2){
            return $goods_info;
        }
    }
}