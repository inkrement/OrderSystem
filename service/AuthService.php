<?php

namespace Service;

use Slim\Slim;

class AuthService {

    public static function logout(){
        $app = Slim::getInstance();
        $app->deleteCookie('role');
        $app->deleteCookie('userid');
    }

    public static function login(\User $user){
        $app = Slim::getInstance();
        $app->setEncryptedCookie('role', $user->getRole());
        $app->setEncryptedCookie('userid', $user->getId());

        var_dump($app->getEncryptedCookie('userid'));
    }

    public static function check($email, $password){
        $app = Slim::getInstance();
        $user = \UserQuery::create()->findOneByEmail($email);

        if ($user != null && password_verify($password, $user->getPassword())) {
            AuthService::login($user);
            return true;
        }

        return false;
    }

    public static function getUser() {
        $app = Slim::getInstance();
        $id = $app->getCookie('userid');
        //FIXME: filter
        return \UserQuery::create()->find()->getFirst();
        //return \UserQuery::create()->findById($id)->getFirst();
    }
}