<?php

namespace App;

class UserRepository
{
    private $repoFile;

    public function __construct(string $repoFile)
    {
        $this->repoFile = $repoFile;
    }

    public function save(array $user): void
    {
        $users = $this->getAllUsers();
        $found = false;

        foreach ($users as &$existingUser) {
            if ($existingUser['id'] === $user['id']) {
                $existingUser = $user;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $user['id'] = $user['id'] ?? uniqid();
            $users[] = $user;
        }

        file_put_contents($this->repoFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function getAllUsers(): array
    {
        if (file_exists($this->repoFile)) {
            return json_decode(file_get_contents($this->repoFile), true) ?? [];
        }
        return [];
    }

    public function find(string $id): ?array
    {
        $users = $this->getAllUsers();
        foreach ($users as $user) {
            if ($user['id'] === $id) {
                return $user;
            }
        }
        return null;
    }
}