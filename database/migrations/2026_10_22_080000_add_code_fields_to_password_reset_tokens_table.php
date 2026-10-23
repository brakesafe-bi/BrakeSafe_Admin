<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            if (!Schema::hasColumn('password_reset_tokens', 'code_hash')) {
                $table->string('code_hash', 6)->nullable();
            }
            if (!Schema::hasColumn('password_reset_tokens', 'code_expires_at')) {
                $table->timestamp('code_expires_at')->nullable();
            }
            if (!Schema::hasColumn('password_reset_tokens', 'code_attempts')) {
                $table->unsignedTinyInteger('code_attempts')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            $columns = [];
            foreach (['code_hash', 'code_expires_at', 'code_attempts'] as $column) {
                if (Schema::hasColumn('password_reset_tokens', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
