<?php

namespace frontend\modules\cabinet\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Payment;

/**
 * PaymentsSearch represents the model behind the search form about `common\models\PaymentService`.
 */
class PaymentsSearch extends Payment
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'created_by', 'updated_by', 'service_id', 'payment_system_id', 'cargo_id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['service_price', 'service_amount'], 'number'],
            [['wallet_number'], 'safe'],
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
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Payment::find()
          ->where(['created_by' => userId()])
          ->orderBy(['created_at' => SORT_DESC])
          ->with([
            'service', 'paymentSystem', 'cargo'
          ])
        ;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'service_id' => $this->service_id,
            'payment_system_id' => $this->payment_system_id,
            'cargo_id' => $this->cargo_id,
            'status' => $this->status,
            'service_price' => $this->service_price,
            'service_amount' => $this->service_amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'wallet_number', $this->wallet_number]);

        return $dataProvider;
    }
}
