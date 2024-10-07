<?php

namespace App;
class UserRepository {
    private $repoFile;

    public function __construct(string $repoFile) {
        $this->repoFile = $repoFile;
    }

    public function save(array $user): void
    {
        $users = $this->getAllUsers();
        $id = uniqid();
        $user['id'] = $id;
        $users[$id] = $user;
        file_put_contents($this->repoFile, json_encode($users, JSON_PRETTY_PRINT));
    }

    public function getAllUsers(): array
    {
        if (file_exists($this->repoFile)) {
            return json_decode(file_get_contents($this->repoFile), true) ?? [];
        }
        return [];
    }
}