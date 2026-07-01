<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\AdminCrudController;
use App\Models\JobApply;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class JobApplicationExportTest extends TestCase
{
    use RefreshDatabase;

    private string $cvDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureJobAppliesTable();
        $this->cvDirectory = public_path('storage/job/cv');

        if (! is_dir($this->cvDirectory)) {
            mkdir($this->cvDirectory, 0775, true);
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cvDirectory.'/test-export-*.pdf') ?: [] as $file) {
            unlink($file);
        }

        parent::tearDown();
    }

    public function test_all_apply_list_uses_lazy_export_rows_without_changing_row_data(): void
    {
        $owner = $this->adminUser();
        $otherOwner = $this->adminUser();

        for ($index = 1; $index <= 31; $index++) {
            $this->jobApplication($owner, $index);
        }

        $this->jobApplication($otherOwner, 100);

        Auth::login($owner);

        $view = app(AdminCrudController::class)->allApplyList();
        $applications = $view->getData()['all_list'];

        $this->assertInstanceOf(LazyCollection::class, $applications);
        $this->assertSame(
            ['applicant-01@example.test', 'applicant-02@example.test', 'applicant-03@example.test'],
            $applications->take(3)->pluck('email')->all()
        );
    }

    public function test_download_pdf_streams_application_attachment_without_loading_file_into_memory(): void
    {
        $owner = $this->adminUser();
        $fileName = 'test-export-'.str()->random(12).'.pdf';
        $contents = '%PDF-1.4'.PHP_EOL.str_repeat('streamed-payload', 2000);
        file_put_contents($this->cvDirectory.'/'.$fileName, $contents);

        $application = $this->jobApplication($owner, 1, [
            'attachment' => $fileName,
        ]);

        $response = app(AdminCrudController::class)->downloadPdf($application->id);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('attachment; filename='.$fileName, $response->headers->get('content-disposition'));

        ob_start();
        $response->sendContent();
        $downloadedContents = ob_get_clean();

        $this->assertSame($contents, $downloadedContents);
    }

    private function ensureJobAppliesTable(): void
    {
        if (Schema::hasTable('job_applies')) {
            return;
        }

        Schema::create('job_applies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('job_id')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function jobApplication(User $owner, int $index, array $overrides = []): JobApply
    {
        return JobApply::query()->create(array_merge([
            'job_id' => $index,
            'owner_id' => $owner->id,
            'user_id' => $owner->id,
            'email' => sprintf('applicant-%02d@example.test', $index),
            'phone' => sprintf('+370600%05d', $index),
            'attachment' => sprintf('test-export-%02d.pdf', $index),
        ], $overrides));
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'user_role' => UserRole::Admin->value,
        ]);
    }
}
