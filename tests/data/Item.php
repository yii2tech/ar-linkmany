<?php

namespace yii2tech\tests\unit\ar\linkmany\data;

use yii\db\ActiveRecord;
use yii2tech\ar\linkmany\LinkManyBehavior;

/**
 * @property integer $id
 * @property string $name
 *
 * @property array $groupIds
 * @property array $dataGroupIds
 *
 * @property Group[]|array $groups
 * @property ItemGroupData[]|array $itemGroupData
 * @property Group[]|array $dataGroups
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
                'relation' => 'groups',
                'relationReferenceAttribute' => 'groupIds',
            ],
            'linkManyDataBehavior' => [
                'class' => LinkManyBehavior::className(),
                'relation' => 'dataGroups',
                'relationReferenceAttribute' => 'dataGroupIds',
                'extraColumns' => [
                    'note' => 'test',
                    'callbackNote' => function() {return 'callback';},
                ],
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
            ['groupIds', 'safe'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroups()
    {
        return $this->hasMany(Group::className(), ['id' => 'groupId'])->viaTable('ItemGroup', ['itemId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItemGroupData()
    {
        return $this->hasMany(ItemGroupData::className(), ['itemId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDataGroups()
    {
        return $this->hasMany(Group::className(), ['id' => 'groupId'])->via('itemGroupData');
    }
}