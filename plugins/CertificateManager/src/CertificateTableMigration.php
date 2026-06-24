<?php

namespace CertificateManager;

use App\Models\ScheduledConference;
use CertificateManager\Models\CertificateCategory;
use CertificateManager\Models\CertificateTemplate;
use FormReview\Model\ReviewForm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CertificateTableMigration extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (! Schema::hasTable('certificate_templates')) {
			Schema::create('certificate_templates', function (Blueprint $table) {
				$table->id();
				$table->foreignIdFor(ScheduledConference::class)->constrained();
				$table->string('name');
				$table->string('email');
				$table->unsignedInteger('type')->default(99);
				$table->unsignedBigInteger('template_id');
				$table->unsignedInteger('order_column')->nullable();
				$table->timestamps();
			});
		}

		if (! Schema::hasTable('certificates')) {
			Schema::create('certificates', function (Blueprint $table) {
				$table->id();
				$table->foreignIdFor(CertificateTemplate::class)->constrained();
				$table->string('email');
				$table->nullableMorphs('certifiable');
				$table->integer('number')->nullable();
				$table->timestamps();
			});
		}
	}
}
