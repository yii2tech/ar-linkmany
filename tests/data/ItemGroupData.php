<?php

namespace yii2tech\tests\unit\ar\linkmany\data;

use yii\db\ActiveRecord;

/**
 * @property integer $itemId
 * @property integer $groupId
 * @property string $note
 * @property string $callbackNote
 */
class ItemGroupData extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ItemGroupData';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [$this->attributes(), 'safe'],
        ];
    }
}