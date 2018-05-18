<?php namespace Viamage\RealTime\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateTokensTable extends Migration
{
    public function up()
    {
        Schema::create(
            'viamage_realtime_tokens',
            function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->increments('id');
                $table->integer('user_id')->index();
                $table->string('token')->index();
                $table->timestamps();
            }
        );
    }

    public function down()
    {
        Schema::dropIfExists('viamage_realtime_tokens');
    }
}
