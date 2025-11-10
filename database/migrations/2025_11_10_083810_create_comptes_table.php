<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_compte', 20)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('titulaire');
            $table->enum('type', ['courant', 'epargne', 'entreprise', 'joint']);
            $table->decimal('solde', 15, 2)->default(0);
            $table->string('devise', 3)->default('XOF');
            $table->enum('statut', ['actif', 'bloque', 'suspendu'])->default('actif');
            $table->string('motif_blocage')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
