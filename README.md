# DmitryDudin  
  
  Пример кода из последних задач.
  Как смотреть:
  1) Функционал парсинга сайта https://github.com/DmitryDudinShow/exampleCodehow/blob/main/src/Core/Fssp/ParseCaptcha/FsspSiteClient.php
  2) Сервис для получения данных https://github.com/DmitryDudinShow/exampleCodehow/blob/main/src/Core/Fssp/ParseCaptcha/FsspSiteService.php
  3) Функционал разгадывания каптч https://github.com/DmitryDudinShow/exampleCodehow/blob/main/src/Core/ComputerVision/FsspCaptchaModel.php
  4) Трекер процессов парсинга https://github.com/DmitryDudinShow/exampleCodehow/tree/main/src/Core/Fssp/ParseCaptcha/Tracker
  
    
    Это пример кода, он выдернут из инфраструктуры, и при простом запуске работать не будет, только для наглядности
    
  Функционал:  
    
  Была задача для парсинга сайта, из проблем были сложности с поставщиком информации, сайт постоянно лежал и после множества запросов банил клиента. Путём экспериментов и выявления зависимостей 403 ошибки был внедрён паттерн Curcuit Breaker + Retry Pattern. С наращиванием объёмов получаемой информации понадобилось подключение прокси + написал для него примитивный балансировщик в виде бесконечной коллекции.
  
    
  Следующая проблема возникла в каптче. Так как изначально каптча была простая - подключил системную библиотеку Teseract OCR, и через PHP-библиотеку-обёртку отдавал на разгадывание в системную утилиту, предварительно очистив изображение, и подгоняя под более высокое качество разгадывание (В рабочем состоянии качество было в среднем 67% (Каждая вторая каптча разгадывалась успешно)). При этом каптчи сохраняли в ClickHouse для дальнейшей адаптации модели разгадывания. В данный момент каптча на сайте обновилась и идёт подключение сервиса АнтиКапча, который безпроблемно включится в текущую схему, т.к. это было заложено в архитектуру на раннем этапе.

На что можно не смотреть:
  1) Констуктор. В некоторых кусках он может показаться большим, но в данном проекте он собирается через DI
  2) Логика в компоненте FsspSiteClient. Да, я знаю что логика, тем более хранение состояния недостустимо в подобного рода компонентах, но требования по времени были сверхсрочные, и на этот момент закрыли глаза. По той же причине отсутствуют тесты, хотя практически всегда я их пишу
  3) HTTP-запросы проходят через обёртку на curl (Guzzle)
  4) Exceptions. На первый взгляд может показаться что исключения в FsspSiteClient не соответствуют логическому поведению, но это не так, по крайней мере в рамках этой задачи. Дело в том, что снаружи этот код обрабатывает разные виды исключения по-своему (FsspSiteService). Всё что входит в группу LogickException - может нарушить логику или структуру получаемых данных, и должн завершать работу. HttpBadRequestException's - значат что проблемы на стороне поставщика услуг, и стоит попробовать позже. В общем - если возникли вопросы по уместности исключения - сперва взгляните на их обработку снаружи. Спасибо  

Остальные файлы вынес, т.к. они тоже цепляли компоненту
Есть вопросы по коду? Я в телеграме: https://t.me/dudin_dmitry
