<?php

return [
    'required' => 'Поле «:attribute» обязательно.',
    'string' => 'Поле «:attribute» должно быть текстом.',
    'confirmed' => 'Пароли не совпадают.',
    'digits' => 'Поле «:attribute» должно содержать :digits цифр.',
    'unique' => 'Такое значение поля «:attribute» уже используется.',
    'min' => ['string' => 'Поле «:attribute» должно содержать не менее :min символов.'],
    'max' => [
        'string' => 'Поле «:attribute» не должно быть длиннее :max символов.',
        'file' => 'Файл «:attribute» не должен быть больше :max КБ.',
        'array' => 'Поле «:attribute» не должно содержать больше :max элементов.',
    ],
    'regex' => 'Неверный формат поля «:attribute».',
    'email' => 'Поле «:attribute» должно быть корректным email-адресом.',
    'url' => 'Поле «:attribute» должно быть корректным URL.',
    'image' => 'Поле «:attribute» должно быть изображением.',
    'current_password' => 'Текущий пароль указан неверно.',
    'after_or_equal' => 'Дата «:attribute» не может быть раньше даты начала.',
    'attributes' => [
        'login' => 'логин или email', 'email' => 'email', 'password' => 'пароль', 'title' => 'название', 'name' => 'название списка',
        'slug' => 'адрес списка', 'games_text' => 'список игр', 'cover' => 'обложка', 'avatar' => 'аватар',
        'profile_cover' => 'обложка профиля', 'game_ids' => 'любимые игры', 'code' => 'код',
        'started_at' => 'начало игры', 'completed_at' => 'окончание игры', 'current_password' => 'текущий пароль',
    ],
];
