<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProviderAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('provider_accounts', function (Blueprint $table) {
            $table->increments('id')->unique();
            $table->integer('provider_id')->unique();
            $table->string('stripe_acct_id')->unique();
            $table->string('stripe_sk_key')->unique();
            $table->string('stripe_pk_key')->unique();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('state')->nullable();
            $table->string('DOB')->nullable();
            $table->string('ssn_last_4')->nullable();
            $table->enum('type', ['individual', 'company']);
            $table->string('tos_acceptance_date')->nullable();
            $table->string('tos_acceptance_ip')->nullable();
            $table->string('personal_id_no')->nullable();
            $table->string('verification_document')->nullable();
            $table->string('status')->nullable();
            $table->string('bank_account_holder_name')->nullable();
            $table->string('bank_account_holder_type')->nullable();
            $table->string('bank_routing_number')->nullable();
            $table->string('bank_account_number')->nullable();
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
        Schema::dropIfExists('provider_accounts');
    }
}
