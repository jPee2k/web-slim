<?php

namespace Web\Dev;

class Validator
{
    private $errors = [];

    public function validate($user)
    {
        // name
        if (empty($user['name'])) {
            $this->errors['name'] = "Поле Name обязательное для заполения";
        }

        // email
        if (empty($user['email'])) {
            $this->errors['email'] = "Поле Email обязательное для заполения";
        }
        if (mb_strpos($user['email'], '@') === false) {
            $this->errors['email'] = "Email адресс должен содержать символ '@'";
        }

        // password
        if (empty($user['password'])) {
            $this->errors['password'] = "Поле Password обязательное для заполения";
        }
        if (strlen($user['password']) < 6) {
            $this->errors['password'] = "Пароль должен содержать минимум 6 символов";
        }

        // confirm password
        if (empty($user['passwordConfirmation'])) {
            $this->errors['passwordConfirmation'] = "Поле Password Confirmation обязательное для заполения";
        }

        if ($user['password'] !== $user['passwordConfirmation']) {
            $this->errors['passwordConfirmation'] = "Пароли не совпадают";
        }

        return $this->errors;
    }
}
