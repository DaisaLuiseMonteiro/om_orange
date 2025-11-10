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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->string('type'); // transfert, depot, retrait, etc.
            $table->decimal('montant', 15, 2);
            $table->string('devise', 3)->default('XOF');
            
            // Compte émetteur
            $table->uuid('compte_emetteur_id');
            $table->foreign('compte_emetteur_id')
                ->references('id')
                ->on('comptes')
                ->onDelete('cascade');
            
            // Compte bénéficiaire (peut être null pour les dépôts/retraits)
            $table->uuid('compte_destinataire_id')->nullable();
            $table->foreign('compte_destinataire_id')
                ->references('id')
                ->on('comptes')
                ->onDelete('set null');
            
            // Frais de transaction
            $table->decimal('frais', 15, 2)->default(0);
            $table->string('statut')->default('en_attente'); // en_attente, reussie, echouee, annulee
            
            // Informations supplémentaires
            $table->string('motif')->nullable();
            $table->json('metadata')->nullable();
            
            // Horodatages
            $table->timestamp('date_execution')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
