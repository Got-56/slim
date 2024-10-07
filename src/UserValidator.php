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
        return $errors;
    }
}