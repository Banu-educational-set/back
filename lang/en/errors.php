<?php

return [

    // Generic / framework-level (bootstrap exception handlers)
    'validation' => 'Validation error.',
    'unauthenticated' => 'Unauthenticated.',
    'forbidden' => 'Forbidden.',
    'not_found' => 'Not found.',
    'resource_not_found' => 'Resource not found.',
    'endpoint_not_found' => 'Endpoint not found.',
    'method_not_allowed' => 'Method not allowed.',
    'request_failed' => 'Request failed.',
    'invalid_api_key' => 'Invalid external API key.',

    // Account status (EnsureUserApproved middleware)
    'verify_phone' => 'Please verify your phone via OTP to continue.',
    'awaiting_approval' => 'Your account is awaiting admin approval.',
    'account_not_approved' => 'Account is not approved.',
    'account_blocked' => 'Your account has been blocked.',
    'account_blocked_reason' => 'Your account has been blocked: :reason',

    // Auth
    'invalid_credentials' => 'Invalid credentials.',

    // Media
    'file_missing' => 'File missing on disk.',
    'media_not_owned' => 'Media does not belong to the current user.',
    'media_already_attached' => 'Media is already attached to a different resource.',
    'file_exceeds' => 'File exceeds :max KB.',
    'file_ext_allowed' => 'File extension must be one of: :values.',

    // Enrollment / terms
    'term_not_open' => 'This term is not currently open.',
    'term_not_open_enrollment' => 'Term is not currently open for enrollment.',
    'already_enrolled' => 'Already enrolled in this term.',
    'user_must_be_student_or_missionary' => 'User must have the student or missionary role.',

    // Exams
    'exam_no_questions' => 'Exam has no questions.',
    'exam_window_closed' => 'The exam window has closed.',
    'exam_already_passed' => 'You have already passed this exam.',
    'exam_not_started' => 'You must start the exam before submitting.',
    'each_question_one_correct' => 'Each question must have exactly one correct option.',
    'exam_misconfigured' => 'This exam is misconfigured. Please contact an administrator.',
    'answer_invalid_question' => 'One of the submitted questions does not belong to this exam.',
    'answer_invalid_option' => 'One of the submitted options does not belong to its question.',
    'media_purpose_mismatch' => 'This file was uploaded for a different purpose and cannot be used here.',

    // Prerequisites
    'prereq_course_not_met' => 'Prerequisites not met for this course.',
    'prereq_session_not_met' => 'Prerequisites not met for this session.',
    'course_self_prereq' => 'A course cannot be its own prerequisite.',
    'course_prereq_cycle' => 'Assigning these prerequisites would create a cycle.',
    'course_prereq_same_term' => 'Prerequisite courses must belong to the same term as this course.',
    'session_self_prereq' => 'A session cannot be its own prerequisite.',
    'session_prereq_cycle' => 'Assigning these prerequisites would create a cycle.',
    'session_prereq_same_course' => 'Prerequisite sessions must belong to the same course as this session.',

    // Missionary requests
    'missionary_only_update' => 'Only missionaries can update requests.',
    'request_assigned_other' => 'This request is assigned to another missionary.',
    'missionary_source_states' => 'A missionary can only update requests that are pending or seen.',
    'missionary_target_states' => 'A missionary can only set the status to accepted or rejected.',
    'invalid_status' => 'Invalid status.',
    'missionary_id_role' => 'missionary_id must reference a user with the missionary role.',
    'missionary_not_found' => 'Missionary not found.',
    'memory_request_not_accepted' => 'The selected request must belong to you and be in the accepted status.',

    // Course/session/teacher validation closures
    'teacher_id_role' => 'teacher_id must reference a user with the teacher role.',
    'city_not_in_province' => 'City does not belong to the given province.',
    'minimum_score_gt_score' => 'minimum_score cannot exceed score.',
    'message_or_media_required' => 'Either a message or media_ids is required.',

    // Dynamic "Invalid X. Allowed: ..." (query params against enums)
    'invalid_value_allowed' => ':label is invalid. Allowed: :allowed.',
    'invalid_role_allowed' => 'Invalid role(s): :invalid. Allowed: :allowed.',
    'invalid_value' => 'Invalid value.',

];
