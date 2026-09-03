<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique();

            $table->enum('type', [
                'quote',
                'proforma',
                'invoice',
                'credit_note',
            ]);

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->foreignId('source_document_id')
                ->nullable()
                ->constrained('commercial_documents')
                ->nullOnDelete();

            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('valid_until')->nullable();

            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);

            $table->string('currency', 3)->default('XOF');
            $table->string('payment_method')->nullable();

            $table->enum('status', [
                'draft',
                'sent',
                'accepted',
                'rejected',
                'partially_paid',
                'paid',
                'closed',
                'cancelled',
            ])->default('draft');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['type', 'status']);
        });

        Schema::create('commercial_document_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('commercial_document_id')
                ->constrained('commercial_documents')
                ->cascadeOnDelete();

            $table->foreignId('inventory_product_id')
                ->constrained('inventory_products')
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_document_lines');
        Schema::dropIfExists('commercial_documents');
    }
};
