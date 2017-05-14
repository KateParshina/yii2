<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "resource_to_task".
 *
 * @property string $r_t_t_id
 * @property int $task_id
 * @property int $res_id
 */
class ResourceToTask extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'resource_to_task';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['task_id', 'res_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'r_t_t_id' => 'R T T ID',
            'task_id' => 'Task ID',
            'res_id' => 'Res ID',
        ];
    }
}
