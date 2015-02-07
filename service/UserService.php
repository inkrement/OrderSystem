<?php namespace Service;

/**
 * UserService
 *
 * @author Christian Hotz-Behofsits <chris.hotz.behofits@gmail.com>
 * @date 07.02.2015
 */

class UserService {

    public static function remove($id){
        \UserQuery::create()->findPk($id)->delete();
    }

    public static function add($firstname, $lastname, $email, $password, $role, $phone, $plz, $city){
        $user = new \User();
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setEmail($email);
        $user->setPassword(password_hash($password, PASSWORD_DEFAULT));
        $user->setRole($role);
        $user->setPhone($phone);
        $user->setPlz($plz);
        $user->setCity($city);
        $user->save();
    }

    public static function all(){
        return \UserQuery::create()->find();
    }

}