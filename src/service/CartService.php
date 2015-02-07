<?php namespace Service;
use Slim\Slim;

/**
 * CartService
 *
 * @author Christian Hotz-Behofsits <chris.hotz.behofits@gmail.com>
 * @date 07.02.2015
 */

class CartService {
    public static function get(){
        $app = Slim::getInstance();
        $c = $app->getCookie('orderpositions');
        return ($c == null)?[]:json_decode($c, true);
    }

    public static function set(array $cart){
        $app = Slim::getInstance();
        $app->setCookie('orderpositions', json_encode($cart));
    }

    public static function add($productId, $quantity, $productName){
        $orders = CartService::get();
        array_push($orders, ['id' => $productId, 'quantity' => $quantity,
            'name' => $productName]);
        CartService::set($orders);
    }

    public static function order($user){
        $cart = CartService::get();
        if(count($cart) > 0){
            $order = new \Order();
            $order->setUser($user);
            $order->save();

            foreach($cart as $pos){
                $orderpos = new \OrderPosition();
                $orderpos->setQuantity($pos['quantity']);
                $orderpos->setProduct(\ProductQuery::create()->findById($pos['id'])->getFirst());
                $orderpos->setOrder($order);
                $orderpos->save();
            }
        }
    }

    public static function clear(){
        $app = Slim::getInstance();
        $app->setCookie('orderpositions', json_encode([]));
    }

    public static function removePosition($id){
        $orders = CartService::get();
        unset($orders[$id]);
        CartService::set($orders);
    }

}