<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePetrolDefaultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('petrol_defaults', function (Blueprint $table) {
            $table->id();
            $table->string('type',15);
            $table->double('price',10,3)->nullable();
            $table->string('supplier',100)->nullable();
            $table->timestamps();
            $table->unique('type');
        });
        // Schema::table('petrol_defaults',function(Blueprint $table){
        //     $table->double('price',10,3)->nullable()->change();
        //     $table->string('supplier',100)->nullable()->change();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('petrol_defaults');
    }
}
