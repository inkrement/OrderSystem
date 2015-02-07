<?php namespace Service;

/**
 * OrderService
 *
 * @author Christian Hotz-Behofsits <chris.hotz.behofits@gmail.com>
 * @date 07.02.2015
 */

class OrderService {

    public static function getLast24Hours(\User $user){
        return \OrderQuery::create()->filterByUser($user)->
        filterByDatetime(array('min' => time() - 24 * 60 * 60));
    }

    public static function restrictedRemove(\User $user, $id){
        return \OrderQuery::create()->filterByUser($user)->findById($id)->delete();
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
        return \OrderQuery::create()->orderByDatetime('DESC')->find();
    }

    public static function getOrderPositions($order_id){
        return \OrderPositionQuery::create()->findByOrderId($order_id);
    }

    public static function getTotal($order_id){
        $order_positions = OrderService::getOrderPositions($order_id);

        $sum = 0.0;
        foreach($order_positions as $position)
            $sum += $position->getQuantity() * $position->getProduct()->getUnitPrice();

        return $sum;
    }
}