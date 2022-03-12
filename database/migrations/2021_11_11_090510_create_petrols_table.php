<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FuelStation;

class CreatePetrolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('petrols', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->boolean("is_available")->default(0);
            $table->string('type');
            $table->double('price',10,3);
            $table->string('supplier',100);
            $table->foreignIdFor(FuelStation::class);
            $table->unique(['type','fuel_station_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('petrols');
    }
}
