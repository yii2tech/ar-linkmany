<?php

namespace yii2tech\tests\unit\ar\linkmany\data;

use yii\db\ActiveRecord;
use yii2tech\ar\linkmany\LinkManyBehavior;

/**
 * @property integer $id
 * @property string $name
 *
 * @property Group[]|array $groups
 */
class Item extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'linkManyBehavior' => [
                'class' => LinkManyBehavior::className(),
                'variationsRelation' => 'translations',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'Item';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['name', 'required'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroups()
    {
        return $this->hasMany(Group::className(), ['id' => 'groupId'])->viaTable('ItemGroup', ['itemId' => 'id']);
    }
}