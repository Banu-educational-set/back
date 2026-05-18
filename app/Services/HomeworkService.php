<?php

namespace App\Services;

use App\Models\Homework;

class HomeworkService
{
    public function create(array $data): Homework
    {
        return Homework::create($data);
    }

    public function update(Homework $homework, array $data): Homework
    {
        $homework->fill($data)->save();

        return $homework->fresh();
    }

    public function delete(Homework $homework): void
    {
        $homework->delete();
    }
}
