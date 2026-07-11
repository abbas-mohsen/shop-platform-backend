<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Extend the orders.status enum to include 'cancelled'.
     * Final allowed values: pending, approved, rejected, delivered, cancelled
     *
     * MySQL-only: ENUM + MODIFY COLUMN are MySQL syntax. SQLite (used by the
     * test suite) stores enums as TEXT with a CHECK constraint and accepts any
     * value once the constraint is dropped/ignored, so skipping is safe there.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','approved','rejected','delivered','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','approved','rejected','delivered') NOT NULL DEFAULT 'pending'");
    }
};
