<?php

namespace Web\Dev;

class Validator
{
    private $errors = [];

    public function validate($user)
    {
        if (empty($user['name'])) {
            $this->errors['name'] = "Поле Name обязательное для заполения";
        }

        if (empty($user['email'])) {
            $this->errors['email'] = "Поле Email обязательное для заполения";
        }

        if (empty($user['password'])) {
            $this->errors['password'] = "Поле Password обязательное для заполения";
        }

        if (empty($user['passwordConfirmation'])) {
            $this->errors['passwordConfirmation'] = "Поле Password Confirmation обязательное для заполения";
        }

        if ($user['password'] !== $user['passwordConfirmation']) {
            $this->errors['passwordConfirmation'] = "Пароли не совпадают";
        }

        return $this->errors;
    }
}
