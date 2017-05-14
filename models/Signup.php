<?php


namespace app\models;

use Yii;
use yii\base\Model;



class Signup extends Model
{
    public $username;
    public $email;
    public $password;
    public $userstatus = "user";    
    private $fuser = false;


    public function rules()
    {
        return [
            [['username','email','password'],'required'],
            ['email','email'],
            ['email','unique','targetClass'=>'app\models\User'],
            ['username','unique','targetClass'=>'app\models\User'],
            ['password','string','min'=>2,'max'=>10]
        ];
    }

    public function signup()
    {
        $user = new User();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->setPassword($this->password);
        $user->userstatus = $this->userstatus;
        $user->generateAutKey();
        return $user->save();
    }
    public function getUser()
    {
        if ($this->fuser === false) {
            $this->fuser = User::findByUsername($this->username);
        }
        return $this->fuser;
    }

}