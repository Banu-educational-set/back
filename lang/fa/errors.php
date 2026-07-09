<?php

return [

    // Generic / framework-level (bootstrap exception handlers)
    'validation' => 'خطای اعتبارسنجی.',
    'unauthenticated' => 'احراز هویت نشده‌اید.',
    'forbidden' => 'شما اجازه‌ی انجام این کار را ندارید.',
    'not_found' => 'یافت نشد.',
    'resource_not_found' => 'منبع موردنظر یافت نشد.',
    'endpoint_not_found' => 'آدرس موردنظر یافت نشد.',
    'method_not_allowed' => 'این متد مجاز نیست.',
    'request_failed' => 'درخواست ناموفق بود.',
    'invalid_api_key' => 'کلید API خارجی نامعتبر است.',

    // Account status (EnsureUserApproved middleware)
    'verify_phone' => 'برای ادامه، شماره تلفن خود را با کد تأیید کنید.',
    'awaiting_approval' => 'حساب شما در انتظار تأیید مدیر است.',
    'account_not_approved' => 'حساب شما تأیید نشده است.',
    'account_blocked' => 'حساب شما مسدود شده است.',
    'account_blocked_reason' => 'حساب شما مسدود شده است: :reason',

    // Auth
    'invalid_credentials' => 'اطلاعات ورود نامعتبر است.',

    // Media
    'file_missing' => 'فایل روی سرور موجود نیست.',
    'media_not_owned' => 'این فایل متعلق به کاربر فعلی نیست.',
    'media_already_attached' => 'این فایل قبلاً به منبع دیگری متصل شده است.',
    'file_exceeds' => 'حجم فایل نباید از :max کیلوبایت بیشتر باشد.',
    'file_ext_allowed' => 'پسوند فایل باید یکی از موارد زیر باشد: :values.',

    // Enrollment / terms
    'term_not_open' => 'این ترم در حال حاضر باز نیست.',
    'term_not_open_enrollment' => 'این ترم در حال حاضر برای ثبت‌نام باز نیست.',
    'already_enrolled' => 'شما قبلاً در این ترم ثبت‌نام کرده‌اید.',
    'user_must_be_student_or_missionary' => 'کاربر باید نقش دانشجو یا مبلّغ داشته باشد.',

    // Exams
    'exam_no_questions' => 'این آزمون هیچ سؤالی ندارد.',
    'exam_window_closed' => 'مهلت این آزمون به پایان رسیده است.',
    'exam_already_passed' => 'شما قبلاً در این آزمون قبول شده‌اید.',
    'exam_not_started' => 'پیش از ثبت پاسخ‌ها باید آزمون را آغاز کنید.',
    'each_question_one_correct' => 'هر سؤال باید دقیقاً یک گزینه‌ی صحیح داشته باشد.',
    'exam_misconfigured' => 'پیکربندی این آزمون نادرست است. لطفاً با مدیر تماس بگیرید.',
    'answer_invalid_question' => 'یکی از سؤالات ارسال‌شده متعلق به این آزمون نیست.',
    'answer_invalid_option' => 'یکی از گزینه‌های ارسال‌شده متعلق به سؤال مربوطه نیست.',
    'media_purpose_mismatch' => 'این فایل برای هدف دیگری بارگذاری شده و قابل استفاده در اینجا نیست.',

    // Prerequisites
    'prereq_course_not_met' => 'پیش‌نیازهای این درس برآورده نشده است.',
    'prereq_session_not_met' => 'پیش‌نیازهای این جلسه برآورده نشده است.',
    'course_self_prereq' => 'یک درس نمی‌تواند پیش‌نیاز خودش باشد.',
    'course_prereq_cycle' => 'تعیین این پیش‌نیازها باعث ایجاد حلقه می‌شود.',
    'course_prereq_same_term' => 'دروس پیش‌نیاز باید در همان ترم این درس باشند.',
    'session_self_prereq' => 'یک جلسه نمی‌تواند پیش‌نیاز خودش باشد.',
    'session_prereq_cycle' => 'تعیین این پیش‌نیازها باعث ایجاد حلقه می‌شود.',
    'session_prereq_same_course' => 'جلسات پیش‌نیاز باید در همان درس این جلسه باشند.',

    // Missionary requests
    'missionary_only_update' => 'فقط مبلّغان می‌توانند درخواست‌ها را به‌روزرسانی کنند.',
    'request_assigned_other' => 'این درخواست به مبلّغ دیگری اختصاص یافته است.',
    'missionary_source_states' => 'مبلّغ فقط می‌تواند درخواست‌هایی را که در حالت «در انتظار» یا «دیده‌شده» هستند به‌روزرسانی کند.',
    'missionary_target_states' => 'مبلّغ فقط می‌تواند وضعیت را به «پذیرفته‌شده» یا «ردشده» تغییر دهد.',
    'invalid_status' => 'وضعیت نامعتبر است.',
    'missionary_id_role' => 'شناسه‌ی مبلّغ باید به کاربری با نقش مبلّغ اشاره کند.',
    'missionary_not_found' => 'مبلّغ یافت نشد.',
    'memory_request_not_accepted' => 'درخواست انتخاب‌شده باید متعلق به شما و در وضعیت پذیرفته‌شده باشد.',

    // Course/session/teacher validation closures
    'teacher_id_role' => 'شناسه‌ی مدرس باید به کاربری با نقش مدرس اشاره کند.',
    'city_not_in_province' => 'شهر انتخاب‌شده متعلق به استان انتخاب‌شده نیست.',
    'minimum_score_gt_score' => 'حداقل نمره نمی‌تواند از نمره‌ی کل بیشتر باشد.',
    'message_or_media_required' => 'وارد کردن پیام یا حداقل یک فایل الزامی است.',

    // Dynamic "Invalid X. Allowed: ..." (query params against enums)
    'invalid_value_allowed' => 'مقدار :label نامعتبر است. مقادیر مجاز: :allowed.',
    'invalid_role_allowed' => 'نقش‌های نامعتبر: :invalid. مقادیر مجاز: :allowed.',
    'invalid_value' => 'مقدار نامعتبر است.',

];
