<?php

return [
    'required' => 'Поле «:attribute» обязательно.',
    'string' => 'Поле «:attribute» должно быть текстом.',
    'confirmed' => 'Пароли не совпадают.',
    'unique' => 'Такое значение поля «:attribute» уже используется.',
    'min' => ['string' => 'Поле «:attribute» должно содержать не менее :min символов.'],
    'max' => [
        'string' => 'Поле «:attribute» не должно быть длиннее :max символов.',
        'file' => 'Файл «:attribute» не должен быть больше :max КБ.',
        'array' => 'Поле «:attribute» не должно содержать больше :max элементов.',
    ],
    'regex' => 'Неверный формат поля «:attribute».',
    'url' => 'Поле «:attribute» должно быть корректным URL.',
    'image' => 'Поле «:attribute» должно быть изображением.',
    'attributes' => [
        'login' => 'логин', 'password' => 'пароль', 'title' => 'название', 'name' => 'название списка',
        'slug' => 'адрес списка', 'games_text' => 'список игр', 'cover' => 'обложка',
    ],
];
