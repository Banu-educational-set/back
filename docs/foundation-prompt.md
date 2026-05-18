# 📘 Backend Specification — Educational System (Bano)

---

## 1. Tech Stack

* Framework: Laravel (latest stable)
* Database: PostgreSQL
* Auth: Laravel Sanctum
* Roles & Permissions: Spatie Laravel Permission
* Runtime: Docker
* Web Server: Nginx
* Cache/Queue: Redis
* Storage: Local (MVP), S3-compatible later

All development, testing, and deployment MUST be Docker-based.

---

## 2. Roles

admin
teacher
student
missionary
counselor

### Role Capabilities

#### Admin

* Full access
* Manage users (CRUD + roles)
* Manage terms, courses, sessions, exams, homeworks
* Review homework submissions
* View/respond to tickets
* Optional: create missionary requests

#### Teacher

* Manage terms, courses, sessions
* Create exams and homework
* Review homework

#### Student

* Enroll in terms
* View sessions
* Submit homework
* Take exams
* Create tickets (admin or counselor)

#### Missionary

* View assigned requests
* Update request status

#### Counselor

* View tickets
* Respond to tickets

---

## 3. Config

config/education.php

return [
'terms_required_for_missionary' => env('TERMS_REQUIRED_FOR_MISSIONARY', 3),
'default_exam_pass_score' => env('DEFAULT_EXAM_PASS_SCORE', 70),
];

---

## 4. Database Schema

### Users & Roles

users
roles
model_has_roles

---

### Educational Structure

terms

* id
* title
* is_active

courses

* id
* term_id
* title
* teacher_id
* capacity

sessions

* id
* course_id
* title
* type
* start_time
* location_or_link

---

### Exams

exams

* id
* session_id
* title
* pass_score

exam_questions

* id
* exam_id
* question_text

exam_options

* id
* question_id
* option_text
* is_correct

exam_attempts

* id
* user_id
* exam_id
* score
* is_passed
* submitted_at

exam_answers

* id
* attempt_id
* question_id
* selected_option_id

---

### Homework

homeworks

* id
* session_id
* title
* description
* deadline

homework_submissions

* id
* homework_id
* user_id
* status
* teacher_feedback

---

### Enrollment

term_enrollments

* id
* user_id
* term_id
* status

---

### Media (Polymorphic)

media

* id
* model_type
* model_id
* collection_name
* file_name
* file_path
* mime_type
* file_size
* disk
* created_at
* updated_at

Usage:

* user avatar
* session videos/voices
* homework files
* course/term images

---

### Missionary Requests

missionary_requests

* id
* missionary_id
* external_source
* requester_name
* requester_phone
* requester_email
* title
* description
* location
* requested_date
* status

---

### Ticketing

tickets

* id
* student_id
* assigned_to_user_id
* target_role
* subject
* status

ticket_messages

* id
* ticket_id
* sender_id
* message

---

## 5. Business Logic

### Term Completion

Student passes term when:

* all exams passed
* all homework accepted

---

### Missionary Promotion

If passed_terms >= config value → assign missionary role

---

### Exams

* Multiple choice only
* Auto scoring
* Pass score configurable

---

### Homework

* Student uploads file
* Teacher/admin approves or rejects

---

### Tickets

Student selects target_role:

* admin
* counselor

---

### Missionary Requests Flow

WordPress → API → Laravel → missionary panel

---

## 6. API

### Auth

POST /auth/register
POST /auth/login
POST /auth/logout
GET /auth/me

---

### Users

GET /admin/users
POST /admin/users
PUT /admin/users/{id}
DELETE /admin/users/{id}

---

### Terms/Courses/Sessions

CRUD endpoints

---

### Enrollment

POST /student/terms/{id}/enroll
GET /student/my-terms

---

### Exams

GET /exams/{id}
POST /exams/{id}/submit

---

### Homework

POST /homeworks/{id}/submit
PATCH /homework-submissions/{id}

---

### Tickets

Student:
POST /student/tickets
GET /student/tickets

Admin/Counselor:
GET /tickets
POST /tickets/{id}/messages

---

### Missionary Requests

External:
POST /external/missionary-requests
(Header: X-External-Api-Key)

Missionary:
GET /missionary/requests
PATCH /missionary/requests/{id}

---

## 7. Security

* Sanctum auth
* RBAC
* API key for external
* File validation

---

## 8. Services

TermCompletionService
MissionaryPromotionService
ExamScoringService
HomeworkReviewService
TicketService
MissionaryRequestService

---

## 9. Docker Setup

Services:

* app (Laravel)
* nginx
* postgres
* redis
* queue worker

Commands:

docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan migrate --seed

---

## 10. Development Order

1. Auth + roles
2. Users
3. Terms/Courses/Sessions
4. Enrollment
5. Exams
6. Homework
7. Term completion
8. Missionary promotion
9. Tickets
10. External requests

---

End of Document
