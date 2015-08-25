<?php

namespace yii2tech\tests\unit\ar\linkmany\data;

use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property string $name
 */
class Group extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'Group';
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
}