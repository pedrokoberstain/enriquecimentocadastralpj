<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->char('cnpj', 14);
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('situacao_cadastral')->nullable();
            $table->string('municipio')->nullable();
            $table->char('uf', 2)->nullable();
            $table->json('dados_cadastrais');
            $table->timestamp('last_synced_at');
            $table->timestamps();

            $table->unique(['team_id', 'cnpj']);
            $table->index(['team_id', 'razao_social']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
