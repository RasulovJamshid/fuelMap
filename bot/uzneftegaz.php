<?php
//ini_set('error_reporting', E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
// определим кодировку UTF-8
header("HTTP/1.1 200 OK");
header('Content-type: text/html; charset=utf-8');
// создаем объект магазина
$newShop = new ManagerController();
// запускаем магазин
$newShop->init();

/** Класс Магазина
 * Class ShopBot
 */
class ManagerController
{
    // первичные данные
    private $token = "5189722595:AAF5SW7BnXy1ugVTrsoRchGFRgbND9CS2eo";
    private $admin = 4; // Ваш id в ТЕЛГРАМ

    // для соединения с БД
    private $host = 'localhost';
    private $db = 'daffuz_fuel_api';
    private $user = 'daffuz_jamshidR';
    private $pass = 'suutelegram987';
    private $charset = 'utf8mb4';
    /**
     * @var PDO
     */
    private $pdo;

    /** Стартуем  бота
     * @return bool
     */
    public function init()
    {
        // создаем соединение с базой данных
        $this->setPdo();
        // получаем данные от АПИ и преобразуем их в ассоциативный массив
        $rawData = json_decode(file_get_contents('php://input'), true);
        // направляем данные из бота в метод
        // для определения дальнейшего выбора действий
        $this->router($rawData);
        // в любом случае вернем true для бот апи
        return true;
    }

    /** Роутер - Определяем что делать с запросом от АПИ
     * @param $data
     * @return bool
     */
    private function router($data)
    {
        // берем технические данные id чата пользователя == его id и текст который пришел
        $chat_id = $this->getChatId($data);
        // $text=$this->getText($data);

        if(isset($data['message']['text'])||isset($data['callback_query'])){
        $text = $this->getText($data);
        }else{
            $text="phone_location";
        }
        // если пришли данные message
        if (array_key_exists("message", $data)) {
            // дастаем действие админа из базы
            // $action = $this->getAdminAction();
            $actionUser = $this->getUserAction($chat_id);

            // текстовые данные
            if (array_key_exists("text", $data['message'])) {
                // если это пришел старт бота
                if ($text == "/start") {
                        $this->startBot($chat_id, $data);
                }elseif($actionUser == 'petrol_edit'&&is_numeric($text)){
                    $this->update_petrol_price($data);
                }

                }
                //catalog query e
                 else { // другие текстовые сообщения
                    // // смотрим куда отправить данные
                    // if ($action == "addcategory" && $this->isAdmin($chat_id)) {
                    //     // если ждем данные для добавления категории
                    //     $this->adderCategory($text);
                    // } elseif (preg_match("~^addproduct_1_~", $action) && $this->isAdmin($chat_id)) {
                    //     // если ждем данные для добавления товара step_1 - название
                    //     $param = explode("_", $action);
                    //     // отправляем на добавление описания
                    //     $this->addProductName($param['2'], $text);
                    // } elseif (preg_match("~^addproduct_2_~", $action) && $this->isAdmin($chat_id)) {
                    //     // если ждем данные для добавления товара step_2 - описание
                    //     $param = explode("_", $action);
                    //     // отправляем на добавление описания
                    //     $this->addProductDescription($param['2'], $param['3'], $text);
                    // }
                    // else { // если не ждем никаких данных
                        $this->sendMessage($chat_id, "Бизга бу маълумотлар ҳали керак емас. Раҳмат.");
                    // }
                }
            }
         // если пришел запрос на функцию обратного вызова
        elseif (array_key_exists("callback_query", $data)) {
            // смотрим какая функция вызывается
            $func_param = explode("_", $text);
            // определяем функцию в переменную
            $func = $func_param[0];
            // вызываем функцию передаем ей весь объект
            $this->$func($data['callback_query']);
        } // Здесь пришли пока не нужные нам форматы
        else {
            // вернем текст с ошибкой
            $this->sendMessage($chat_id, "Бизга бу маълумотлар ҳали керак емас. Раҳмат.");
        }
        return true;
    }





    /** Отменяем все действия админа
     * @return mixed
     */
    private function adminActionCancel()
    {
        // возвращаем результат запроса
        return $this->pdo->query("DELETE FROM bot_shop_action_admin");
    }

    /** Получаем действие админа из таблицы
     * @return bool
     */
    private function getAdminAction()
    {
        // достаем из базы
        $last = $this->pdo->query("SELECT name FROM bot_shop_action_admin ORDER BY id DESC LIMIT 1");
        // преобразуем строку в массив
        $lastAction = $last->fetch();
        // если есть значение то возвращаем его иначе false
        return isset($lastAction['name']) ? $lastAction['name'] : false;
    }

    /** Записываем действие админа
     * @param $action
     * @return mixed
     */
    private function setActionAdmin($action)
    {
        // отменяем все действия админа
        if ($this->adminActionCancel()) {
            // готовим запрос
            $insertSql = $this->pdo->prepare("INSERT INTO bot_shop_action_admin SET name = :name");
            // возвращаем результат
            return $insertSql->execute(['name' => $action]);
        } else {
            // выводим ошибку
            $this->sendMessage($this->admin, "Ошибка отмены предыдущих действий.");
        }
    }





    /** готовим категории
     * @return array
     */
    private function prepareAdminCategory()
    {
        // создаем массив для кнопок
        $buttons = [];
        // получаем категории из базы
        $category = $this->pdo->query('SELECT * FROM bot_shop_category');
        // проходим циклом по полученным данным из базы
        while ($row = $category->fetch()) {
            // здесь в качестве иконок эмодзи - возможно их не будет видно в редакторе, но они здесь есть
            // выводим иконку для понимания видимости
            $hideIcon = $row['hide'] ? '🙈' : '🐵';
            // формируем кнопки однадля изменения видимости другая для удаления
            $buttons[] = [
                $this->buildInlineKeyBoardButton($row['name'], "showCategory_" . $row['id']),
                $this->buildInlineKeyBoardButton($hideIcon, "hideCategory_" . $row['id'] . "_" . $row['hide']),
                $this->buildInlineKeyBoardButton("✖", "deleteCategory_" . $row['id']),
            ];
        }
        // первичный набор данных для отправки
        $fields = [
            'chat_id' => $this->admin,
            'parse_mode' => 'html'
        ];
        // определяем текст
        $text = "/addcategory - добавить категорию\n/admin - вернуться\n\nКатегории:";
        // смотрим сколько категорий
        if (count($buttons) == 0) {
            // если нет категорий то выводим информ
            $text .= "\nЕще нет категорий в базе.";
        } else {
            // если есть категории то выводим кнопки
            $fields['reply_markup'] = $this->buildInlineKeyBoard($buttons);
        }
        // добавляем в данные текст
        $fields['text'] = $text;
        // возвращаем результат
        return $fields;
    }

    /** Выводим приветственное слово
     * @param $chat_id
     */
    private function startBot($chat_id, $data)
    {
        // достаем пользователя из базы
        $user = $this->pdo->prepare("SELECT * FROM bot_profile WHERE user_id = :user_id");
        $user->execute(['user_id' => $chat_id]);
        // если такого пользователя нет в базе то пишем его туда
        $text='';
        $buttons=null;
        if ($user->rowCount() == 0) {
            // добавляем пользователя
            $fields=[
                'name'=>$data['message']['chat']['first_name'],
                'user_id'=>$chat_id
            ];
            if($this->botManagerQuery('managers',$fields)){
                $newUser = $this->pdo->prepare("INSERT INTO bot_profile SET user_id = :user_id, first_name = :first_name, action = 'start'");
                $newUser->execute([
                    'user_id' => $chat_id,
                    'first_name' => $data['message']['chat']['first_name']
                ]);
            };
            $text = '<b>Сизга хеч кайси Заправка бириктирилмаган, Заправка бириктириш учун админ билан богланинг ва /start ни босинг</b>';
        } else {
            $petrols=$this->botManagerGetQuery('managers/availablep');
            if($petrols){

                $text="<b>";
                // $text="<b>Заправка: ".$station['name']."\nСтатус: ".($station['status']==0?"Ёпиқ":"Очиқ")."\nАдрес: " . (strlen($station['address'])>1?$station['address']:"киритилмаган");
                $i=0;
                // $status_action_text=$station['status']==0?"Очиш":"Ёпиш";
                // $buttons[]=[$this->buildInlineKeyboardButton($status_action_text,"changeVisibility_".$station['id'])];
                while ($petrols[$i]){
                    $text.="\n".$petrols[$i]['type'];
                    // формируем кнопки однадля изменения видимости другая для удаления
                    // $availabilty = $petrols[$i]['is_available']==0?"Кўрсатиш":"Беркитиш";
                    $buttons[]=[
                        $this->buildInlineKeyboardButton($petrols[$i]['type'],"editPetrol_".$petrols[$i]['type'])
                    ];
                    $i++;
                }
                $text.="</b>";
            }else{
                $text="<b>Сизга хеч кайси Заправка бириктирилмаган, Заправка бириктириш учун админ билан богланинг ва /start ни босинг</b>";
            }
            // если пользователь есть то меняем ему действие
            @$this->setActionUser("start", $chat_id);
        }
        return $this->sendMessage($chat_id,$text,$buttons);
    }


    // private function changePetrolVisibility($data){
    //     $obj = $data['data'];
    //     $chat_id = $this->getChatId($data);
    //     $message_id = $data['message']['message_id'];
    //     $text = $data['message']['text'];
    //     $buttons=null;
    //     // разбиваем в массив
    //     $param = explode("_", $obj);

    //     $update_fields=[
    //         'manager_id'=>$chat_id,
    //         'petrol_id'=>$param[1]
    //     ];
    //     if(is_numeric($param[1])){
    //         $update_status = $this->botManagerUpdateQuery("managers/petrol/visibility/",$update_fields);
    //         if($update_status){
    //             $station=$this->botManagerGetQuery('managers/'.$chat_id);
    //             //get stattion data
    //             if($station){
    //                 //list stattion data to text and to buttons
    //                 $text="<b>Заправка: ".$station['name']."\nСтатус: ".($station['status']==0?"Ёпиқ":"Очиқ")."\nАдрес: " . (strlen($station['address'])>1?$station['address']:"киритилмаган");
    //                 $i=0;
    //                 $status_action_text=$station['status']==0?"Очиш":"Ёпиш";
    //                 $buttons[]=[$this->buildInlineKeyboardButton($status_action_text,"changeVisibility_".$station['id'])];
    //                 while ($station['petrols'][$i]){
    //                     $text.="\n".$station['petrols'][$i]['type'].": \n Нархи: ".($station['petrols'][$i]['price']+0) . " сўм \n Статус: ";
    //                     $text.=$station['petrols'][$i]['is_available']==0?"Йўк":"Бор";
    //                     // формируем кнопки однадля изменения видимости другая для удаления
    //                     $availabilty = $station['petrols'][$i]['is_available']==0?"Кўрсатиш":"Беркитиш";
    //                     $buttons[]=[
    //                         $this->buildInlineKeyboardButton($station['petrols'][$i]['type'],"editPetrol_".$station['petrols'][$i]['id']),
    //                         $this->buildInlineKeyboardButton($availabilty,"changePetrolVisibility_".$station['petrols'][$i]['id']),
    //                     ];
    //                     $i++;
    //                 }
    //                 $text.="</b>";
    //             }else{
    //                 $text="<b>Сизга хеч кайси Заправка бириктирилмаган, Заправка бириктириш учун админ билан богланинг ва /start ни босинг</b>";
    //             }
    //             $upMessage=$this->editMessageText($chat_id,$message_id,$text,$buttons);
    //             if($upMessage['ok']){
    //                 //notify if message succesfuly edited
    //                 $this->notice($data['id'], "Статус узгартирилди");
    //             }
    //         }else{
    //             //send message if avaibility cannot be updated
    //             $this->notice($data['id'], "Статусга узгартириш киригизишда хатолик");
    //         }
    //     }else{
    //         //send if there is no petrol with given id
    //         $this->sendMessage($chat_id,'Хабар, муддати тугаган, илтимос /start ни босиб кайтатан бошланг!');
    //     }
    // }

    // private function changeVisibility($data){
    //     $obj = $data['data'];
    //     $chat_id = $this->getChatId($data);
    //     $message_id = $data['message']['message_id'];
    //     $text = $data['message']['text'];
    //     $buttons=null;
    //     // разбиваем в массив
    //     $param = explode("_", $obj);

    //     $update_fields=[
    //         'manager_id'=>$chat_id,
    //         'station_id'=>$param[1]
    //     ];
    //     if(is_numeric($param[1])){
    //         $update_status = $this->botManagerUpdateQuery("managers/change/status",$update_fields);
    //         error_log(json_encode($update_status));
    //         if($update_status){
    //             $station=$this->botManagerGetQuery('managers/'.$chat_id);
    //             //get stattion data
    //             if($station){
    //                 //list stattion data to text and to buttons
    //                 $text="<b>Заправка: ".$station['name']."\nСтатус: ".($station['status']==0?"Ёпиқ":"Очиқ")."\nАдрес: " . (strlen($station['address'])>1?$station['address']:"киритилмаган");
    //                 $i=0;
    //                 $status_action_text=$station['status']==0?"Очиш":"Ёпиш";
    //                 $buttons[]=[$this->buildInlineKeyboardButton($status_action_text,"changeVisibility_".$station['id'])];
    //                 while ($station['petrols'][$i]){
    //                     $text.="\n".$station['petrols'][$i]['type'].": \n Нархи: ".($station['petrols'][$i]['price']+0) . " сўм \n Статус: ";
    //                     $text.=$station['petrols'][$i]['is_available']==0?"Йўк":"Бор";
    //                     // формируем кнопки однадля изменения видимости другая для удаления
    //                     $availabilty = $station['petrols'][$i]['is_available']==0?"Кўрсатиш":"Беркитиш";
    //                     $buttons[]=[
    //                         $this->buildInlineKeyboardButton($station['petrols'][$i]['type'],"editPetrol_".$station['petrols'][$i]['id']),
    //                         $this->buildInlineKeyboardButton($availabilty,"changePetrolVisibility_".$station['petrols'][$i]['id']),
    //                     ];
    //                     $i++;
    //                 }
    //                 $text.="</b>";
    //             }else{
    //                 $text="<b>Сизга хеч кайси Заправка бириктирилмаган, Заправка бириктириш учун админ билан богланинг ва /start ни босинг</b>";
    //             }
    //             $upMessage=$this->editMessageText($chat_id,$message_id,$text,$buttons);
    //             if($upMessage['ok']){
    //                 //notify if message succesfuly edited
    //                 $this->notice($data['id'], "Статус узгартирилди");
    //             }
    //         }else{
    //             //send message if avaibility cannot be updated
    //             $this->notice($data['id'], "Статусга узгартириш киригизишда хатолик");
    //         }
    //     }else{
    //         //send if there is no petrol with given id
    //         $this->sendMessage($chat_id,'Хабар, муддати тугаган, илтимос /start ни босиб кайтатан бошланг!');
    //     }
    // }

    private function editPetrol($data){
        $obj = $data['data'];
        $chat_id = $this->getChatId($data);
        $message_id = $data['message']['message_id'];
        $text = $data['message']['text'];
        // разбиваем в массив
        $param = explode("_", $obj);
        // $setCurrentPetrol =
        $petrolData = $this->botManagerUpdateQuery('managers/uzbeknef',['type'=>$param[1]]);
        $petrolData = $this->botManagerGetQuery('managers/'.$chat_id."/petrol/".$param[1]);
        if($petrolData['id']==$param[1]&&$this->setParamUser("current_petrol",$param[1],$chat_id)&&$this->setActionUser("petrol_edit",$chat_id)){
            $this->editMessageText($chat_id,$message_id,"<b>Илтимос ".$petrolData['type']. " учун янги нархни киргизинг</b>");
            $this->notice($data['id'],"Янги нархни киргизинг");
        }else{
            $this->notice($data['id'],"Узгартиришда хатолик юз берди!");
        }

    }



    private function update_petrol_price($data){
        $chat_id = $this->getChatId($data);
        $price = $this->getText($data);
        $user = $this->pdo->prepare("SELECT current_petrol FROM bot_profile WHERE user_id=:user_id");
        $user->execute(['user_id'=>$chat_id]);
        $current_petrol = $user->fetch()['current_petrol'];

        if($this->botManagerUpdateQuery('managers/'.$chat_id."/petrol/".$current_petrol,['price'=>$price])){
            $this->sendMessage($chat_id,"Бензин, нархи узгартирилди");
            $this->startBot($chat_id,$data);
        }else{
            $this->sendMessage($chat_id,"Хатолик, юз берди");
            $this->startBot($chat_id,$data);
        }


    }
    /** Получаем действие пользователя из таблицы
     * @return bool
     */
    private function getUserAction($user_id)
    {
        // достаем из базы
        $last = $this->pdo->prepare("SELECT action FROM bot_profile WHERE user_id = :user_id");
        $last->execute(['user_id' => $user_id]);
        // преобразуем строку в массив
        $lastAction = $last->fetch();
        // если есть значение то возвращаем его иначе false
        return !empty($lastAction['action']) ? $lastAction['action'] : false;
    }

    private function getUserCurrentProduct($user_id)
    {
        // достаем из базы
        $last = $this->pdo->prepare("SELECT current_product_id FROM bot_profile WHERE user_id = :user_id");
        $last->execute(['user_id' => $user_id]);
        // преобразуем строку в массив
        $product = $last->fetch();
        // если есть значение то возвращаем его иначе false
        return !empty($product['current_product_id']) ? $product['current_product_id'] : false;
    }

    /** Записываем действие пользователя
     * @param $action
     * @return mixed
     */
    private function setActionUser($action, $user_id)
    {
        // готовим запрос
        $insertSql = $this->pdo->prepare("UPDATE bot_profile SET action = :action WHERE user_id = :user_id");
        // возвращаем результат
        return $insertSql->execute(['action' => $action, 'user_id' => $user_id]);
    }

    private function setCurrentProduct($product_id, $user_id)
    {
        // готовим запрос
        $insertSql = $this->pdo->prepare("UPDATE bot_profile SET current_product_id = :id WHERE user_id = :user_id");
        // возвращаем результат
        return $insertSql->execute(['id' => $product_id, 'user_id' => $user_id]);
    }

    /** Записываем действие админа
     * @param $param
     * @param $value
     * @param $user_id
     * @return bool
     */
    private function setParamUser($param, $value, $user_id)
    {
        // готовим запрос
        if($param=="adress"){
            $insertSql = $this->pdo->prepare("UPDATE bot_profile SET " . $param . " = :value, longitude=NULL,latitude=NULL WHERE user_id = :user_id");
        }else{
            $insertSql = $this->pdo->prepare("UPDATE bot_profile SET " . $param . " = :value WHERE user_id = :user_id");
        }
        // возвращаем результат
        return $insertSql->execute(['value' => $value, 'user_id' => $user_id]);
    }


    //////////////////////////////////
    // Вспомогательные методы
    //////////////////////////////////
    /**
     *  Создаем соединение с БД
     */
    private function setPdo()
    {
        // задаем тип БД, хост, имя базы данных и чарсет
        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
        // дополнительные опции
        $opt = [
            // способ обработки ошибок - режим исключений
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // тип получаемого результата по-умолчанию - ассоциативный массив
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // отключаем эмуляцию подготовленных запросов
            PDO::ATTR_EMULATE_PREPARES => false,
            // определяем кодировку запросов
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        // записываем объект PDO в свойство $this->pdo
        $this->pdo = new PDO($dsn, $this->user, $this->pass, $opt);
    }

    /** проверяем на админа
     * @param $chat_id
     * @return bool
     */
    private function isAdmin($chat_id)
    {
        // возвращаем true или fasle
        return $chat_id == $this->admin;
    }

    /** Получаем id чата
     * @param $data
     * @return mixed
     */
    private function getChatId($data)
    {
        if ($this->getType($data) == "callback_query") {
            return $data['callback_query']['message']['chat']['id'];
        }
        return $data['message']['chat']['id'];
    }

    /** Получаем id сообщения
     * @param $data
     * @return mixed
     */
    private function getMessageId($data)
    {
        if ($this->getType($data) == "callback_query") {
            return $data['callback_query']['message']['message_id'];
        }
        return $data['message']['message_id'];
    }

    /** получим значение текст
     * @return mixed
     */
    private function getText($data)
    {
        if ($this->getType($data) == "callback_query") {
            return $data['callback_query']['data'];
        }
        return $data['message']['text'];
    }

    /** Узнаем какой тип данных пришел
     * @param $data
     * @return bool|string
     */
    private function getType($data)
    {
        if (isset($data['callback_query'])) {
            return "callback_query";
        } elseif (isset($data['message']['text'])) {
            return "message";
        } elseif (isset($data['message']['photo'])) {
            return "photo";
        }elseif (isset($data['message']['location'])) {
            return "location";
        }elseif (isset($data['message']['contact'])) {
            return "contact";
        }


        else {
            return false;
        }
    }

    /** Кнопка inline
     * @param $text
     * @param string $callback_data
     * @param string $url
     * @return array
     */
    public function buildInlineKeyboardButton($text, $callback_data = '', $url = '')
    {
        // рисуем кнопке текст
        $replyMarkup = [
            'text' => $text,
        ];
        // пишем одно из обязательных дополнений кнопке
        if ($url != '') {
            $replyMarkup['url'] = $url;
        } elseif ($callback_data != '') {
            $replyMarkup['callback_data'] = $callback_data;
        }
        // возвращаем кнопку
        return $replyMarkup;
    }

    /** набор кнопок inline
     * @param array $options
     * @return string
     */
    public function buildInlineKeyBoard(array $options)
    {
        // собираем кнопки
        $replyMarkup = [
            'inline_keyboard' => $options,
        ];
        // преобразуем в JSON объект
        $encodedMarkup = json_encode($replyMarkup, true);
        // возвращаем клавиатуру
        return $encodedMarkup;
    }

    /** кнопка клавиатуры
     * @param $text
     * @param bool $request_contact
     * @param bool $request_location
     * @return array
     */
    public function buildKeyboardButton($text, $request_contact = false, $request_location = false)
    {
        $replyMarkup = [
            'text' => $text,
            'request_contact' => $request_contact,
            'request_location' => $request_location,
        ];
        return $replyMarkup;
    }

    /** готовим набор кнопок клавиатуры
     * @param array $options
     * @param bool $onetime
     * @param bool $resize
     * @param bool $selective
     * @return string
     */
    public function buildKeyBoard(array $options, $onetime = false, $resize = false, $selective = true)
    {
        $replyMarkup = [
            'keyboard' => $options,
            'one_time_keyboard' => $onetime,
            'resize_keyboard' => $resize,
            'selective' => $selective,
        ];
        $encodedMarkup = json_encode($replyMarkup, true);
        return $encodedMarkup;
    }

    //////////////////////////////////
    // Взаимодействие с Бот Апи
    //////////////////////////////////
    /** Отправляем текстовое сообщение с inline кнопками
     * @param $user_id
     * @param $text
     * @param null $buttons
     * @return mixed
     */
    private function sendMessage($user_id, $text, $buttons = NULL)
    {
        // готовим массив данных
        $data_send = [
            'chat_id' => $user_id,
            'text' => $text,
            'parse_mode' => 'html'
        ];
        // если переданны кнопки то добавляем их к сообщению
        if (!is_null($buttons) && is_array($buttons)) {
            $data_send['reply_markup'] = $this->buildInlineKeyBoard($buttons);
        }
        // отправляем текстовое сообщение
        return $this->botApiQuery("sendMessage", $data_send);
    }

    /** отправляем картинку с текстом
     * @param $user_id
     * @param $img_url
     * @param $caption
     * @param null $buttons
     * @return mixed
     */
    private function sendPhoto($user_id,$img_url, $caption, $buttons = NULL)
    {

        $data_send = [
            'chat_id' => $user_id,
            'photo'=>$img_url,
            'caption' => $caption,
            'parse_mode' => 'html'
        ];

        if (!is_null($buttons) && is_array($buttons)) {
            $data_send['reply_markup'] = $this->buildInlineKeyBoard($buttons);
        }

        return $this->botApiQuery("sendPhoto", $data_send);
    }


    /** Меняем содержимое сообщения
     * @param $user_id
     * @param $message_id
     * @param $text
     * @param null $buttons
     * @return mixed
     */
    private function editMessageText($user_id, $message_id, $text, $buttons = NULL)
    {
        // готовим массив данных
        $data_send = [
            'chat_id' => $user_id,
            'text' => $text,
            'message_id' => $message_id,
            'parse_mode' => 'html'
        ];
        // если переданны кнопки то добавляем их к сообщению
        if (!is_null($buttons) && is_array($buttons)) {
            $data_send['reply_markup'] = $this->buildInlineKeyBoard($buttons);
        }
        // отправляем текстовое сообщение
        return $this->botApiQuery("editMessageText", $data_send);
    }


    /** Уведомление в клиенте
     * @param $cbq_id
     * @param $text
     * @param bool $type
     */
    private function notice($cbq_id, $text = "", $type = false)
    {
        $data = [
            'callback_query_id' => $cbq_id,
            'show_alert' => $type,
        ];

        if (!empty($text)) {
            $data['text'] = $text;
        }

        $this->botApiQuery("answerCallbackQuery", $data);
    }


     /** Запрос к Api
     * @param $method
     * @param array $fields
     * @return mixed
     */
    private function botManagerGetQuery($method)
    {
        $ch = curl_init('https://smartoil.asmgrup.com/api/'.$method);
        $headers = array(
            "Authorization: Bearer secret"
        );
        curl_setopt_array($ch, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers
        ));
        $r = json_decode(curl_exec($ch), true);
        curl_close($ch);
        return $r;
    }

    /** Запрос к Api
     * @param $method
     * @param array $fields
     * @return bool
     */
    private function botManagerUpdateQuery($method, $fields = array())
    {
        $ch = curl_init('https://smartoil.asmgrup.com/api/'.$method);

        $headers = array(
            "Authorization: Bearer secret"
        );
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers

        ));
        // $r = curl_exec($ch);
        // $err = curl_error($ch);
        // error_log($err);
        $r = json_decode(curl_exec($ch), true);
        curl_close($ch);
        return $r;
    }

    /** Запрос к Api
     * @param $method
     * @param array $fields
     * @return bool
     */
    private function botManagerQuery($method, $fields = array())
    {
        $ch = curl_init('https://smartoil.asmgrup.com/api/'.$method);

        $headers = array(
            "Authorization: Bearer secret"
        );
        curl_setopt_array($ch, array(
            CURLOPT_POST => count($fields),
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_SSL_VERIFYPEER => 0,
            // CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers

        ));
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    /** Запрос к BotApi
     * @param $method
     * @param array $fields
     * @return mixed
     */
    private function botApiQuery($method, $fields = array())
    {
        $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/' . $method);
        curl_setopt_array($ch, array(
            CURLOPT_POST => count($fields),
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 10
        ));
        $r = json_decode(curl_exec($ch), true);
        curl_close($ch);
        return $r;
    }
}
