<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Temperatures extends Migration
{
	protected $table = 'temperatures';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		Schema::create($this->table, function (Blueprint $table) {
			$table->increments('id');
			$table->float('value');
			$table->timestamps();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop($this->table);
    }
}
