<?php
 
namespace app\controllers;
 
use yii\web\Controller;
use app\models\Tag;
use app\models\Task;
use sjaakp\taggable\TagSuggestAction;
 
class TagController extends Controller  {
 
    public function actions()    {
        return [
            'suggest' => [
                'class' => TagSuggestAction::className(),
                'tagClass' => Tag::className(),
                'nameAttribute'=>'tag'
            ],
        ];
    }

    public function actionView($id){
    	$model = new Tag;
        $taskTag = Task::find()->select(['task.task_id', 'task.task_name'])->leftJoin('tag_to_task', 'tag_to_task.task_id = task.task_id')->where(['tag_to_task.tag_id' => $id])->asArray()->all();
        $tagname = Tag::findOne($id);
    	return $this->render('view', [
                'model' => $model,
                'tagname' => $tagname,
                'taskTag' => $taskTag,
            ]);
    }
}