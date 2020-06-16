<?php

namespace Web\Dev;

// https://ru.hexlet.io/courses/php-mvc/lessons/post-form/theory_unit
class ValidatorPractice implements ValidatorInterfacePractice
{
    public function validate(array $course)
    {
        // BEGIN (write your solution here)
        $errors = [];

        if (empty($course['paid'])) {
            $errors['paid'] = "Can't be blank";
        }

        if (empty($course['title'])) {
            $errors['title'] = "Can't be blank";
        }

        return $errors;
        // END
    }
}
