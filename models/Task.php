<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use sjaakp\taggable\TaggableBehavior;
/**
 * This is the model class for table "task".
 *
 * @property string $task_id
 * @property int $owner
 * @property string $employer
 * @property int $parent_task
 * @property string $task_name
 * @property string $task_deskription
 * @property string $deadline
 * @property string $start_task
 * @property int $estimation
 * @property int $track_time
 * @property string $status
 * @property int $dell
 */
class Task extends ActiveRecord
{
    public $items = [];
    public $arrayitems = [];
    public $file;
    public $ntskid;

    // public $indent = 0;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'task';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['owner', 'parent_task', 'estimation', 'track_time', 'dell'], 'integer'],
            [['task_name'], 'required'],
            [['task_deskription'], 'string'],
            [['deadline', 'start_task'], 'safe'],
            [['employer'], 'string', 'max' => 50],
            [['task_name'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 225],
            [['file'], 'file'],
            [['editorTags'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'task_id' => 'Task ID',
            'owner' => 'Owner',
            'employer' => 'Employer',
            'parent_task' => 'Parent Task',
            'task_name' => 'Task Name',
            'task_deskription' => 'Task Deskription',
            'deadline' => 'Deadline',
            'percentcomplete' => 'Percent Complete',
            'estimation' => 'Estimation',
            'track_time' => 'Track Time',
            'status' => 'Status',
            'dell' => 'Dell',
            'modelKeyAttribute' => 'task_id',
            'nameAttribute'=>'tag',
        ];
    }

    public function behaviors()
    {
        return [
            'taggable' => [
                'class' => TaggableBehavior::className(),
                'tagClass' => Tag::className(),
                'junctionTable' => 'tag_to_task',
                'modelKeyAttribute' => 'task_id',
                'nameAttribute'=>'tag',
            ]
        ];
    }
    
    public function SelectNoParrent(){
        Yii::$app->session->open();
            $user_id = Yii::$app->user->id;
            return Task::find()->where(['owner' => $user_id, 'parent_task' => NULL])->all();      
    }

    public function SelectParrent($arr, $indent){
        $lastactivity = Timetracking::lastActivity($arr->task_id);
        if (is_null($arr->parent_task)) {
            $parent = 0;
        } else {$parent = $arr->parent_task;}
        if ($lastactivity['time'] == 0) {
            $lastactivity['time'] = "Not activity";
        }
        $this->items['id'] = $arr->task_id; 
        $this->items['indent'] = $indent; 
        $this->items['parent'] = $parent; 
        $this->items['title'] = $arr->task_name; 
        $this->items['duration'] = $arr->estimation; 
        $this->items['percentComplete'] = $arr->percentcomplete; 
        $this->items['start'] = $lastactivity['time']; 
        $this->items['finish'] = $arr->status; 
        $this->items['effortDriven'] = 0;    
        array_push($this->arrayitems, $this->items);

        Yii::$app->session->open();
            $user_id = Yii::$app->user->id;
        $parent_arr = Task::find()->where(['owner' => $user_id, 'parent_task' => $arr->task_id])->all(); 
        for ($i=0; $i < count($parent_arr); $i++) { 
            $this->SelectParrent($parent_arr[$i], $i+1);
        }

        return $this->arrayitems;
    }

    public function SelectNoTree($arrayitems){
        $user_id = Yii::$app->user->id;
        $res = Task::find()->where(['owner' => $user_id])->orWhere(['employer' => $user_id])->all();
        for ($i=0; $i < count($res); $i++) {
            if (is_null($res[$i]->parent_task)) {
                $parent = 0;
            } else {
                $parent = $res[$i]->parent_task;
            }

            $lastactivity = Timetracking::lastActivity($res[$i]->task_id);
            if ($lastactivity['time'] == 0) {
                $lastactivity['time'] = "Not activity";
            }

            $this->items['id'] = $res[$i]->task_id;
            $this->ntskid = $res[$i]->task_id;
            $this->items['indent'] = 0; 
            $this->items['parent'] = $parent; 
            $this->items['title'] = $res[$i]->task_name; 
            $this->items['duration'] = $res[$i]->estimation; 
            $this->items['percentComplete'] = $res[$i]->percentcomplete; 
            $this->items['start'] = $lastactivity['time']; 
            $this->items['finish'] = $res[$i]->status; 
            $this->items['effortDriven'] = 0;
            $aa = array_filter($arrayitems,function($a){
                                            return $a["id"] == $this->ntskid;
                                        }); 
            if (!$aa) {
                array_push($arrayitems, $this->items);  
            }              
        }
        return $arrayitems;
    }

    public function getResources()
    {
        return $this->hasMany(Resource::className(),['res_id' => 'res_id'])->viaTable('resource_to_task', [ 'task_id' => 'task_id' ]);
    }

    public function uploadResources($resources, $type)
    {
        foreach ($resources as $resource) {
            $model = new Resource();
            switch ($type) {
                case "url":
                    $model->res_path = $resource;
                    if($model->save()){
                        $this->saveResourceRelation($model->res_id);
                    }
                    break;
                case "file":
                    $path = 'uploads/'.Yii::$app->security->generateRandomString(32).'.'.$resource->extension;
//                    $path = 'uploads/' . $resource->baseName . '.' . $resource->extension;
                    $resource->saveAs($path);
                    $model->res_path = '/web/'.$path;
                    if ($model->save()){
                        $this->saveResourceRelation($model->res_id);
                    }
                    break;
            }
        }
    }

    public function saveResourceRelation($res_id)
    {
        $resToTask = new ResourceToTask();
        $resToTask->task_id = $this->task_id;
        $resToTask->res_id = $res_id;
        $resToTask->save();
    }

    public static function updatePrecenteComplete($id){
        $task = Task::findOne($id);
        $parent = $task->parent_task;
        if (($task->track_time/60) > $task->estimation)  {
            $task->track_time = $task->estimation * 60;
        }
        $task->percentcomplete = (int) ceil((($task->track_time/60)*100)/$task->estimation);
        if ($task->percentcomplete > 100 && $task->status == "InWork") {
                $task->percentcomplete = 99;
        } elseif ($task->status == "Finish") {
            $task->percentcomplete = 100;
        } else { 
            $task->percentcomplete = $task->percentcomplete; 
        }
        $task->employer = "$task->employer";
        $task->update();
        while (is_numeric($parent)) {
            $tasks = Task::find()->where(['parent_task' => $parent])->asArray()->all();
            $worktime = 0;
            if (count($tasks)) {
                for ($i=0; $i < count($tasks); $i++) { 
                    if (($tasks[$i]['track_time']/60) > $tasks[$i]['estimation']) {
                        $tasks[$i]['track_time'] = $tasks[$i]['estimation'] * 60;
                    }
                    $worktime += $tasks[$i]['track_time'];
                }
            }
            $parentTask = Task::findOne($task->parent_task);
            $percentComplete = (int) ceil((($worktime/60)*100)/$parentTask->estimation);
            if ($percentComplete > 100) {
                $percentComplete = 100;
            } else { $percentComplete = $percentComplete; }
            $parentTask->percentcomplete = $percentComplete;
            $parentTask->employer = "$parentTask->employer";
            $parentTask->update();
            $parent = $parentTask->parent_task;
        }
    }

    public function getEstimate($id){
        $parentTask = Task::findOne($id);
        $parentEstimate = $parentTask->estimation;
        $subtasks = Task::find()->where(['parent_task' => $id])->asArray()->all();
        $subestimation = 0;
        if (count($subtasks)) {
            for ($i=0; $i < count($subtasks); $i++) { 
                $subestimation += $subtasks[$i]['estimation'];                 
            }
            return ($parentEstimate - $subestimation);
        } else { 
            return $parentEstimate; 
        }
        
    }
}
