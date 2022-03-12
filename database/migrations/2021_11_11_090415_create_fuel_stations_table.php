<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFuelStationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fuel_stations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->decimal('lat',10,7);
            $table->decimal('lon',10,7);
            $table->string('address')->nullable();
            $table->boolean('status')->default(1);
            $table->string('img_url')->default(asset('logos/default.png'));
            $table->boolean('is_active')->default(1);
            $table->json('access_list')->nullable();
            // $table->point('position');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fuel_stations');
    }
}
