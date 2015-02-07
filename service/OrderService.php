<?php
/**
 * User: chris
 * Date: 07.02.15
 * Time: 09:47
 */

namespace Service;

class OrderService {

    public static function getLast24Hours(\User $user){
        return \OrderQuery::create()->filterByUser($user)->
        filterByDatetime(array('min' => time() - 24 * 60 * 60));
    }

    public static function remove($id){
        \OrderQuery::create()->findById($id)->delete();
    }

    public static function restrictedGet(\User $user, $id){
        return \OrderQuery::create()->filterByUser($user)->findById($id)->getFirst();
    }

    public static function get($id){
        return \OrderQuery::create()->findById($id)->getFirst();
    }

    public static function all(){
        return \OrderQuery::create()->find();
    }
}