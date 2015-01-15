<?php

use yii\db\Schema;

class m130524_201442_init extends \yii\mongodb\Migration
{
    public function up()
    {
        $this->createCollection('users');
    }

    public function down()
    {
        $this->dropCollection('users');
    }
}
