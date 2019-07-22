<?php

namespace local_dde\tables;

use html_writer;

class filter extends table
{
    protected $filters = [];

    function __construct($id = null, \html_table $table = null, $clearCache = false)                                                 //передаем id, чтобы не забивать SESSION
    {
        if(empty($id))
            $id = required_param('tableid', PARAM_TEXT);
        parent::__construct($id, $table, $clearCache);
        if ($filters = $this->loadData('filters')) {                                                                    //если в сессии сохранены фильтра
            $masFilters = [];
            foreach ($filters as $k => $f) {
                $f[5] = optional_param($f[1], $f[5], $f[2]);                                                            //default=opt_param(name, default, datatype)
                $masFilters[$k] = $f;                                                                                   //mas[name-input] = [$type, $name, $typedata, $cols, $length,                                                                                                           $default, $opt]
            }
            $this->filters = $masFilters;
            $this->saveData('filters', $masFilters);                                                                    //сохраняем массив в сессию
        }
    }

    //Создать отфильтрованную таблицу по значениям filters
    //ETALON
//    function filterData($tabledata)                                                                                     //передаем $tabledata - таблица, в которой искать
//    {
//        $mas = [];                                                                                                      //пустой массив для отфильтрованных строк
//
//        foreach ($tabledata as $data) {                                                                                 //для каждой записи из таблицы
//            $canData = true;                                                                                            //флаг
//            foreach ($this->filters as $f) {                                                                            //для каждого фильтра
//                if (!$canData) break;                                                                                   //нет флага - останавливаем,
//                if ($f[3] == "*") {                                                                                     //поиск по всем полям(*)
//                    if ((mb_stripos(implode(',', $data), $f[5], 0, "UTF-8") === false)) {                               //если default(запрос поиска) НЕ содержится в массиве строки
//                        $canData = false;                                                                               //флаг 0
//                    }
//                } else {
//                    //etalon
////                    if ($nums = explode(',', $f[3])) {                                                                  //поля поиска "1,2" преобразуем в массив [1,2]
////                        foreach ($nums as $num) {                                                                       //для каждого поля поиска (столбца таблицы)
////                            if (isset($data[$num])) {                                                                   //если существует ячейка строка[столбец]
////                                if ($f[5] === false || $f[5] === '') {                                                  //если запрос поиска пустой или не задан - ничего не делаем
////
////                                } else
////                                {
////                                    if(($f[2] == PARAM_INT) && $f[5])
////                                    {
////                                        if($f[5] != $data[$num])
////                                        {
////                                            $canData = false;                                                                   //флаг 0
////                                            break;
////                                        }
////                                    }elseif ((mb_stripos($data[$num], $f[5], 0, 'UTF-8') === false)) {                     //если запрос НЕ содержится в ячейке
////                                        $canData = false;                                                                   //флаг 0
////                                        break;
////                                    }
////                                }
////                            }
////                        }
////                    }
//                    if ($nums = explode(',', $f[3])) {                                                                  //поля поиска "1,2" преобразуем в массив [1,2]
//                        foreach ($nums as $num) {                                                                       //для каждого поля поиска (столбца таблицы)
//                            if (isset($data[$num])) {
//                                switch ($f[0]) {
//                                    case 'multiple':
//                                        $hasData = false;
//                                        if (is_array($f[5]) && $f[5] && $f[5][0] !== '') {
//                                            foreach ($f[5] as $s) {
//                                                if ((mb_stripos($data[$num], $s, 0, 'UTF-8') !== false)) {              //если запрос НЕ содержится в ячейке
//                                                    $hasData = true;
//                                                }
//                                            }
//                                        } else {
//                                            $hasData = true;
//                                        }
//                                        if (!$hasData) {
//                                            $canData = false;                                                           //флаг 0
//                                            break;
//                                        }
//                                        break;
//
//                                    /*DDD*/
//                                    case 'datefrom':
//                                        $time = strtotime($f[5]);
//                                        if ($f[5] !== '') {
//                                            if (strtotime($data[$num]) < $time ) {
//                                                $canData = false;
//                                            }
//                                        }
//                                        break;
//
//                                    case 'dateto':
//                                        $time = strtotime($f[5]);
//                                        if ($f[5] !== '') {
//                                            if (strtotime($data[$num]) > $time ) {
//                                                $canData = false;
//                                            }
//                                        }
//                                        break;
//                                    /*DDD*/
//
//                                    default:
//                                        if ($f[5] === false || $f[5] === '') {                                          //если запрос поиска пустой или не задан - ничего не делаем
//
//                                        } elseif ((mb_stripos($data[$num], $f[5], 0, 'UTF-8') === false)) {             //если запрос НЕ содержится в ячейке
//                                            $canData = false;                                                           //флаг 0
//                                            break;
//                                        }
//                                        break;
//                                }
//
//                            }
//                        }
//                    }
//                }
//
//            }
//            if ($canData)                                                                                               //если флаг есть
//                $mas[] = $data;                                                                                         //наполняем массив данных строками
//
//        }
//        $this->countrecords = count($mas);                                                                              //количество строк = количество строк в отфильтрованном массиве
//        return $mas;                                                                                                    //возвращам отфильтрованный массив
//    }

    function filterData($tabledata)                                                                                     //передаем $tabledata - таблица, в которой искать
    {
        $mas = [];                                                                                                      //пустой массив для отфильтрованных строк
        $datastr = $this->getDataAsStr($tabledata);
        foreach ($datastr as $data) {                                                                                 //для каждой записи из таблицы
            $canData = true;                                                                                            //флаг
            foreach ($this->filters as $f) {                                                                            //для каждого фильтра
                if (!$canData) break;                                                                                   //нет флага - останавливаем,
                if ($f[3] == "*") {                                                                                     //поиск по всем полям(*)
                    if ((mb_stripos(implode(',', $data), $f[5], 0, "UTF-8") === false)) {                               //если default(запрос поиска) НЕ содержится в массиве строки
                        $canData = false;                                                                               //флаг 0
                    }
                } else {
                    if ($nums = explode(',', $f[3])) {                                                                  //поля поиска "1,2" преобразуем в массив [1,2]
                        $searchStr = [];
                        foreach ($nums as $num) {
                            $searchStr[] = isset($data[$num]) ? $data[$num] : '';
                        }
                        $searchStr = implode(',', $searchStr);
                        if ($searchStr) {
                            switch ($f[0]) {
                                case 'multiple':
                                    $hasData = false;
                                    if (is_array($f[5]) && $f[5] && $f[5][0] !== '') {
                                        foreach ($f[5] as $s) {
                                            if ((mb_stripos($searchStr, $s, 0, 'UTF-8') !== false)) {              //если запрос НЕ содержится в ячейке
                                                $hasData = true;
                                            }
                                        }
                                    } else {
                                        $hasData = true;
                                    }
                                    if (!$hasData) {
                                        $canData = false;                                                           //флаг 0
                                        break;
                                    }
                                    break;

                                default:
                                    if ($f[5] === false || $f[5] === '') {                                          //если запрос поиска пустой или не задан - ничего не делаем

                                    } elseif ((mb_stripos($searchStr, $f[5], 0, 'UTF-8') === false)) {             //если запрос НЕ содержится в ячейке
                                        $canData = false;                                                           //флаг 0
                                        break;
                                    }
                                    break;
                            }

                        } elseif ($f[5]) $canData = false;
                    }
                }

            }
            if ($canData)                                                                                               //если флаг есть
                $mas[] = $data;                                                                                         //наполняем массив данных строками

        }
        $this->countrecords = count($mas);                                                                              //количество строк = количество строк в отфильтрованном массиве
        return $mas;                                                                                                    //возвращам отфильтрованный массив
    }

    function render()
    {
        $filters = '';
        if ($this->filters) {
            $filters = $this->renderFilters();
        }
        return parent::mainRender($filters, '');
    }

}


?>