<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\Task;

class PersonalArea extends ActiveRecord{

	public function getTasksToDo(){
		$user_id = Yii::$app->user->id;
		$userTasks = Task::find()->where(['employer' => $user_id, 'status' => 'InWork'])->all();
		for ($i=0; $i < count($userTasks); $i++) {
			$importance[$i]['task_name'] = $userTasks[$i]['task_name'];
			$importance[$i]['task_id'] = $userTasks[$i]['task_id']; 
			$importance[$i]['last_time'] = (int) ceil(($userTasks[$i]['estimation']-($userTasks[$i]['track_time']/60))*60);
			$importance[$i]['percentcomplete'] =$userTasks[$i]['percentcomplete'];
			if (isset($importance[$i]['percentcompletetime'])) {
                $importance[$i]['percentcompletetime'] = round(($userTasks[$i]['track_time'] * 100) / ($userTasks[$i]['estimation'] * 60), 2);
            } else { $importance[$i]['percentcompletetime'] = 0;}
			// $importance[$i]['importance'] = round( $userTasks[$i]['percentcomplete']/(($userTasks[$i]['estimation']*60) - $userTasks[$i]['track_time']), 3); 
			$importance[$i]['importance'] = $importance[$i]['percentcompletetime'] + $importance[$i]['last_time'] + $importance[$i]['percentcomplete'];

			/*$importance[$i]['importance'] = round($userTasks[$i]['percentcomplete']/(100 - $userTasks[$i]['percentcomplete']) + (($userTasks[$i]['track_time'] * 100) / ($userTasks[$i]['estimation']*60)/(100 - ($userTasks[$i]['track_time'] * 100) / ($userTasks[$i]['estimation']*60))), 3);*/
			// if (!is_null($userTasks[$i]->parent_task)) {
			// 	$importance[$i]['importance'] += 1;
			// }
		}
		usort($importance, function($a, $b){
			if ($a['importance'] == $b['importance']) {
				return 0;
			}
    			return ($a['importance'] < $b['importance']) ? -1 : 1;
			});
		return $importance;
	}
}