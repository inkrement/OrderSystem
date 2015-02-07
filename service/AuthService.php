<?php namespace Service;

/**
 * AuthService
 *
 * @author Christian Hotz-Behofsits <chris.hotz.behofits@gmail.com>
 * @date 07.02.2015
 */

use Slim\Slim;

class AuthService {

    public static function logout(){
        $app = Slim::getInstance();
        $app->deleteCookie('role');
        $app->deleteCookie('userid');
    }

    public static function login(\User $user){
        $app = Slim::getInstance();
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

        $user = \UserQuery::create()->findById($id)->getFirst();

        if($user == null){
            $user = new \User();
            $user->setRole('guest');
        }
        //FIXME: filter
        return \UserQuery::create()->find()->getFirst();
        //return $user;
    }


    /**
     * converts right to number
     * @param $role
     * @return int
     */
    private static function rights($role){
        $rights = 0;

        switch($role){
            case 'admin':
                $rights += 100;
            case 'employee':
                $rights += 100;
            case 'member':
                $rights += 100;
            case 'customer':
                $rights += 100;
        }

        return $rights;
    }

    /**
     * @param $role
     * @param string $min
     * @return bool
     */
    public static function isAllowed($role, $min='member'){
        return (AuthService::rights($role) < AuthService::rights($min))? false: true;
    }


}