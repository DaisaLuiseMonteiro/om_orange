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
        Schema::table('users', function (Blueprint $table) {
            // Supprimer les colonnes existantes qui seront remplacées
            $table->dropColumn(['name', 'email_verified_at']);
            
            // Ajouter les colonnes pour la relation polymorphique
            $table->string('authenticatable_type')->nullable()->after('id');
            $table->string('authenticatable_id')->nullable()->after('authenticatable_type');
            
            // Ajouter les colonnes pour la vérification
            $table->string('verification_code')->nullable()->after('password');
            $table->timestamp('code_expires_at')->nullable()->after('verification_code');
            $table->boolean('is_active')->default(false)->after('code_expires_at');
            
            // Modifier la colonne email pour la rendre nullable
            $table->string('email')->nullable()->change();
            
            // Ajouter des index pour les performances
            $table->index(['authenticatable_type', 'authenticatable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Recréer les colonnes supprimées
            $table->string('name')->after('id');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            
            // Supprimer les colonnes ajoutées
            $table->dropColumn([
                'authenticatable_type',
                'authenticatable_id',
                'verification_code',
                'code_expires_at',
                'is_active'
            ]);
            
            // Remettre la colonne email en non nullable
            $table->string('email')->nullable(false)->change();
        });
    }
};
