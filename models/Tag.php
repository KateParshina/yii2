<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use sjaakp\taggable\TagBehavior;

class Tag extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'tag' => [
                'class' => TagBehavior::className(),
                'junctionTable' => 'tag_to_task',
                'nameAttribute' => 'tag',
                // 'modelKeyAttribute' => 'task_id',
            ]
        ];
    }

    public function getTags() {
        return $this->hasMany(Task::className(), [ 'id' => 'task_id' ])
            ->viaTable('tag_to_task', [ 'tag_id' => 'id' ]);
    }
    public function getTagsAll()
    {
        return $this->find()->asArray()->orderBy(['count' =>SORT_DESC])->all();
    }
}