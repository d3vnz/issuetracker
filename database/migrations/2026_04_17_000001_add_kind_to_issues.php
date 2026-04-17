<?php
/*
 * Copyright (c) D3V Services Limited on behalf of their client.
 * All code used in this development is either the property of D3V or their client and is not to be altered or reproduced without prior written consent from either of the above.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            if (! Schema::hasColumn('issues', 'kind')) {
                $table->string('kind')->nullable()->index()->after('status');
            }
        });

        // Back-fill kind from the existing labels.name (single-object JSON shape).
        // Safe to re-run; only touches NULL kinds.
        if (Schema::hasColumn('issues', 'kind') && Schema::hasColumn('issues', 'labels')) {
            DB::statement("
                UPDATE issues
                SET kind = JSON_UNQUOTE(JSON_EXTRACT(labels, '$.name'))
                WHERE kind IS NULL
                  AND labels IS NOT NULL
                  AND JSON_EXTRACT(labels, '$.name') IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            if (Schema::hasColumn('issues', 'kind')) {
                $table->dropIndex(['kind']);
                $table->dropColumn('kind');
            }
        });
    }
};
