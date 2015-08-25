<?php

namespace yii2tech\tests\unit\ar\linkmany;

use yii2tech\ar\linkmany\LinkManyBehavior;
use yii2tech\tests\unit\ar\linkmany\data\Item;

class LinkManyBehaviorTest extends TestCase
{
    public function testGetRelationReferenceAttributeValue()
    {
        /* @var $item Item|LinkManyBehavior */
        $item = Item::findOne(1);
        $relationReferenceAttributeValue = $item->getRelationReferenceAttributeValue();
        $this->assertEquals([1, 2], $relationReferenceAttributeValue);
        $this->assertEquals($relationReferenceAttributeValue, $item->groupIds);
    }
}