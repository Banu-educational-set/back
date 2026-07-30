<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\User;
use App\Services\HomeworkSubmissionService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Tests\TestCase;

class PersianLocalizationTest extends TestCase
{
    public function test_application_uses_persian_by_default(): void
    {
        $this->assertSame('fa', app()->getLocale());
    }

    public function test_api_validation_errors_are_fully_persian(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'خطای اعتبارسنجی.')
            ->assertJsonPath('errors.phone.0', 'فیلد شماره تلفن الزامی است.')
            ->assertJsonPath('errors.password.0', 'فیلد رمز عبور الزامی است.');
    }

    public function test_nested_validation_attributes_are_translated(): void
    {
        $validator = Validator::make(
            ['answers' => [['question_id' => 'invalid']]],
            ['answers.*.question_id' => ['integer']],
        );

        $this->assertSame(
            'فیلد شناسه سؤال باید یک عدد صحیح باشد.',
            $validator->errors()->first('answers.0.question_id'),
        );
    }

    public function test_service_errors_are_translated(): void
    {
        $homework = new Homework(['deadline' => now()->subMinute()]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('مهلت ارسال این تکلیف به پایان رسیده است.');

        app(HomeworkSubmissionService::class)->submit(new User, $homework, 1);
    }

    public function test_unexpected_api_errors_do_not_leak_english_messages(): void
    {
        Route::get('/api/test-unexpected-localized-error', static function () {
            throw new RuntimeException('English internal exception');
        });

        $this->getJson('/api/test-unexpected-localized-error')
            ->assertInternalServerError()
            ->assertExactJson([
                'success' => false,
                'message' => 'خطایی در سرور رخ داده است. لطفاً دوباره تلاش کنید.',
            ]);
    }

    public function test_web_login_fallback_is_translated(): void
    {
        $this->getJson('/login')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'احراز هویت نشده‌اید.');
    }
}
