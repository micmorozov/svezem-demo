<?php

namespace frontend\modules\cabinet\models;

use common\models\Profile;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\web\NotFoundHttpException;

/**
 * ProfileSearch represents the model behind the search form about `common\models\Profile`.
 */
class ProfileSearch extends Profile
{

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [

        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $this->load($params);

        $user = (Yii::$app->user->isGuest) ? 0 : Yii::$app->user->identity->id;

        /** @var ActiveQuery $query */
        $query = Profile::find()->where(['created_by' => $user]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
             $query->where('0=1');
            return $dataProvider;
        }

        return $dataProvider;
    }
}
