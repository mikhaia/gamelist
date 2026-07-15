<?php

return [
    'brand' => 'GameList',
    'tagline' => 'Твоя игровая история — в одном месте',
    'hours' => '{1} :count час|[2,4] :count часа|[5,*] :count часов',
    'nav' => [
        'lists' => 'Мои списки', 'history' => 'Моя история', 'login' => 'Войти', 'register' => 'Регистрация', 'logout' => 'Выйти', 'settings' => 'Настройки',
    ],
    'actions' => [
        'create_list' => 'Новый список', 'save' => 'Сохранить', 'cancel' => 'Отмена', 'edit' => 'Изменить',
        'delete' => 'Удалить', 'add_game' => 'Добавить игру', 'catalog' => 'Каталог игр', 'import' => 'Импорт', 'preview' => 'Предпросмотр',
        'import_selected' => 'Импортировать выбранные', 'search' => 'Найти', 'copy' => 'Скопировать ссылку',
        'open' => 'Открыть список', 'back' => 'Назад к списку', 'cards' => 'Карточки', 'compact' => 'Компактно', 'board' => 'Доска',
    ],
    'statuses' => [
        'want_to_play' => 'Хочу сыграть', 'installed' => 'Установлена', 'playing' => 'Играю',
        'completed' => 'Пройдена', 'dropped' => 'Брошена',
    ],
    'platforms' => [
        'nintendo_switch' => 'Nintendo Switch', 'steam' => 'Steam', 'pc' => 'PC',
        'playstation' => 'PlayStation', 'xbox' => 'Xbox',
    ],
    'messages' => [
        'registered' => 'Добро пожаловать в GameList!', 'list_created' => 'Список создан.',
        'list_updated' => 'Список обновлён.', 'list_deleted' => 'Список удалён.',
        'game_created' => 'Игра добавлена.', 'game_updated' => 'Игра обновлена.',
        'game_deleted' => 'Игра удалена.', 'status_updated' => 'Статус обновлён.',
        'imported' => 'Добавлено игр: :count.', 'copied' => 'Ссылка скопирована.',
        'avatar_updated' => 'Аватар обновлён.', 'password_updated' => 'Пароль изменён.',
    ],
    'errors' => [
        'cover_download' => 'Не удалось скачать обложку. Попробуйте другой URL или загрузите файл.',
        'cover_not_image' => 'По указанному URL находится не изображение.',
        'cover_size' => 'Обложка должна быть меньше 8 МБ.',
        'cover_invalid' => 'Файл не удалось распознать как изображение.',
        'cover_url' => 'Этот URL нельзя использовать для загрузки обложки.',
        'game_duplicate' => 'Такая игра уже есть в этом списке.',
        'statuses_in_use' => 'Нельзя отключить статусы, которые уже используются: :statuses. Сначала измените статусы этих игр.',
        'import_limit' => 'За один раз можно импортировать не более 100 игр.',
    ],
];
