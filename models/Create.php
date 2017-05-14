<?php


namespace app\models;

use Yii;

class Create extends \yii\db\ActiveRecord
{
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
            [['owner', 'parent_task', 'task_name', 'status'], 'required'],
            [['owner', 'parent_task', 'estimation', 'track_time'], 'integer'],
            [['task_deskription'], 'string'],
            [['deadline', 'start_task'], 'safe'],
            [['task_name'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 225],
            [['employer'], 'string', 'max' => 50],
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
//            'start_task' => 'Start Task',
            'estimation' => 'Estimation',
            'track_time' => 'Track Time',
            'status' => 'Status',
        ];
    }

}
