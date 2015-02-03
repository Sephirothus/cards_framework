<?php

class m130524_201442_init extends \yii\mongodb\Migration
{
    public function up()
    {
        $this->createCollection('users');
        $this->insert('users', [
        	'username' => 'admin',
        	'auth_key' => '5g9y0VHHm66ne05P0kLQIJvrc99WMZQ0',
        	'password_hash' => '$2y$13$SbeNvPoL31KsXAEufRVaeOX9uH608fJ74gOOFq/fiKFS9YoQx5mkS',
        	'password_reset_token' => '',
        	'email' => 'no@no.no',
            'gender' => 'male',
        	'created_at' => date('Y-m-d H:i:s'),
        	'updated_at' => date('Y-m-d H:i:s'),
        	'status' => 10
        ]);
    }

    public function down()
    {
        $this->dropCollection('users');
    }
}
