<?php

namespace Web\Dev;

// https://ru.hexlet.io/courses/php-mvc/lessons/post-form/theory_unit
interface ValidatorInterfacePractice
{
    // Return array of errors, or empty array if no errors
    public function validate(array $data);
}
