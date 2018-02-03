<?php

use yii\db\Migration;
/**
 * Class m170201_115748_events_table
 * Creating table for events
 */
class m170201_115748_events_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('events',[
           'id'                     => $this->primaryKey(),
           'title'                  => $this->string(120)->notNull(),
           'event_type'             => $this->string(30)->notNull()->defaultValue(\yii\db\ActiveRecord::EVENT_AFTER_INSERT),
           'model_class'            => $this->string(120)->notNull(),
           'additional_expression'  => $this->string(255)->notNull(),
           'created_at'             => $this->dateTime(),
           'updated_at'             => $this->dateTime()
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
       $this->dropTable('events');
    }

}
