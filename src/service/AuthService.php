<?php namespace Service;

/**
 * AuthService
 *
 * @author Christian Hotz-Behofsits <chris.hotz.behofits@gmail.com>
 * @date 07.02.2015
 */

use Slim\Slim;

class AuthService {

    /**
     * initialize Auth
     *
     * looks for cookies and setup some
     * auth specific $app attributes
     */
    public static function start(){
        $app = Slim::getInstance();
        $app->userid = $app->getCookie('userid');
    }

    /**
     * logout
     *
     * removes cookies and $app specific attributes
     */
    public static function logout(){
        $app = Slim::getInstance();
        $app->deleteCookie('userid');
        unset($app->userid);
    }

    /**
     * login
     *
     * logs the provided user in. creates specific cookies
     * and $app attributes
     * @param \User $user user object to login
     */
    public static function login(\User $user){
        $app = Slim::getInstance();
        $app->setCookie('userid', $user->getId());
        $app->userid=$user->getId();
    }

    /**
     * check credentials
     *
     * checks user credentials, where email is the username and
     * password the keyword.
     *
     * @param $email
     * @param $password
     * @return bool result of the query
     */
    public static function check($email, $password){
        $user = \UserQuery::create()->findOneByEmail($email);

        if ($user != null && password_verify($password, $user->getPassword())) {
            AuthService::login($user);
            return true;
        }

        return false;
    }

    /**
     * user
     * get the logged in user or a new dummy 'guest user'
     * @return mixed|\User
     */
    public static function getUser() {
        $app = Slim::getInstance();
        $id = $app->userid;

        $user = \UserQuery::create()->findById($id)->getFirst();

        if($user == null){
            $user = new \User();
            $user->setRole('guest');
        }

        return $user;
    }


    /**
     * converts right to number to compare rights
     *
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
     * authorisation check
     *
     * @param $role
     * @param string $min
     * @return bool
     */
    public static function isAllowed($role, $min='member'){
        return (AuthService::rights($role) < AuthService::rights($min))? false: true;
    }


}