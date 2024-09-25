<?php
/*
 *
 *  * Copyright (c) D3V Services Limited on behalf of their client.
 *  * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 *
 */

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->integer('number')->index();
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('labels')->nullable();
            $table->string('state')->nullable();
            $table->foreignIdFor(User::class)->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('notified_developer')->default(false)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
