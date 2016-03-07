<?php

namespace yii2tech\tests\unit\ar\linkmany;

use Yii;
use yii\db\Query;
use yii2tech\ar\linkmany\LinkManyBehavior;
use yii2tech\tests\unit\ar\linkmany\data\Item;

class LinkManyBehaviorTest extends TestCase
{
    public function testSetupRelationReferenceAttributeValue()
    {
        $behavior = new LinkManyBehavior();

        $behavior->setRelationReferenceAttributeValue([1, 2]);
        $this->assertTrue($behavior->getIsRelationReferenceAttributeValueInitialized());
        $this->assertEquals([1, 2], $behavior->getRelationReferenceAttributeValue());

        $behavior->setRelationReferenceAttributeValue([]);
        $this->assertEquals([], $behavior->getRelationReferenceAttributeValue());

        $behavior->setRelationReferenceAttributeValue('');
        $this->assertEquals([], $behavior->getRelationReferenceAttributeValue());

        $behavior->setRelationReferenceAttributeValue(null);
        $this->assertFalse($behavior->getIsRelationReferenceAttributeValueInitialized());
    }

    public function testGetRelationReferenceAttributeValue()
    {
        /* @var $item Item|LinkManyBehavior */
        $item = Item::findOne(1);
        $relationReferenceAttributeValue = $item->getRelationReferenceAttributeValue();
        $this->assertEquals([1, 2], $relationReferenceAttributeValue);
        $this->assertEquals($relationReferenceAttributeValue, $item->groupIds);
    }

    /**
     * @depends testGetRelationReferenceAttributeValue
     */
    public function testNewRecord()
    {
        /* @var $item Item|LinkManyBehavior */
        /* @var $refreshedItem Item|LinkManyBehavior */

        $item = new Item();
        $item->name = 'new item';
        $item->groupIds = [2, 4];
        $item->save(false);

        $refreshedItem = Item::findOne($item->id);
        $this->assertEquals($item->groupIds, $refreshedItem->groupIds);
    }

    /**
     * @depends testGetRelationReferenceAttributeValue
     */
    public function testRemoveReferences()
    {
        /* @var $item Item|LinkManyBehavior */
        /* @var $refreshedItem Item|LinkManyBehavior */
        $item = Item::findOne(1);
        $item->groupIds = [2];
        $item->save(false);

        $refreshedItem = Item::findOne($item->id);
        $this->assertEquals($item->groupIds, $refreshedItem->groupIds);
    }

    /**
     * @depends testGetRelationReferenceAttributeValue
     */
    public function testRemoveAllReferences()
    {
        /* @var $item Item|LinkManyBehavior */
        /* @var $refreshedItem Item|LinkManyBehavior */
        $item = Item::findOne(1);
        $item->groupIds = [];
        $item->save(false);

        $refreshedItem = Item::findOne($item->id);
        $this->assertEquals([], $refreshedItem->groupIds);

        $this->assertEquals(2, (new Query())->from('ItemGroup')->count());
    }

    /**
     * @depends testRemoveAllReferences
     */
    public function testRemoveAllReferencesNoDelete()
    {
        /* @var $item Item|LinkManyBehavior */
        $item = Item::findOne(1);
        $item->groupIds = [];
        $item->deleteOnUnlink = false;
        $item->save(false);

        $this->assertEquals(4, (new Query())->from('ItemGroup')->count());
    }

    /**
     * @depends testGetRelationReferenceAttributeValue
     */
    public function testAddReferences()
    {
        /* @var $item Item|LinkManyBehavior */
        /* @var $refreshedItem Item|LinkManyBehavior */
        $item = Item::findOne(1);
        $item->groupIds = array_merge($item->groupIds, [3, 4]);
        $item->save(false);

        $refreshedItem = Item::findOne($item->id);
        $this->assertEquals($item->groupIds, $refreshedItem->groupIds);
    }

    /**
     * @depends testAddReferences
     */
    public function testAddReferencesNotUnique()
    {
        /* @var $item Item|LinkManyBehavior */
        /* @var $refreshedItem Item|LinkManyBehavior */
        $item = Item::findOne(1);
        $item->groupIds = [1, 2, 2, 4];
        $item->save(false);

        $refreshedItem = Item::findOne($item->id);
        $this->assertEquals([1, 2, 4], $refreshedItem->groupIds);
    }

    public function testDrySave()
    {
        /* @var $item Item|LinkManyBehavior */
        /* @var $refreshedItem Item|LinkManyBehavior */
        $item = Item::findOne(1);
        $item->save(false);

        $this->assertFalse($item->isRelationPopulated('groups'));
    }

    /**
     * @depends testNewRecord
     */
    public function testExtraColumns()
    {
        $item = new Item();
        $item->name = 'new item';
        $item->dataGroupIds = [1, 3];
        $item->save(false);

        /* @var $refreshedItem Item|LinkManyBehavior */
        $refreshedItem = Item::findOne($item->id);
        $this->assertEquals($item->dataGroupIds, $refreshedItem->dataGroupIds);

        $viaModels = $item->itemGroupData;
        $this->assertCount(2, $viaModels);
        $this->assertEquals('test', $viaModels[0]->note);
        $this->assertEquals('callback', $viaModels[0]->callbackNote);
    }

    /**
     * @depends testNewRecord
     */
    public function testDelete()
    {
        $item = new Item();
        $item->name = 'new item';
        $item->groupIds = [1, 3];
        $item->save(false);

        $item->delete();

        $junctionTableCount = (new Query())
            ->from('ItemGroup')
            ->andWhere(['itemId' => $item->id])
            ->count();
        $this->assertEquals(0, $junctionTableCount);
    }
}