<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\ar\linkmany;

use yii\base\Behavior;
use yii\base\UnknownPropertyException;
use yii\db\ActiveRecordInterface;
use yii\db\BaseActiveRecord;

/**
 * LinkManyBehavior provides support for ActiveRecord many-to-many relation saving.
 *
 * Configuration example:
 *
 * ```php
 * class Item extends ActiveRecord
 * {
 *     public function behaviors()
 *     {
 *         return [
 *             'linkManyBehavior' => [
 *                 'class' => LinkManyBehavior::className(),
 *                 'relation' => 'groups',
 *                 'relationReferenceAttribute' => 'groupIds',
 *             ],
 *         ];
 *     }
 *
 *     public function getGroups()
 *     {
 *         return $this->hasMany(Group::className(), ['id' => 'groupId'])->viaTable('ItemGroup', ['itemId' => 'id']);
 *     }
 * }
 * ```
 *
 * @property BaseActiveRecord $owner
 * @property array|null $relationReferenceAttributeValue
 * @property boolean $isRelationReferenceAttributeValueInitialized
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class LinkManyBehavior extends Behavior
{
    /**
     * @var string name of the owner model "many to many" relation,
     * which should be handled.
     */
    public $relation;
    /**
     * @var string name of the owner model attribute, which should be used to set
     * "many to many" relation values.
     * This will establish an owner virtual property, which can be used to specify related record primary keys.
     */
    public $relationReferenceAttribute;
    /**
     * @var array additional column values to be saved into the junction table.
     * Each column value can be a callable, which will be invoked during linking to compose actual value.
     * Starting from version 1.0.2, this callable may accept linked model instance as a first parameter.
     * For example:
     *
     * ```php
     * [
     *     'type' => 'user-defined',
     *     'createdAt' => function() {return time();},
     *     'categoryId' => function ($model) {return $model->categoryId;},
     * ]
     * ```
     */
    public $extraColumns = [];
    /**
     * @var boolean whether to delete the pivot model or table row on unlink.
     */
    public $deleteOnUnlink = true;

    /**
     * @var null|array relation reference attribute value
     */
    private $_relationReferenceAttributeValue;


    /**
     * @param mixed $value relation reference attribute value
     */
    public function setRelationReferenceAttributeValue($value)
    {
        $this->_relationReferenceAttributeValue = $value;
    }

    /**
     * @return array relation reference attribute value
     */
    public function getRelationReferenceAttributeValue()
    {
        if ($this->_relationReferenceAttributeValue === null) {
            $this->_relationReferenceAttributeValue = $this->initRelationReferenceAttributeValue();
        }
        return $this->_relationReferenceAttributeValue;
    }

    /**
     * @return boolean whether the relation reference attribute value has been initialized or not.
     */
    public function getIsRelationReferenceAttributeValueInitialized()
    {
        return ($this->_relationReferenceAttributeValue !== null);
    }

    /**
     * Initializes value of [[relationAttributeValue]] in case it is not set.
     * @return array relation attribute value.
     */
    protected function initRelationReferenceAttributeValue()
    {
        $result = [];
        $relatedRecords = $this->owner->{$this->relation};
        if (!empty($relatedRecords)) {
            foreach ($relatedRecords as $relatedRecord) {
                /* @var $relatedRecord ActiveRecordInterface */
                $result[] = $this->normalizePrimaryKey($relatedRecord->getPrimaryKey());
            }
        }
        return $result;
    }

    /**
     * @param mixed $primaryKey raw primary key value.
     * @return string|integer normalized value.
     */
    protected function normalizePrimaryKey($primaryKey)
    {
        if (is_object($primaryKey) && method_exists($primaryKey, '__toString')) {
            // handle complex types like [[\MongoId]] :
            $primaryKey = $primaryKey->__toString();
        }
        return $primaryKey;
    }

    // Property Access Extension:

    /**
     * PHP getter magic method.
     * This method is overridden so that relation attribute can be accessed like property.
     *
     * @param string $name property name
     * @throws UnknownPropertyException if the property is not defined
     * @return mixed property value
     */
    public function __get($name)
    {
        try {
            return parent::__get($name);
        } catch (UnknownPropertyException $exception) {
            if ($name === $this->relationReferenceAttribute) {
                return $this->getRelationReferenceAttributeValue();
            }
            throw $exception;
        }
    }

    /**
     * PHP setter magic method.
     * This method is overridden so that relation attribute can be accessed like property.
     * @param string $name property name
     * @param mixed $value property value
     * @throws UnknownPropertyException if the property is not defined
     */
    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (UnknownPropertyException $exception) {
            if ($name === $this->relationReferenceAttribute) {
                $this->setRelationReferenceAttributeValue($value);
            } else {
                throw $exception;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true)
    {
        if (parent::canGetProperty($name, $checkVars)) {
            return true;
        }
        return ($name === $this->relationReferenceAttribute);
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true)
    {
        if (parent::canSetProperty($name, $checkVars)) {
            return true;
        }
        return ($name === $this->relationReferenceAttribute);
    }

    // Events :

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            BaseActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     * Handles owner 'afterInsert' and 'afterUpdate' events, ensuring related models are linked.
     * @param \yii\base\Event $event event instance.
     */
    public function afterSave($event)
    {
        if (!$this->getIsRelationReferenceAttributeValueInitialized() || !isset($this->owner->{$this->relation})) {
            return;
        }

        $linkModels = [];
        $unlinkModels = [];

        $newReferences = $this->getRelationReferenceAttributeValue();
        if (is_array($newReferences)) {
            $newReferences = array_unique($newReferences);
        } else {
            if (empty($newReferences)) {
                $newReferences = [];
            } else {
                $newReferences = [$newReferences];
            }
        }

        foreach ($this->owner->{$this->relation} as $relatedModel) {
            /* @var $relatedModel ActiveRecordInterface */
            $primaryKey = $this->normalizePrimaryKey($relatedModel->getPrimaryKey());
            if (($primaryKeyPosition = array_search($primaryKey, $newReferences)) === false) {
                $unlinkModels[] = $relatedModel;
            } else {
                unset($newReferences[$primaryKeyPosition]);
            }
        }

        if (!empty($newReferences)) {
            $relation = $this->owner->getRelation($this->relation);
            /* @var $relatedClass ActiveRecordInterface */
            $relatedClass = $relation->modelClass;
            $linkModels = $relatedClass::findAll(array_values($newReferences));
        }

        foreach ($unlinkModels as $model) {
            $this->owner->unlink($this->relation, $model, $this->deleteOnUnlink);
        }

        foreach ($linkModels as $model) {
            $this->owner->link($this->relation, $model, $this->composeLinkExtraColumns($model));
        }
    }

    /**
     * Handles owner 'afterDelete' event, ensuring related models are unlinked.
     * @param \yii\base\Event $event event instance.
     */
    public function afterDelete($event)
    {
        $this->owner->unlinkAll($this->relation, $this->deleteOnUnlink);
    }

    /**
     * Composes actual link extra columns value from [[extraColumns]], resolving possible callbacks.
     * @param ActiveRecordInterface|null $model linked model instance.
     * @return array additional column values to be saved into the junction table.
     */
    protected function composeLinkExtraColumns($model = null)
    {
        if (empty($this->extraColumns)) {
            return [];
        }
        $extraColumns = [];
        foreach ($this->extraColumns as $column => $value) {
            if (!is_scalar($value) && is_callable($value)) {
                $value = call_user_func($value, $model);
            }
            $extraColumns[$column] = $value;
        }
        return $extraColumns;
    }
}