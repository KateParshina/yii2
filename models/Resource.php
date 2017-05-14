<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * This is the model class for table "resource".
 *
 * @property string $res_id
 * @property string $res_path
 */
class Resource extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'resource';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['res_path'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'res_id' => 'Res ID',
            'res_path' => 'Res Path',
        ];
    }

    public function getTaskRelations()
    {
        return $this->hasMany(ResourceToTask::className(),['res_id' => 'res_id']);
    }
}
