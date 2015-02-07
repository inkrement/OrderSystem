<?php
/**
 * User: chris
 * Date: 07.02.15
 * Time: 09:47
 */

namespace Service;


class ProductService {

    public static function add($name, $unitprice, $description, $img_name, $width=300){
        $new_img_name = uniqid('img-'.date('Ymd').'-');

        $image = new \Eventviva\ImageResize($img_name);
        $image->resizeToWidth($width);
        $image->save('templates/img/' . $new_img_name);

        $product = new \Product();
        $product->setImg($new_img_name);
        $product->setName($name);
        $product->setUnitPrice($unitprice);
        $product->setDescription($description);
        $product->save();
    }

    public static function remove($id){
        $product = \ProductQuery::create()->findPk($id);
        $product->setDeleteflag(true);
        $product->save();
    }

    public static function all(){
        return \ProductQuery::create()->findByDeleteflag(false);
    }

}