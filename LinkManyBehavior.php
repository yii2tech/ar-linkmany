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
 * LinkManyBehavior
 *
 * @property BaseActiveRecord $owner
 * @property array|null $relationAttributeValue
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
     */
    public $relationReferenceAttribute;

    /**
     * @var null|array
     */
    private $_relationReferenceAttributeValue;


    /**
     * @param array|null $value
     */
    public function setRelationReferenceAttributeValue($value)
    {
        $this->_relationReferenceAttributeValue = $value;
    }

    /**
     * @return array
     */
    public function getRelationReferenceAttributeValue()
    {
        if ($this->_relationReferenceAttributeValue === null) {
            $this->_relationReferenceAttributeValue = $this->initRelationReferenceAttributeValue();
        }
        return $this->_relationReferenceAttributeValue;
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
        ];
    }

    /**
     * Handles owner 'afterInsert' and 'afterUpdate' events, ensuring related models are linked.
     * @param \yii\base\Event $event event instance.
     */
    public function afterSave($event)
    {
        // @todo
    }
}