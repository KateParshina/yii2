<?php

namespace app\controllers;


use app\models\Notice;
use Yii;
use yii\filters\AccessControl;
use yii\rest\CreateAction;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\helpers\Json;

/**
* 
*/
class NoticeController extends Controller
{
	public function actionNotice(){
		$notice = new Notice();
		$id = Yii::$app->user->id;
		$user_notice = Notice::find()->where(['user_id' => $id,'read_n' => Null])->asArray()->all();
		echo Json::encode($user_notice);		
	}
}