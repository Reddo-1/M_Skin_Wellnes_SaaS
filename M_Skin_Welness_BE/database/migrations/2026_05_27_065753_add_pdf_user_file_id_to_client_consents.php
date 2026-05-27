<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_consents', function (Blueprint $table) {
            //FK al UserFile del PDF generado por el wizard; nullable porque los consents firmados con el flujo antiguo no tienen PDF asociado
            $table->unsignedBigInteger('pdf_user_file_id')->nullable()->after('signature_user_file_id');

            $table->foreign('pdf_user_file_id', 'fk_client_consents_pdf')
                ->references('id')->on('user_files')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('client_consents', function (Blueprint $table) {
            $table->dropForeign('fk_client_consents_pdf');
            $table->dropColumn('pdf_user_file_id');
        });
    }
};
