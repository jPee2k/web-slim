<?php

namespace Web\Dev\Users;

function usersData() {
    $path = __DIR__ . '/../src/data/users-info.txt';

    if (file_exists($path)) {
        $data = file_get_contents($path);
        $preparedUsersData = explode(PHP_EOL, $data);
        //$jsonUsersData = collect($preparedUsersData)->filter()->all();
        $users = collect($preparedUsersData)->filter()->map(function ($user) {
            return json_decode($user, true);
        })->keyBy('id')->all();
    } else {
        $users = [];
    }

    return $users;
}

function findUserById($id) {
    $users = collect(usersData());

    return $users->firstWhere('id', $id);
}

function putContents($content) {
    $path = __DIR__ . '/../src/data/users-info.txt';

    $encodedContent = json_encode($content) . PHP_EOL;
    $result = file_put_contents($path, $encodedContent, FILE_APPEND | LOCK_EX);

    return $result;
}
