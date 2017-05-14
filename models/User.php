<?php

namespace app\models;
use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord  implements IdentityInterface{
    public $id;
    public $authKey;
    public $accessToken;

    public static function getUserId($username){
        $employer = User::find()->select('user_id')->where(['username' => $username])->asArray()->one();
        $res['empl_id'] = $employer['user_id'];
        Yii::$app->session->open();
        $res['owner_id'] = Yii::$app->user->id;
        return $res;
    }

    public static function getUserName($id){
        $user = User::find()->select('username')->where(['user_id' => $id])->asArray()->one();
        return $user['username'];
    }

    public static function identityUser(){
        if (!Yii::$app->user->id) {
            $this->redirect('/site/index');
        }
    }


    /**
     * SetPassword password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function setPassword($password){
        return $this->password = md5($password) . strlen($password);
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        // var_dump($this->password);
        // var_dump(md5($password) . strlen($password));
        // die();
        return $this->password === md5($password) . strlen($password);
    }

        /**
     * Finds user by usermail
     *
     * @param string $usermail
     * @return static|null
     */
    public static function findByUsermail($email)
    {
        return static::findOne(['email'=>$email]);
    }

    public static function findByUsername($username)
    {
        return static::findOne(['username'=>$username]);
    }

/**======================================================= */
    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }
    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->user_id;

    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }


    public function generateAutKey(){
        return $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        foreach (self::$users as $user) {
            if ($user['accessToken'] === $token) {
                return new static($user);
            }
        }

        return null;
    }
}


