<?php

namespace App;

class UserValidator
{
    public function validate(array $user): array
    {
        $errors = [];

        if (empty($user['nickname'])) {
            $errors['nickname'] = 'Nickname cannot be empty';
        } elseif (mb_strlen($user['nickname']) < 4) {
            $errors['nickname'] = 'Nickname must be at least 4 characters long';
        }

        if (empty($user['email'])) {
            $errors['email'] = 'Email cannot be empty';
        } elseif (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email is not valid';
        }

        return $errors;
    }
}