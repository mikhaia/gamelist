<?php

namespace App\Enums;

enum Achievement: string
{
    case Games1 = 'games_1';
    case Avatar1 = 'avatar_1';
    case Installed1 = 'installed_1';
    case Drops1 = 'drops_1';
    case Completions1 = 'completions_1';
    case Drops3 = 'drops_3';
    case Drops5 = 'drops_5';
    case Drops10 = 'drops_10';
    case Drops100 = 'drops_100';
    case Drops1000 = 'drops_1000';
    case Completions3 = 'completions_3';
    case Completions5 = 'completions_5';
    case Completions10 = 'completions_10';
    case Completions100 = 'completions_100';
    case Completions1000 = 'completions_1000';
    case Wishlist100 = 'wishlist_100';
    case Wishlist1000 = 'wishlist_1000';
    case Playing3 = 'playing_3';
    case Playing5 = 'playing_5';
    case Playing10 = 'playing_10';
    case Opinions1 = 'opinions_1';
    case Ratings1 = 'ratings_1';
    case Opinions3 = 'opinions_3';
    case Opinions5 = 'opinions_5';
    case Opinions10 = 'opinions_10';
    case Opinions100 = 'opinions_100';
    case Ratings5 = 'ratings_5';
    case Ratings10 = 'ratings_10';
    case Ratings100 = 'ratings_100';
    case Ratings1000 = 'ratings_1000';
    case GoldStatus = 'gold_status';
    case Friends1 = 'friends_1';
    case Friends3 = 'friends_3';
    case Friends5 = 'friends_5';
    case Friends10 = 'friends_10';
    case Friends100 = 'friends_100';

    public function title(): string
    {
        return match ($this) {
            self::Games1 => 'Бэклог пробуждён',
            self::Avatar1 => 'Знай героя в лицо',
            self::Installed1 => 'Диск выдержит',
            self::Drops1 => 'Тактическое отступление',
            self::Completions1 => 'Титры увидены',
            self::Drops3 => 'Первые жертвы',
            self::Drops5 => 'Без сожалений',
            self::Drops10 => 'Alt + F4 — мой стиль',
            self::Drops100 => 'Серийный дроппер',
            self::Drops1000 => 'Судья без права апелляции',
            self::Completions3 => 'Разминка окончена',
            self::Completions5 => 'Пожиратель титров',
            self::Completions10 => 'Комбо из финалов',
            self::Completions100 => 'Убийца бэклога',
            self::Completions1000 => 'Легенда последнего босса',
            self::Wishlist100 => 'Хочу всё',
            self::Wishlist1000 => 'Бэклог бессмертен',
            self::Playing3 => 'Тройная загрузка',
            self::Playing5 => 'Мультиклассовость',
            self::Playing10 => 'Главный герой мультивселенной',
            self::Opinions1 => 'Никто не спрашивал',
            self::Ratings1 => 'Я здесь критик',
            self::Opinions3 => 'Местный эксперт',
            self::Opinions5 => 'Голос бэклога',
            self::Opinions10 => 'Диванный журналист',
            self::Opinions100 => 'Главный редактор',
            self::Ratings5 => 'Шкала активирована',
            self::Ratings10 => 'Суд начался',
            self::Ratings100 => 'Игровой инквизитор',
            self::Ratings1000 => 'Абсолютный критик',
            self::GoldStatus => 'Золотой аккаунт',
            self::Friends1 => 'Player Two подключён',
            self::Friends3 => 'Пати собрано',
            self::Friends5 => 'Своя гильдия',
            self::Friends10 => 'Рейд почти готов',
            self::Friends100 => 'Сервер переполнен',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Games1 => 'Первая игра добавлена. Пути назад больше нет.',
            self::Avatar1 => 'Аватар загружен. Теперь даже в лобби видно, кто тут собирается фармить ачивки.',
            self::Installed1 => 'Игра установлена, а свободное место снова объявлено необязательным.',
            self::Drops1 => 'Это не поражение. Просто игра оказалась недостойна твоего времени.',
            self::Completions1 => 'Первая игра пройдена. Финальный босс уже пишет жалобу.',
            self::Drops3 => 'Три игры не пережили встречу с твоими стандартами.',
            self::Drops5 => 'Пять игр отправлены туда, где им больше не придётся притворяться интересными.',
            self::Drops10 => 'Десять тактических выходов ради сна, жизни и игр получше.',
            self::Drops100 => 'Сто игр начали путь, но не заслужили увидеть его конец.',
            self::Drops1000 => 'Тысяча игр признаны виновными в преступлении против твоего времени.',
            self::Completions3 => 'Три игры пройдены. Настоящая охота только начинается.',
            self::Completions5 => 'Пять финалов позади, а жажда новых побед только усилилась.',
            self::Completions10 => 'Десять игр завершены. Счётчик побед официально сломан.',
            self::Completions100 => 'Сто игр завершены. Бэклог впервые почувствовал настоящий страх.',
            self::Completions1000 => 'Тысяча игр пройдена. Финальные боссы теперь проверяют, нет ли тебя онлайн.',
            self::Wishlist100 => 'Сто игр ждут своего часа. Свободное время пока не найдено.',
            self::Wishlist1000 => 'Тысяча желаемых игр. Этого хватит до пенсии и ещё на пару жизней.',
            self::Playing3 => 'Три мира открыты одновременно. Реальный пока можно свернуть.',
            self::Playing5 => 'Пять игр в процессе, потому что выбрать одну — слишком скучно.',
            self::Playing10 => 'Десять активных приключений. Даже память уже просит отдельный квест-журнал.',
            self::Opinions1 => 'Первое мнение опубликовано. Теперь интернет наконец знает правду.',
            self::Ratings1 => 'Первая оценка поставлена. Игровая индустрия нервно обновляет страницу.',
            self::Opinions3 => 'Три мнения написаны. Авторитет пока самопровозглашённый, но уверенный.',
            self::Opinions5 => 'Пять игр получили всё, что ты о них думаешь, даже если они не были готовы.',
            self::Opinions10 => 'Десять мнений опубликованы. Осталось потребовать пресс-копии.',
            self::Opinions100 => 'Сто мнений написаны. Теперь у каждой игры есть шанс получить твою рецензию и травму.',
            self::Ratings5 => 'Пять оценок поставлены. Цифры начинают приобретать личный характер.',
            self::Ratings10 => 'Десять игр получили приговор по всей строгости десятибалльной шкалы.',
            self::Ratings100 => 'Сто игр оценены. Ни один спорный балл не будет пересмотрен.',
            self::Ratings1000 => 'Тысяча оценок поставлена. Metacritic уже чувствует конкуренцию.',
            self::GoldStatus => 'Email добавлен, Gold открыт. Теперь аккаунт сияет чуть ярче остальных.',
            self::Friends1 => 'Первый союзник найден. Теперь есть с кем делить победы и обвинять лаги.',
            self::Friends3 => 'Три друга добавлены. Для данжа хватит, если кто-нибудь согласится быть хилером.',
            self::Friends5 => 'Пять друзей в списке. Можно выбирать название, герб и крайнего за вайп.',
            self::Friends10 => 'Десять друзей собраны. Танка всё ещё нет, но виноват уже назначен.',
            self::Friends100 => 'Сто друзей добавлены. Социальная батарейка покинула игру.',
        };
    }

    public function requirement(): string
    {
        return match ($this) {
            self::Games1 => 'Добавьте первую игру в любой список.',
            self::Avatar1 => 'Загрузите фотографию профиля.',
            self::Installed1 => 'Впервые отметьте игру как установленную.',
            self::Drops1 => 'Впервые отметьте игру как брошенную.',
            self::Completions1 => 'Впервые отметьте игру как пройденную.',
            self::Drops3 => 'Бросьте 3 игры.',
            self::Drops5 => 'Бросьте 5 игр.',
            self::Drops10 => 'Бросьте 10 игр.',
            self::Drops100 => 'Бросьте 100 игр.',
            self::Drops1000 => 'Бросьте 1000 игр.',
            self::Completions3 => 'Пройдите 3 игры.',
            self::Completions5 => 'Пройдите 5 игр.',
            self::Completions10 => 'Пройдите 10 игр.',
            self::Completions100 => 'Пройдите 100 игр.',
            self::Completions1000 => 'Пройдите 1000 игр.',
            self::Wishlist100 => 'Добавьте 100 игр в желаемые.',
            self::Wishlist1000 => 'Добавьте 1000 игр в желаемые.',
            self::Playing3 => 'Играйте одновременно в 3 игры.',
            self::Playing5 => 'Играйте одновременно в 5 игр.',
            self::Playing10 => 'Играйте одновременно в 10 игр.',
            self::Opinions1 => 'Оставьте первое мнение об игре.',
            self::Ratings1 => 'Поставьте первую оценку игре.',
            self::Opinions3 => 'Оставьте мнения о 3 играх.',
            self::Opinions5 => 'Оставьте мнения о 5 играх.',
            self::Opinions10 => 'Оставьте мнения о 10 играх.',
            self::Opinions100 => 'Оставьте мнения о 100 играх.',
            self::Ratings5 => 'Оцените 5 игр.',
            self::Ratings10 => 'Оцените 10 игр.',
            self::Ratings100 => 'Оцените 100 игр.',
            self::Ratings1000 => 'Оцените 1000 игр.',
            self::GoldStatus => 'Добавьте email и получите Gold-статус.',
            self::Friends1 => 'Добавьте первого друга.',
            self::Friends3 => 'Добавьте 3 друзей.',
            self::Friends5 => 'Добавьте 5 друзей.',
            self::Friends10 => 'Добавьте 10 друзей.',
            self::Friends100 => 'Добавьте 100 друзей.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Games1 => 'stadia_controller',
            self::Avatar1 => 'account_circle',
            self::Installed1 => 'download_done',
            self::Drops1 => 'block',
            self::Completions1 => 'trophy',
            self::Drops3, self::Drops5, self::Drops10, self::Drops100, self::Drops1000 => 'block',
            self::Completions3, self::Completions5, self::Completions10, self::Completions100, self::Completions1000 => 'trophy',
            self::Wishlist100, self::Wishlist1000 => 'bookmark_add',
            self::Playing3, self::Playing5, self::Playing10 => 'sports_esports',
            self::Opinions1, self::Opinions3, self::Opinions5, self::Opinions10, self::Opinions100 => 'edit',
            self::Ratings1, self::Ratings5, self::Ratings10, self::Ratings100, self::Ratings1000 => 'auto_awesome',
            self::GoldStatus => 'workspace_premium',
            self::Friends1, self::Friends3, self::Friends5, self::Friends10, self::Friends100 => 'groups',
        };
    }

    public function colorClasses(): string
    {
        return match ($this) {
            self::Drops1,
            self::Drops3,
            self::Drops5,
            self::Drops10,
            self::Drops100,
            self::Drops1000 => 'bg-rose-500/10 text-rose-300 ring-rose-400/20',
            self::Completions1,
            self::Ratings1,
            self::Ratings5,
            self::Ratings10,
            self::Ratings100,
            self::Ratings1000,
            self::GoldStatus => 'bg-amber-500/10 text-amber-300 ring-amber-400/20',
            self::Completions3,
            self::Completions5,
            self::Completions10,
            self::Completions100,
            self::Completions1000 => 'bg-emerald-500/10 text-emerald-300 ring-emerald-400/20',
            self::Wishlist100,
            self::Wishlist1000,
            self::Games1,
            self::Avatar1,
            self::Installed1,
            self::Opinions1,
            self::Opinions3,
            self::Opinions5,
            self::Opinions10,
            self::Opinions100 => 'bg-violet-500/10 text-violet-300 ring-violet-400/20',
            self::Playing3,
            self::Playing5,
            self::Playing10 => 'bg-cyan-500/10 text-cyan-300 ring-cyan-400/20',
            self::Friends1,
            self::Friends3,
            self::Friends5,
            self::Friends10,
            self::Friends100 => 'bg-sky-500/10 text-sky-300 ring-sky-400/20',
        };
    }
}
