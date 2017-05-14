<?php

namespace app\controllers;


use app\models\Search;
use Yii;
use yii\filters\AccessControl;
use yii\rest\CreateAction;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\helpers\Json;
/**
* 
*/
class ClassSearch extends Controller
{
	public function actionSearch()
	{
		$searchString = 0
		$search = new Search();
		$id = Yii::$app->user->username;
		$user_search = Notice::find()->asArray()->all();
		echo Json::encode($user_search);


	}
}