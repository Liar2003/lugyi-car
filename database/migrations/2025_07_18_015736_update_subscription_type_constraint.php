<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing CHECK constraint if it exists
        DB::statement("ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_type_check");

        // Add the updated CHECK constraint with new values
        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_type_check
            CHECK (type IN ('1month', '3months', 'lifetime', '5months', '8months', '1year'))");
    }

    public function down(): void
    {
        // Roll back to the original constraint
        DB::statement("ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_type_check");

        DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_type_check
            CHECK (type IN ('1month', '3months', 'lifetime'))");
    }
};
