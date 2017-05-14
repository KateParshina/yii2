<?php

namespace app\controllers;

use app\models\Neo;
use app\models\Resource;
use Yii;
use yii\helpers\Json;
use app\models\Task;
use app\models\User;
use app\models\Timetracking;
use app\models\Dependence;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\Notice;
use yii\web\UploadedFile;

/**
 * TaskController implements the CRUD actions for Task model.
 */
class TaskController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Task models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Task::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }


    public function actionTask($id)
    {               
        $taskmodel = $this->findModel($id);
        $owner = User::getUserName($taskmodel->owner);        
        $employer = User::getUserName($taskmodel->employer);
        $lastactivity = Timetracking::lastActivity($id);
        $worktime = Timetracking::WorkTime($id);

        if ($taskmodel->employer == 0) {
            $this->redirect('/task/update' . "/" . $id);
        }

        if ($taskmodel->status == "Finish") {
            $finNoti = Notice::find()->where(['task_id' => $id, 'user_id' => Yii::$app->user->id, 'read_n' => NULL])->asArray()->one();
            if ($finNoti) {
            $this->updateNotice($id);
            }
        } elseif ($taskmodel->status == "InWork" && $taskmodel->owner == Yii::$app->user->id) {
            $finNoti = Notice::find()->where(['task_id' => $id, 'user_id' => Yii::$app->user->id, 'read_n' => NULL])->asArray()->one();
            if ($finNoti) {
            $this->updateNotice($id);
            }
        }

        if (isset($_POST['accept'])) { 
            if ($taskmodel->status == "WaitSubmit") {
                $taskmodel->status = "InWork";
                $taskmodel->employer = "$taskmodel->employer";
                $taskmodel->update();
                $this->updateNotice($id);
                $this->createNotice($id, $taskmodel, "owner" ," was accepted!");
            } elseif ($taskmodel->status == "WaitConfirm") {
                $taskmodel->status = "Finish";
                $taskmodel->employer = "$taskmodel->employer";
                $taskmodel->update();
                $this->updateNotice($id);
                $this->createNotice($id, $taskmodel, "employer" ," was accepted!");
                $del_dep = Dependence::find()->where(['dep_task_id' => $id ])->one(); // drop where dep_task_id = $id
                if ($del_dep) {
                    $del_dep->delete();
                }
                
            }
        } elseif (isset($_POST['discard'])) {
            if ($taskmodel->status == "WaitSubmit") {
                $taskmodel->status = "NotAccepted";
                $taskmodel->employer = "0";
                $taskmodel->update();
                $this->updateNotice($id);
                $this->createNotice($id, $taskmodel, "owner" ," was not accepted!");
            } elseif ($taskmodel->status == "WaitConfirm") {
                $taskmodel->status = "InWork";
                $taskmodel->employer = "$taskmodel->employer";
                $taskmodel->update();
                $this->updateNotice($id);
                $this->createNotice($id, $taskmodel, "employer" ," was not accepted!");
            }
        } elseif (isset($_POST['ok'])) {
            $noti = Notice::find()->where(['task_id' => $id, 'user_id' => Yii::$app->user->id, 'read_n' => NULL])->asArray()->one();
            if ($noti) {
            $this->updateNotice($id);
            }
        }

        $denendence = Dependence::find()->where(['task_id' => $id])->asArray()->all();
        if (isset($denendence[0]['task_id'])) {
            $denendence = 1;
        } else  { 
            $denendence = 0; 
        }

        if (isset(Yii::$app->user->id)) {
            $notice = Notice::find()->where(['user_id' => Yii::$app->user->id, 'task_id' => $id, 'read_n' => NULL])->one();
            if ($taskmodel->status != "Finish"){
                if ($notice) {
                    if ($taskmodel->status == "WaitConfirm" && $taskmodel->owner == Yii::$app->user->id) {
                        $control = 'submit';
                    } elseif ($taskmodel->status == "WaitConfirm" && $taskmodel->employer == Yii::$app->user->id) {
                        $control = "fullcontrol";                        
                    } elseif ($taskmodel->status == "WaitSubmit" && $taskmodel->employer == Yii::$app->user->id) {
                        $control = 'submit';
                    } elseif ($taskmodel->status == "InWork" && $taskmodel->employer == Yii::$app->user->id || $taskmodel->status == "InWork" && $taskmodel->owner == Yii::$app->user->id) {
                        $control = "fullcontrol";
                    } else { 
                    $control = false;
                    }        
                } elseif ($taskmodel->status == "InWork" && $taskmodel->employer == Yii::$app->user->id || $taskmodel->status == "InWork" && $taskmodel->owner == Yii::$app->user->id){ 
                    $control = "fullcontrol";
                } elseif ($taskmodel->status == "WaitSubmit" && $taskmodel->owner == Yii::$app->user->id) { 
                    $control = "fullcontrol";
                } elseif ($taskmodel->status == "WaitConfirm" && $taskmodel->owner == Yii::$app->user->id) {
                        $control = 'submit';
                } else { 
                    $control = false;
                } 
            } else { 
                $control = "finish";
            }
    
            if ($taskmodel->employer == Yii::$app->user->id) {
                    $controlEmployer = 1; 
            } else { 
                $controlEmployer = 0; 
            }             
        } else {
            $control = false;
        }


        return $this->render('task', [
            'taskmodel' => $taskmodel,
            'owner' => $owner,
            'employer' => $employer,
            'lastactivity' => $lastactivity,
            'worktime' => $worktime,
            'id' => $id,
            'denendence' => $denendence,
            'control' => $control,
            'controlEmployer' => $controlEmployer
        ]);
    }

    public function actionMyTasks()
    {
        $model = new Task();
        $tasks = $this->actionGetTasks();     
        return $this->render('mytasks', [
            'model' => $model,
            'tasks' => $tasks
        ]);
    }

    public function actionMyMyTasks()
    {
        $model = new Task();
        return $this->render('mymytasks', [
            'model' => $model
        ]);
    }   

    public function actionGetMyMyTasks()
    {
        $model = new Task();   
       $tasks = $model->find()->where(['owner' => Yii::$app->user->id, 'parent_task' => NULL])->all();
       // var_dump($tasks->discription);
       // 
       for ($i=0; $i < count($tasks); $i++) { 
              $userTasks[$i]['id'] = $tasks[$i]->task_id;
              $userTasks[$i]['name'] = $tasks[$i]->task_name;
              $tasks[$i]->percentcomplete > 0 ? $userTasks[$i]['percentcomplete'] = $tasks[$i]->percentcomplete : $userTasks[$i]['percentcomplete'] = 0;
              $userTasks[$i]['status'] = $tasks[$i]->status;
              $userTasks[$i]['owner'] = User::getUserName($tasks[$i]->owner);
              $userTasks[$i]['description'] = $tasks[$i]->task_deskription;
              $userTasks[$i]['deadline'] = $tasks[$i]->deadline;
              $userTasks[$i]['estimate'] = $tasks[$i]->estimation;
              $userTasks[$i]['startTime'] = Timetracking::firstActivity($tasks[$i]->task_id);
              $lastActivity = Timetracking::lastActivity($tasks[$i]->task_id);
              if ($lastActivity['time'] > 0) {
                  $lastAct = $lastActivity['status'] . " at " .  $lastActivity['time'];
              } else { $lastAct =  $lastActivity['status'];}
              $userTasks[$i]['type'] = $lastAct;
              $userTasks[$i]['employer'] = User::getUserName($tasks[$i]->employer);
          }   
        echo Json::encode($userTasks);
    }   

    public function actionGetMyMyTasksNoparent()
    {
        $model = new Task();   
       $tasks = $model->find()->where(['owner' => Yii::$app->user->id])->orWhere(['employer' => Yii::$app->user->id])->all();    
        echo Json::encode($tasks);
    }


    public function actionGetTasks(){
        $model = new Task();
                $res_arr = $model->SelectNoParrent();
        for ($i=0; $i < count($res_arr); $i++) { 
            $items = $model->SelectParrent($res_arr[$i], 0);
        }
        if (isset($items)) {
            $allitems = $model->SelectNoTree($items);
        } else {
            $items = [];
            $allitems = $model->SelectNoTree($items);
        }
        
        return Json::encode($allitems);   
        
    }

    public function actionGetMyTasks(){
        $model = new Task();
                $res_arr = $model->SelectNoParrent();
        for ($i=0; $i < count($res_arr); $i++) { 
            $items = $model->SelectParrent($res_arr[$i], 0);
        }
        if (isset($items)) {
            $allitems = $model->SelectNoTree($items);
        } else {
            $items = [];
            $allitems = $model->SelectNoTree($items);
        }
        
        return Json::encode($allitems);   
        
    }

    public function actionSetTimeTracking($taskId, $state){
        $model = new Timetracking();
        Yii::$app->session->open();
        $user_id = Yii::$app->user->id;
        $date = date('Y-m-d H:i:s');
        if ($state == "play" || $state == "resume") {
            $model->user_id = $user_id;
            $model->task_id = $taskId;
            $model->start = $date;
            $ps = Timetracking::find()->where(['pause' => NULL, 'user_id' => $user_id])->one();
            if (!$ps) {
                $model->save();
            } else { 
                echo Json::encode($rull = 1); 
            } 
        } elseif ($state == "pause") {
            $pause = Timetracking::find()->where(['pause' => NULL, 'task_id' => $taskId])->one();            
            $pause->pause = $date;
            $pause->update();
            $task = Task::find()->where(['task_id' => $taskId])->one();
            $task->track_time = Timetracking::WorkTime($taskId);
            $task->employer = "$task->employer";
            $task->update();
            Task::updatePrecenteComplete($taskId);
        } elseif ($state == "stop") {
            $stop = Timetracking::find()->where(['pause' => NULL, 'task_id' => $taskId])->one();
            if (is_null($stop)) {
                $tsk_model = Task::find()->where(['task_id' => $taskId])->one();
                $tsk_model->status = 'WaitConfirm';
                $tsk_model->employer = "$tsk_model->employer";
                $tsk_model->track_time = Timetracking::WorkTime($taskId);
                $tsk_model->update();
                $not = new Notice;
                $not->user_id = $tsk_model->owner;
                $not->task_id = $taskId;
                $not->notice_text = "Task " . "'" . $tsk_model->task_name . "'" . " wait to submit!";
                $noti = $not->find()->where(['task_id' => $taskId, 'user_id' => $tsk_model->owner, 'read_n' => NULL])->asArray()->one();
                if (!$noti) {
                    $not->save(); 
                }
                 // return $this->redirect('/task/task' . "/" . $tsk_model->task_id);          
            } else {
                $pause = Timetracking::find()->where(['pause' => NULL,'task_id' => $taskId])->one();
                $pause->pause = $date;
                $pause->update();
                $tsk_model = Task::find()->where(['task_id' => $taskId])->one();
                $tsk_model->status = 'WaitConfirm'; 
                $tsk_model->employer = "$tsk_model->employer";
                $tsk_model->track_time = Timetracking::WorkTime($taskId);              
                $tsk_model->update();
                $not = new Notice;
                $not->user_id = $tsk_model->owner;
                $not->task_id = $taskId;
                $not->notice_text = "Task " . "'" . $tsk_model->task_name . "'" . " wait to submit!";
                $noti = $not->find()->where(['task_id' => $taskId, 'user_id' => $tsk_model->owner, 'read_n' => NULL])->asArray()->one();
                // var_dump($noti);
                if (!$noti) {
                    $not->save(); 
                }  
                // return $this->redirect('/task/task' . "/" . $tsk_model->task_id);          
            }
        }
    }

    public function actionGetTime($taskId){
        $model = new Task;
        $tsk = $model->findOne($taskId);
        return Json::encode($tsk->track_time);
    }

    /**
    * Prepare data to view fill info for task
    */

    public function actionGetFullTask($id){
        $fullRes = Task::find()->where(['task_id' => $id])->asArray()->one();

        $employer = User::findOne($fullRes['employer']);
        $fullRes['employerId'] = $fullRes['employer'];
        $owner = User::findOne($fullRes['owner']);                
        $fullRes['employer'] = $employer->username;        
        $fullRes['owner']= $owner->username;

        $worktime = Timetracking::WorkTime($id);
        if (is_null($worktime)) {
            $fullRes['worktime'] = 0;
            } else {
                $fullRes['worktime'] = $worktime;
            }
        if (is_null($fullRes['percentcomplete'])) {
                   $fullRes['percentcomplete'] = "0";
            } else {$fullRes['percentcomplete'] = $fullRes['percentcomplete'];}      

        $fullRes['lastactivity'] = Timetracking::lastActivity($id);

        echo Json::encode($fullRes);
    }

    public function actionGetTaskInWork(){
        $taskmodel = new Task;
        $lastactivity = Timetracking::UserTimeTrack(Yii::$app->user->id);
        if (is_null($lastactivity['pause'])) {
            $task = $taskmodel->findOne($lastactivity['task_id']);            
            echo Json::encode($task);
        } 
    }

    /**
     * Displays a single Task model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        User::identityUser();
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Task model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($id)
    {  
        User::identityUser();
        if (is_numeric($id)) {
                $maxEstimate = Task::getEstimate($id);
        } else { $maxEstimate = 1000*1000; }
        $model = new Task();
        $post = Yii::$app->request->post();
        if ($post) {
            $us_id = User::getUserId(Yii::$app->request->post()['Task']["employer"]);

            $urls = isset($post['url']) ? $post['url'] : null;
            $files = UploadedFile::getInstancesByName('file');
        }
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $newid = $model->task_id;
            $upd = Task::findOne($newid);
            $upd->owner = $us_id['owner_id'];
            $upd->employer = $us_id['empl_id'];
            if (is_numeric($id)) {
                $maxEstimate = Task::getEstimate($id);
                $upd->parent_task = $id;
                $depen = new Dependence;
                $depen->task_id =  $id;
                $depen->dep_task_id = $newid;
                $depen->save();
            }
            $upd->update();
            $not = new Notice;
            $not->user_id = $upd->employer;
            $not->task_id = $newid;
            $not->notice_text = "Task " . "'" . $upd->task_name . "'" . " wait to submit!";
            $not->save();

            if($urls){
                $model->uploadResources($urls, 'url');
            }

            if ($files) {
                $model->uploadResources($files, 'file');
            }


            return $this->redirect('/task/task' . "/" . $newid);
        } else {
            return $this->render('create', [
                'model' => $model,
                'maxEstimate' => $maxEstimate,
            ]);
        }
    }

    /**
     * Updates an existing Task model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        User::identityUser();
        $post = Yii::$app->request->post();
        $urls = isset($post['url']) ? $post['url'] : null;
        $files = UploadedFile::getInstancesByName('file');

        $model = $this->findModel($id);
        if (is_numeric($model->parent_task)) {
            $maxEstimate = Task::getEstimate($model->parent_task);
        } else { $maxEstimate = 1000*1000; }

        if ($model->owner != Yii::$app->user->id) {
            return $this->redirect('/user/personal-area');
        }

        $model->employer  = User::getUserName($model->employer);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $us_id = User::getUserId(Yii::$app->request->post()['Task']["employer"]);
            $upd = Task::findOne($id);
            $upd->employer = $us_id['empl_id'];
            if ($model->status == 'NotAccepted') {
                $upd->status = 'WaitSubmit';
            } else {
                $model->status = $model->status;
            }
            $upd->update();
            $this->updateNotice($model->task_id);
            $newnot = new Notice;
            $newnot->user_id = $us_id['empl_id'];
            $newnot->task_id = $id;
            $newnot->notice_text = "Task " . "'" . $model->task_name . "'" . " wait to submit!";
            $newnot->save();

            if($urls){
                $model->uploadResources($urls, 'url');
            }

            if ($files) {
                $model->uploadResources($files, 'file');
            }
            return $this->redirect('/task/task'. "/" . $model->task_id);
        } else {
            return $this->render('update', [
                'model' => $model,
                'maxEstimate' => $maxEstimate
            ]);
        }
    }

    public function actionRemoveResource()
    {
        $res_id = Yii::$app->request->post('res_id', null);
        $modelResource = Resource::findOne($res_id);
        if($modelResource && $modelResource->delete()){
            foreach ($modelResource->taskRelations as $relation){
                $relation->delete();
            }
        }

        return true;
    }

    /**
     * Deletes an existing Task model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Task model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Task the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Task::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('This task does not exist.');
        }
    }

    public function updateNotice($id){
        $not = Notice::find()->where(['user_id' => Yii::$app->user->id, 'task_id' => $id, 'read_n' => NULL])->one();
        if ($not) {
            $not->read_n = 1;
            $not->update();
        }
    }

    public function createNotice($id, $model, $user ,$mes){
        $newnot = new Notice;
        $newnot->user_id = $model->$user;
        $newnot->task_id = $id;
        $newnot->notice_text = "Task " . "'" . $model->task_name . "'" . $mes;
        $newnot->save(); 
    }

}
