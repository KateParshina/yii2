<?php

namespace app\controllers;


use app\models\Info;
use app\models\Tag;
use app\models\PersonalArea;
use app\models\Task;
use app\models\User;
use Yii;
use yii\helpers\Json;
use yii\filters\AccessControl;
use yii\rest\CreateAction;
use yii\web\Controller;
use app\models\Signup;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class UserController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // var_dump(); 
            // var_dump(Yii::$app->user->identity); die();
            return $this->redirect('personal-area');
        }

                // 
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }


    /**
     * Displays reg page.
     *
     * @return string
     */
    public function actionSignup()
    {
        $model = new Signup();
        if (isset($_POST['Signup'])) {
            $model->attributes = Yii::$app->request->post('Signup');
            if ($model->validate() && $model->signup()) {
                return $this->goHome();
            }            
        }
        return $this->render('signup', ['model' => $model]);
    }
    public function actionInfo($id)
    {
        $userInfo = User::find()->where(['user_id' => $id])->asArray()->one();
        $model = new User();
        $taskInfo = new Task();
        $countEmployer = count($taskInfo->find()->where(['employer' => $id])->all());        
        $countOwner = count($taskInfo->find()->where(['owner' => $id])->all());        
        $countInWork = count($taskInfo->find()->where(['owner' => $id, 'status' => 'InWork'])->all());        
        return $this->render('info', [
            'model' => $model,
            'userInfo' => $userInfo,
            'countEmployer' => $countEmployer,
            'countOwner' => $countOwner,
            'countInWork' => $countInWork
        ]);
    }

    public function actionPersonalArea()
    {
        $tags = new Tag;
        $AllTags = $tags->getTagsAll();

        $model = new PersonalArea();
        Yii::$app->session->open();
        if (isset(Yii::$app->user->id)) {
            $id = Yii::$app->user->id;
            $userInfo = User::findOne($id);
        } else {
            return $this->goHome();
        }
        // var_dump($AllTags);
        //  die();
        
        return $this->render('personalarea', [
            'model' => $model,
            'userInfo' => $userInfo,
            'AllTags'=> $AllTags,
        ]);
    }

    public function actionGetTasks(){
        $model = new PersonalArea();
        $tasksToDo = $model->getTasksToDo();
        echo Json::encode($tasksToDo);
    }
}
